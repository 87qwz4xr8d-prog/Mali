#!/usr/bin/env bash
# กู้คืนชุดสำรองมาลี — หยุดบริการก่อนรัน
# Usage: sudo ./deploy/restore.sh 20260930-020000
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

STAMP="${1:-}"
if [[ -z "$STAMP" || "$STAMP" == "-h" || "$STAMP" == "--help" ]]; then
  cat <<'EOF'
กู้คืนข้อมูลมาลีจากชุดสำรอง

  sudo ./deploy/restore.sh <stamp>

ตัวอย่าง:
  sudo systemctl stop mali          # หรือ: docker compose stop
  sudo ./deploy/restore.sh 20260930-020000
  sudo systemctl start mali

ตัวแปร: MALI_DATA_DIR MALI_BACKUP_DIR
EOF
  [[ -z "$STAMP" ]] && exit 1
  exit 0
fi

if [[ ! "$STAMP" =~ ^[0-9]{8}-[0-9]{6}(-[0-9]+)?$ ]]; then
  echo "รหัสชุดสำรองไม่ถูกต้อง: $STAMP" >&2
  exit 1
fi

DATA_DIR="${MALI_DATA_DIR:-}"
BACKUP_ROOT="${MALI_BACKUP_DIR:-}"

if [[ -z "$DATA_DIR" ]]; then
  if [[ -d /var/lib/mali ]]; then DATA_DIR=/var/lib/mali; else DATA_DIR="$REPO_ROOT/data"; fi
fi
if [[ -z "$BACKUP_ROOT" ]]; then
  if [[ "$DATA_DIR" == /var/lib/mali ]]; then BACKUP_ROOT=/var/backups/mali; else BACKUP_ROOT="$DATA_DIR/backups"; fi
fi

SRC="$BACKUP_ROOT/$STAMP"
if [[ ! -f "$SRC/mali.db" ]]; then
  echo "ไม่พบ $SRC/mali.db" >&2
  exit 1
fi

mkdir -p "$DATA_DIR"
# สำรองของเดิมก่อนทับ
SAFETY="$BACKUP_ROOT/pre-restore-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$SAFETY"
if [[ -f "$DATA_DIR/mali.db" ]]; then
  cp -a "$DATA_DIR/mali.db" "$SAFETY/mali.db" || true
  [[ -f "$DATA_DIR/mali.db-wal" ]] && cp -a "$DATA_DIR/mali.db-wal" "$SAFETY/" || true
  [[ -f "$DATA_DIR/mali.db-shm" ]] && cp -a "$DATA_DIR/mali.db-shm" "$SAFETY/" || true
fi

# ลบ WAL/SHM ของเดิมเพื่อไม่ให้ปนกับไฟล์กู้คืน
rm -f "$DATA_DIR/mali.db-wal" "$DATA_DIR/mali.db-shm"
cp -a "$SRC/mali.db" "$DATA_DIR/mali.db"
if [[ -d "$SRC/uploads" ]]; then
  rm -rf "$DATA_DIR/uploads"
  cp -a "$SRC/uploads" "$DATA_DIR/uploads"
fi

if id mali >/dev/null 2>&1; then
  chown -R mali:mali "$DATA_DIR" || true
elif [[ -n "${SUDO_UID:-}" ]]; then
  chown -R "${SUDO_UID}:${SUDO_GID:-$SUDO_UID}" "$DATA_DIR" || true
fi

echo "กู้คืนจาก $SRC ไปที่ $DATA_DIR แล้ว"
echo "ของเดิมเก็บไว้ที่ $SAFETY (ถ้ามี)"
echo "เริ่มบริการมาลีอีกครั้ง (systemctl start mali / docker compose start)"
