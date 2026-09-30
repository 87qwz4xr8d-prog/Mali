#!/usr/bin/env bash
# ติดตั้งระบบ Auto Backup บน Linux PC เซิร์ฟเวอร์ (systemd timer หรือ cron)
#   sudo ./deploy/install-auto-backup.sh
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "รันด้วย sudo: sudo $0" >&2
  exit 1
fi

INSTALL_DIR="${INSTALL_DIR:-}"
DATA_DIR="${MALI_DATA_DIR:-}"
BACKUP_DIR="${MALI_BACKUP_DIR:-}"
KEEP="${MALI_BACKUP_KEEP:-14}"
MIRROR="${MALI_BACKUP_MIRROR:-}"
HOUR="${MALI_BACKUP_HOUR:-2}"

if [[ -z "$INSTALL_DIR" ]]; then
  if [[ -d /opt/mali/deploy ]]; then
    INSTALL_DIR=/opt/mali
  else
    INSTALL_DIR="$REPO_ROOT"
  fi
fi

if [[ -z "$DATA_DIR" ]]; then
  if [[ -d /var/lib/mali ]]; then DATA_DIR=/var/lib/mali; else DATA_DIR="$INSTALL_DIR/data"; fi
fi
if [[ -z "$BACKUP_DIR" ]]; then
  if [[ "$DATA_DIR" == /var/lib/mali ]]; then BACKUP_DIR=/var/backups/mali; else BACKUP_DIR="$DATA_DIR/backups"; fi
fi

mkdir -p "$BACKUP_DIR" "$DATA_DIR"
chmod +x "$INSTALL_DIR/deploy/backup.sh" "$INSTALL_DIR/deploy/restore.sh" || true

cat >/etc/mali-backup.env <<EOF
MALI_DATA_DIR=$DATA_DIR
MALI_BACKUP_DIR=$BACKUP_DIR
MALI_BACKUP_KEEP=$KEEP
MALI_BACKUP_MIRROR=$MIRROR
EOF
chmod 644 /etc/mali-backup.env

if command -v systemctl >/dev/null 2>&1 && [[ -d /etc/systemd/system ]]; then
  # ปรับ path ใน unit ให้ชี้ repo ที่ติดตั้งจริง
  sed "s|/opt/mali|$INSTALL_DIR|g" "$INSTALL_DIR/deploy/mali-backup.service" >/etc/systemd/system/mali-backup.service
  # ใส่ชั่วโมงที่ต้องการใน timer (ค่าเริ่ม 02:00)
  HOUR_PAD=$(printf '%02d' "$HOUR")
  sed \
    -e "s|/opt/mali|$INSTALL_DIR|g" \
    -e "s|OnCalendar=.*|OnCalendar=*-*-* ${HOUR_PAD}:00:00|" \
    "$INSTALL_DIR/deploy/mali-backup.timer" >/etc/systemd/system/mali-backup.timer

  systemctl daemon-reload
  systemctl enable --now mali-backup.timer
  echo "เปิด mali-backup.timer แล้ว (ทุกวัน ${HOUR_PAD}:00)"
  systemctl list-timers mali-backup.timer --no-pager || true
else
  CRON_FILE=/etc/cron.d/mali-backup
  cat >"$CRON_FILE" <<EOF
# Mali auto backup — ทุกวันเวลา ${HOUR}:00
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
0 ${HOUR} * * * root cd $INSTALL_DIR && MALI_DATA_DIR=$DATA_DIR MALI_BACKUP_DIR=$BACKUP_DIR MALI_BACKUP_KEEP=$KEEP MALI_BACKUP_MIRROR=$MIRROR $INSTALL_DIR/deploy/backup.sh --mode auto >>/var/log/mali-backup.log 2>&1
EOF
  chmod 644 "$CRON_FILE"
  echo "ติดตั้ง cron แล้ว: $CRON_FILE"
fi

# ทดสอบสำรองครั้งแรก
echo "ทดสอบสำรองครั้งแรก..."
MALI_DATA_DIR="$DATA_DIR" MALI_BACKUP_DIR="$BACKUP_DIR" MALI_BACKUP_KEEP="$KEEP" \
  MALI_BACKUP_MIRROR="$MIRROR" "$INSTALL_DIR/deploy/backup.sh" --mode auto || {
  echo "ทดสอบสำรองยังไม่สำเร็จ (อาจยังไม่มี mali.db) — timer/cron ถูกติดตั้งแล้ว จะรันเมื่อมีฐานข้อมูล" >&2
}

cat <<EOF

Auto Backup พร้อมแล้ว

  ข้อมูล:     $DATA_DIR
  สำรองที่:   $BACKUP_DIR  (เก็บ $KEEP ชุด)
  Mirror:     ${MIRROR:-"(ไม่ตั้ง)"}
  รันมือ:     sudo $INSTALL_DIR/deploy/backup.sh --manual
  กู้คืน:     sudo $INSTALL_DIR/deploy/restore.sh <stamp>
  ในแอป:     /backup (ผู้ใช้ Admin)

คัดลอกโฟลเดอร์สำรองไป USB/NAS เป็นระยะ หรือตั้ง MALI_BACKUP_MIRROR
EOF
