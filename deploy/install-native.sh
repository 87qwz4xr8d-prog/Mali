#!/usr/bin/env bash
# Mali CMMS Linux installer without Docker (Node 22+ and systemd).
#   sudo ./deploy/install-native.sh
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

NON_INTERACTIVE=0
for arg in "$@"; do
  case "$arg" in
    --non-interactive|-y) NON_INTERACTIVE=1 ;;
    --help|-h)
      cat <<'EOF'
ติดตั้งมาลีบนลินุกซ์ด้วย Node.js + systemd (ไม่ใช้ Docker)

  sudo ./deploy/install-native.sh
  sudo MALI_ADMIN_PASSWORD='…' PORT=3000 ./deploy/install-native.sh -y

ต้องการ Node.js 22.14+ บน PATH (หรือที่ /usr/bin/node)
EOF
      exit 0
      ;;
  esac
done

if [[ "$(id -u)" -ne 0 ]]; then
  echo "รันด้วย sudo: sudo $0" >&2
  exit 1
fi

prompt() {
  local var="$1" message="$2" default="${3:-}"
  local current="${!var:-}"
  if [[ -n "$current" ]]; then
    return 0
  fi
  if [[ "$NON_INTERACTIVE" -eq 1 ]]; then
    if [[ -n "$default" ]]; then
      printf -v "$var" '%s' "$default"
      return 0
    fi
    echo "โหมด --non-interactive ต้องตั้ง $var" >&2
    exit 1
  fi
  local reply
  if [[ -n "$default" ]]; then
    read -r -p "$message [$default]: " reply || true
    printf -v "$var" '%s' "${reply:-$default}"
  else
    read -r -p "$message: " reply
    printf -v "$var" '%s' "$reply"
  fi
}

prompt_secret() {
  local var="$1" message="$2"
  if [[ -n "${!var:-}" ]]; then
    return 0
  fi
  if [[ "$NON_INTERACTIVE" -eq 1 ]]; then
    echo "โหมด --non-interactive ต้องตั้ง $var" >&2
    exit 1
  fi
  local a b
  read -r -s -p "$message: " a
  echo
  read -r -s -p "ยืนยันรหัสผ่าน: " b
  echo
  if [[ "$a" != "$b" ]]; then
    echo "รหัสผ่านไม่ตรงกัน" >&2
    exit 1
  fi
  printf -v "$var" '%s' "$a"
}

NODE_BIN="$(command -v node || true)"
if [[ -z "$NODE_BIN" ]]; then
  echo "ไม่พบ node — ติดตั้ง Node.js 22.14+ ก่อน (https://nodejs.org หรือ NodeSource)" >&2
  exit 1
fi
NODE_MAJOR="$("$NODE_BIN" -p "process.versions.node.split('.')[0]")"
if [[ "$NODE_MAJOR" -lt 22 ]]; then
  echo "มาลีใช้ node:sqlite ต้องการ Node 22+ (ตอนนี้ $($NODE_BIN -v))" >&2
  exit 1
fi
NPM_BIN="$(command -v npm || true)"
if [[ -z "$NPM_BIN" ]]; then
  echo "ไม่พบ npm" >&2
  exit 1
fi

PORT="${PORT:-}"
MALI_DATA_DIR="${MALI_DATA_DIR:-}"
MALI_SESSION_SECRET="${MALI_SESSION_SECRET:-}"
MALI_ADMIN_PASSWORD="${MALI_ADMIN_PASSWORD:-}"
INSTALL_DIR="${INSTALL_DIR:-/opt/mali}"
MALI_SHOW_SEED_LOGINS="${MALI_SHOW_SEED_LOGINS:-0}"
MALI_HTTPS="${MALI_HTTPS:-0}"

prompt PORT "พอร์ตที่เปิดให้เข้ามาลี" "3000"
prompt MALI_DATA_DIR "โฟลเดอร์เก็บ SQLite และอัปโหลด" "/var/lib/mali"
prompt_secret MALI_ADMIN_PASSWORD "รหัสผ่านผู้ใช้ admin (ติดตั้งครั้งแรก)"

