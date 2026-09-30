# สำรองข้อมูลอัตโนมัติบน PC เซิร์ฟเวอร์

มาลีเก็บข้อมูลใน SQLite (`mali.db`) และโฟลเดอร์ `uploads`
ระบบ Auto Backup จะสร้างชุดสำรองแบบ timestamp ทุกวัน ตัดชุดเก่าให้อัตโนมัติ และดูสถานะได้ในหน้า **สำรองข้อมูล** (`/backup`, สิทธิ์ Admin)

## ติดตั้ง Auto (แนะนำ)

### Linux

```bash
sudo ./deploy/install-auto-backup.sh
```

สร้าง `systemd` timer รันทุกวันเวลา 02:00 (หรือ cron ถ้าไม่มี systemd)

### Windows PC เซิร์ฟเวอร์

รัน PowerShell **ในฐานะ Administrator**:

```powershell
powershell -ExecutionPolicy Bypass -File .\deploy\install-auto-backup.ps1
```

สร้าง Task Scheduler ชื่อ `MaliAutoBackup` รันทุกวัน 02:00

## รันมือ

```bash
./deploy/backup.sh --manual
```

```powershell
.\deploy\backup.ps1 -Mode manual
```

```bash
node scripts/mali-backup.mjs --mode manual
```

## กู้คืน

หยุดบริการมาลีก่อน แล้ว:

```bash
sudo systemctl stop mali   # หรือ docker compose stop
sudo ./deploy/restore.sh 20260930-020000
sudo systemctl start mali
```

## ตัวแปรสภาพแวดล้อม

| ตัวแปร | ความหมาย | ค่าเริ่ม |
|---|---|---|
| `MALI_DATA_DIR` | โฟลเดอร์ SQLite + uploads | `./data` หรือ `/var/lib/mali` |
| `MALI_BACKUP_DIR` | โฟลเดอร์เก็บชุดสำรอง | `$MALI_DATA_DIR/backups` หรือ `/var/backups/mali` |
| `MALI_BACKUP_KEEP` | จำนวนชุดที่เก็บ | `14` |
| `MALI_BACKUP_MIRROR` | สำเนาไป USB/NAS หลังสำรอง | (ว่าง) |
| `MALI_BACKUP_HOUR` | ชั่วโมงที่รัน Auto (Linux installer) | `2` |

ไฟล์ config ของ timer: `/etc/mali-backup.env`

## โครงสร้างชุดสำรอง

```
/var/backups/mali/
  last-run.json
  20260930-020000/
    mali.db
    meta.json
    uploads/          # ถ้ามี
```

แต่ละชุดเป็นสแนปช็อต SQLite ที่สอดคล้องกัน (ใช้ `VACUUM INTO`) ไม่ต้องหยุดบริการตอนสำรอง

## ในแอป

เมนู **สำรองข้อมูล** (Admin): สำรองตอนนี้, ดูรายการ, ดาวน์โหลด DB, ลบชุดเก่า
