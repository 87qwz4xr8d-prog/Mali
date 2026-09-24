#!/usr/bin/env bash
# Backup Mali SQLite (and WAL companions) to a timestamped folder.
# Usage: sudo ./deploy/backup.sh [/var/lib/mali] [/var/backups/mali]
set -euo pipefail

DATA_DIR="${1:-${MALI_DATA_DIR:-/var/lib/mali}}"
BACKUP_ROOT="${2:-/var/backups/mali}"
DB="$DATA_DIR/mali.db"
STAMP="$(date +%Y%m%d-%H%M%S)"
DEST="$BACKUP_ROOT/$STAMP"

if [[ ! -f "$DB" ]]; then
  echo "ไม่พบฐานข้อมูล: $DB" >&2
  exit 1
fi

mkdir -p "$DEST"

if command -v sqlite3 >/dev/null 2>&1; then
  sqlite3 "$DB" ".backup '$DEST/mali.db'"
else
  echo "ไม่มี sqlite3 — สำเนาไฟล์โดยตรง (ควรทำตอนโหลดต่ำ หรือหยุดบริการก่อน)" >&2
  cp -a "$DB" "$DEST/mali.db"
  [[ -f "$DB-wal" ]] && cp -a "$DB-wal" "$DEST/mali.db-wal"
  [[ -f "$DB-shm" ]] && cp -a "$DB-shm" "$DEST/mali.db-shm"
fi

if [[ -d "$DATA_DIR/uploads" ]]; then
  cp -a "$DATA_DIR/uploads" "$DEST/uploads"
fi

echo "สำรองแล้ว: $DEST"
echo "เก็บไฟล์นี้ไว้นอกเครื่องด้วย (USB / NAS) — SQLite ทั้งก้อนคือข้อมูลมาลี"
