#!/usr/bin/env bash
# Mali CMMS Linux installer (Docker).
# Copies nothing to GitHub — run this from a Mali checkout on the target server:
#   sudo ./deploy/install.sh
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
ติดตั้งมาลีบนลินุกซ์ด้วย Docker

  sudo ./deploy/install.sh
  sudo MALI_ADMIN_PASSWORD='…' PORT=3000 ./deploy/install.sh --non-interactive

ตัวแปรสภาพแวดล้อม (เลือกได้):
  PORT                 พอร์ตโฮสต์ (ค่าเริ่ม 3000)
  MALI_BIND            ที่อยู่ bind (ค่าเริ่ม 0.0.0.0)
  MALI_DATA_DIR        โฟลเดอร์ข้อมูลโฮสต์ (ค่าเริ่ม /var/lib/mali)
  MALI_SESSION_SECRET  ถ้าไม่ใส่ จะสุ่มให้
  MALI_ADMIN_PASSWORD  รหัสผ่าน admin ครั้งแรก (บังคับในโหมดไม่ถาม)
  MALI_SHOW_SEED_LOGINS  1 = แสดงบัญชีทดลองบนหน้าเข้าสู่ระบบ
EOF
      exit 0
      ;;
  esac
done

if [[ "$(id -u)" -ne 0 ]]; then
  echo "รันด้วย sudo: sudo $0" >&2
  exit 1
fi

if [[ ! -f "$REPO_ROOT/docker-compose.yml" || ! -f "$REPO_ROOT/Dockerfile" ]]; then
  echo "ไม่พบ Dockerfile / docker-compose.yml ที่ $REPO_ROOT" >&2
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

PORT="${PORT:-}"
MALI_BIND="${MALI_BIND:-}"
MALI_DATA_DIR="${MALI_DATA_DIR:-}"
MALI_SESSION_SECRET="${MALI_SESSION_SECRET:-}"
MALI_ADMIN_PASSWORD="${MALI_ADMIN_PASSWORD:-}"
MALI_SHOW_SEED_LOGINS="${MALI_SHOW_SEED_LOGINS:-0}"
MALI_HTTPS="${MALI_HTTPS:-0}"

prompt PORT "พอร์ตที่เปิดให้เข้ามาลี" "3000"
prompt MALI_BIND "ที่อยู่ bind (0.0.0.0 = ทุกอินเทอร์เฟซ)" "0.0.0.0"
prompt MALI_DATA_DIR "โฟลเดอร์เก็บ SQLite และอัปโหลด" "/var/lib/mali"
prompt_secret MALI_ADMIN_PASSWORD "รหัสผ่านผู้ใช้ admin (ติดตั้งครั้งแรก)"

