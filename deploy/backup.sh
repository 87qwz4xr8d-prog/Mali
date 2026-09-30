#!/usr/bin/env bash
# สำรองข้อมูลมาลี (SQLite + uploads) พร้อมตัดชุดเก่า
# Usage:
#   ./deploy/backup.sh
#   sudo MALI_DATA_DIR=/var/lib/mali MALI_BACKUP_DIR=/var/backups/mali ./deploy/backup.sh
#   ./deploy/backup.sh --mode auto
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

MODE="auto"
while [[ $# -gt 0 ]]; do
  case "$1" in
    --mode) MODE="${2:-auto}"; shift 2 ;;
    --manual) MODE="manual"; shift ;;
    --auto) MODE="auto"; shift ;;
    --help|-h)
      cat <<'EOF'
สำรองข้อมูลมาลีบน PC เซิร์ฟเวอร์

  ./deploy/backup.sh [--mode auto|manual]

ตัวแปร:
  MALI_DATA_DIR      โฟลเดอร์ข้อมูล (ค่าเริ่ม ./data หรือ /var/lib/mali)
  MALI_BACKUP_DIR    โฟลเดอร์เก็บชุดสำรอง (ค่าเริ่ม $MALI_DATA_DIR/backups)
  MALI_BACKUP_KEEP   จำนวนชุดที่เก็บ (ค่าเริ่ม 14)
  MALI_BACKUP_MIRROR โฟลเดอร์สำเนาเพิ่ม (USB/NAS) ถ้าตั้งไว้จะ rsync/cp หลังสำรอง
EOF
      exit 0
      ;;
    *)
      # รองรับรูปแบบเก่า: backup.sh [data_dir] [backup_root]
      if [[ -z "${MALI_DATA_DIR:-}" && -d "$1" ]]; then
        MALI_DATA_DIR="$1"
        shift
        if [[ $# -gt 0 && -z "${MALI_BACKUP_DIR:-}" ]]; then
          MALI_BACKUP_DIR="$1"
          shift
        fi
        continue
      fi
      echo "อาร์กิวเมนต์ไม่รู้จัก: $1" >&2
      exit 1
      ;;
  esac
done

export MALI_DATA_DIR="${MALI_DATA_DIR:-}"
export MALI_BACKUP_DIR="${MALI_BACKUP_DIR:-}"
export MALI_BACKUP_KEEP="${MALI_BACKUP_KEEP:-14}"

if [[ -z "$MALI_DATA_DIR" ]]; then
  if [[ -d /var/lib/mali ]]; then
    MALI_DATA_DIR=/var/lib/mali
  else
    MALI_DATA_DIR="$REPO_ROOT/data"
  fi
  export MALI_DATA_DIR
fi

if [[ -z "${MALI_BACKUP_DIR}" ]]; then
  if [[ "$MALI_DATA_DIR" == /var/lib/mali ]]; then
    MALI_BACKUP_DIR=/var/backups/mali
  else
    MALI_BACKUP_DIR="$MALI_DATA_DIR/backups"
  fi
  export MALI_BACKUP_DIR
fi

NODE_BIN="$(command -v node || true)"
if [[ -z "$NODE_BIN" ]]; then
  echo "ไม่พบ node — ต้องการ Node.js 22+" >&2
  exit 1
fi

# ใช้ CLI ใน repo (ไม่ต้อง build Next)
"$NODE_BIN" "$REPO_ROOT/scripts/mali-backup.mjs" --mode "$MODE"

if [[ -n "${MALI_BACKUP_MIRROR:-}" ]]; then
  mkdir -p "$MALI_BACKUP_MIRROR"
  if command -v rsync >/dev/null 2>&1; then
    rsync -a --delete "$MALI_BACKUP_DIR/" "$MALI_BACKUP_MIRROR/"
  else
    cp -a "$MALI_BACKUP_DIR/." "$MALI_BACKUP_MIRROR/"
  fi
  echo "สำเนาไปที่ mirror: $MALI_BACKUP_MIRROR"
fi
