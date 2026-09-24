# ติดตั้งมาลีบนเซิร์ฟเวอร์

วิธีหลัก: คัดลอก repo นี้ไปที่เครื่อง Linux แล้วรัน:

```bash
sudo ./deploy/install.sh
```

สคริปต์ติดตั้ง Docker ถ้ายังไม่มี, ถามรหัสผ่าน `admin`, สร้าง `.env`, แล้ว `docker compose up -d --build`

- ไม่ใช่ Docker: `sudo ./deploy/install-native.sh` (Node 22.14+ และ systemd)
- Windows (Docker Desktop): `.\deploy\install.ps1`
- สำรอง SQLite: `sudo ./deploy/backup.sh /var/lib/mali`

รายละเอียดพอร์ต ไฟร์วอลล์ และบัญชี seed อยู่ที่คู่มือติดตั้งเซิร์ฟเวอร์ของโครงการ