if [[ ${#MALI_ADMIN_PASSWORD} -lt 8 ]]; then
  echo "รหัสผ่าน admin สั้นเกินไป (อย่างน้อย 8 ตัวอักษร)" >&2
  exit 1
fi
if [[ "$MALI_ADMIN_PASSWORD" == "Mali@2569" ]]; then
  echo "คำเตือน: ใช้รหัสทดลอง Mali@2569 — เปลี่ยนทันทีหลังติดตั้ง" >&2
fi

if [[ -z "$MALI_SESSION_SECRET" ]]; then
  if command -v openssl >/dev/null 2>&1; then
    MALI_SESSION_SECRET="$(openssl rand -hex 32)"
  else
    MALI_SESSION_SECRET="$(head -c 32 /dev/urandom | od -An -tx1 | tr -d ' \n')"
  fi
  echo "สร้าง MALI_SESSION_SECRET แล้ว (เก็บใน .env ของเครื่องนี้เท่านั้น)"
fi

install_docker() {
  if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
    echo "พบ Docker แล้ว: $(docker --version)"
    return 0
  fi
  echo "กำลังติดตั้ง Docker Engine + Compose …"
  if ! command -v curl >/dev/null 2>&1; then
    if command -v apt-get >/dev/null 2>&1; then
      apt-get update -y
      apt-get install -y --no-install-recommends curl ca-certificates
    else
      echo "ต้องมี curl เพื่อติดตั้ง Docker" >&2
      exit 1
    fi
  fi
  curl -fsSL https://get.docker.com | sh
  if ! docker compose version >/dev/null 2>&1; then
    echo "ติดตั้ง Docker แล้วแต่ยังไม่มี docker compose plugin" >&2
    exit 1
  fi
}

compose_escape() {
  local s="$1"
  if [[ "$s" == *$'\n'* ]]; then
    echo "ค่านี้ห้ามมีบรรทัดใหม่" >&2
    exit 1
  fi
  s="${s//\\/\\\\}"
  s="${s//\"/\\\"}"
  s="${s//\$/\$\$}"
  printf '%s' "$s"
}

write_env() {
  local env_file="$REPO_ROOT/.env"
  umask 077
  cat >"$env_file" <<EOF
NODE_ENV=production
PORT=${PORT}
MALI_BIND=${MALI_BIND}
MALI_DATA_DIR="$(compose_escape "$MALI_DATA_DIR")"
MALI_SESSION_SECRET="$(compose_escape "$MALI_SESSION_SECRET")"
MALI_ADMIN_PASSWORD="$(compose_escape "$MALI_ADMIN_PASSWORD")"
MALI_SHOW_SEED_LOGINS=${MALI_SHOW_SEED_LOGINS}
MALI_HTTPS=${MALI_HTTPS}
EOF
  chmod 600 "$env_file"
  echo "เขียน $env_file (สิทธิ์ 600)"
}

install_docker
mkdir -p "$MALI_DATA_DIR/uploads"
# Image runs as uid 1000 (node)
if command -v chown >/dev/null 2>&1; then
  chown -R 1000:1000 "$MALI_DATA_DIR" || true
fi

write_env

echo "กำลัง build และสตาร์ทมาลี (ครั้งแรกอาจใช้เวลาหลายนาที) …"
docker compose -f "$REPO_ROOT/docker-compose.yml" --env-file "$REPO_ROOT/.env" up -d --build

if ! command -v curl >/dev/null 2>&1; then
  if command -v apt-get >/dev/null 2>&1; then
    apt-get update -y
    apt-get install -y --no-install-recommends curl ca-certificates
  fi
fi

echo "รอให้บริการพร้อม …"
ready=0
for _ in $(seq 1 60); do
  if curl -fsS -o /dev/null "http://127.0.0.1:${PORT}/login" 2>/dev/null; then
    ready=1
    break
  fi
  sleep 2
done

if [[ "$ready" -ne 1 ]]; then
  echo "คอนเทนเนอร์ยังไม่ตอบที่ /login — ดูล็อก: docker compose logs --tail=80" >&2
  docker compose -f "$REPO_ROOT/docker-compose.yml" logs --tail=80 || true
  exit 1
fi

cat <<EOF

มาลีทำงานแล้ว

  URL:      http://<ไอพีเซิร์ฟเวอร์>:${PORT}/login
  ข้อมูล:   ${MALI_DATA_DIR}/mali.db  (SQLite) และ ${MALI_DATA_DIR}/uploads
  รีสตาร์ท: docker compose restart
  ล็อก:     docker compose logs -f
  สำรอง:    sudo ./deploy/backup.sh ${MALI_DATA_DIR}

เข้าสู่ระบบด้วยผู้ใช้ admin และรหัสที่ตั้งตอนติดตั้ง
บัญชี seed อื่น (tech / engineer / manager / requestor / store) เป็นข้อมูลทดลองติดตั้งครั้งแรก
รหัสเริ่มต้นของบัญชีเหล่านั้นคือ Mali@2569 — เปลี่ยนหรือปิดบัญชีหลังใช้งานจริง

เปิดไฟร์วอลล์ (ถ้าใช้ ufw):  sudo ufw allow ${PORT}/tcp
EOF