if [[ ${#MALI_ADMIN_PASSWORD} -lt 8 ]]; then
  echo "รหัสผ่าน admin สั้นเกินไป (อย่างน้อย 8 ตัวอักษร)" >&2
  exit 1
fi

if [[ -z "$MALI_SESSION_SECRET" ]]; then
  if command -v openssl >/dev/null 2>&1; then
    MALI_SESSION_SECRET="$(openssl rand -hex 32)"
  else
    MALI_SESSION_SECRET="$(head -c 32 /dev/urandom | od -An -tx1 | tr -d ' \n')"
  fi
fi

if ! id mali >/dev/null 2>&1; then
  useradd --system --home /opt/mali --shell /usr/sbin/nologin mali
fi

mkdir -p "$MALI_DATA_DIR/uploads" "$INSTALL_DIR"
if [[ "$REPO_ROOT" != "$INSTALL_DIR" ]]; then
  echo "คัดลอกซอร์สไปที่ $INSTALL_DIR"
  tar -C "$REPO_ROOT" --exclude='.git' --exclude='node_modules' --exclude='.next' --exclude='data' -cf - . \
    | tar -C "$INSTALL_DIR" -xf -
fi

systemd_quote() {
  local s="$1"
  if [[ "$s" == *$'\n'* ]]; then
    echo "ค่านี้ห้ามมีบรรทัดใหม่" >&2
    exit 1
  fi
  s="${s//\\/\\\\}"
  s="${s//\"/\\\"}"
  printf '"%s"' "$s"
}

umask 077
cat >/etc/mali.env <<EOF
NODE_ENV=production
PORT=${PORT}
HOSTNAME=0.0.0.0
MALI_DATA_DIR=$(systemd_quote "$MALI_DATA_DIR")
MALI_SESSION_SECRET=$(systemd_quote "$MALI_SESSION_SECRET")
MALI_ADMIN_PASSWORD=$(systemd_quote "$MALI_ADMIN_PASSWORD")
MALI_SHOW_SEED_LOGINS=${MALI_SHOW_SEED_LOGINS}
MALI_HTTPS=${MALI_HTTPS}
EOF
chmod 600 /etc/mali.env
chown root:mali /etc/mali.env || chmod 600 /etc/mali.env

echo "npm ci + build ที่ $INSTALL_DIR (ใช้เวลา) …"
cd "$INSTALL_DIR"
"$NPM_BIN" ci
"$NPM_BIN" run build
mkdir -p .next/standalone/.next .next/standalone/public
cp -a .next/static .next/standalone/.next/static
if [[ -d public ]]; then
  cp -a public/. .next/standalone/public/
fi

# systemd unit: ExecStart uses /usr/bin/node — use the detected binary if different
UNIT_SRC="$INSTALL_DIR/deploy/mali.service"
UNIT_DST="/etc/systemd/system/mali.service"
sed "s|/usr/bin/node|${NODE_BIN}|" "$UNIT_SRC" >"$UNIT_DST"

chown -R mali:mali "$INSTALL_DIR" "$MALI_DATA_DIR"

systemctl daemon-reload
systemctl enable --now mali.service

echo "รอให้บริการพร้อม …"
ready=0
for _ in $(seq 1 40); do
  if curl -fsS -o /dev/null "http://127.0.0.1:${PORT}/login" 2>/dev/null; then
    ready=1
    break
  fi
  sleep 2
done

if [[ "$ready" -ne 1 ]]; then
  echo "systemd ยังไม่ตอบที่ /login — journalctl -u mali -n 80" >&2
  journalctl -u mali -n 80 --no-pager || true
  exit 1
fi

cat <<EOF

มาลีทำงานแล้ว (systemd)

  URL:      http://<ไอพีเซิร์ฟเวอร์>:${PORT}/login
  หน่วย:    systemctl status mali
  ข้อมูล:   ${MALI_DATA_DIR}/mali.db
  env:      /etc/mali.env
  สำรอง:    sudo $INSTALL_DIR/deploy/backup.sh ${MALI_DATA_DIR}

เข้าด้วย admin + รหัสที่ตั้งตอนติดตั้ง
บัญชี seed อื่นเป็นข้อมูลทดลองติดตั้งครั้งแรก (รหัส Mali@2569)
EOF
