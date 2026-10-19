# คู่มือติดตั้งชุดเอกสาร ISO 9001 / IT Security บน Server

## ติดตั้งเร็ว

```bash
tar -xzf Mali-IT-Security-Policy-Server-Install-v1.0.0.tar.gz
cd mali-itsec-server-install
sudo ./install.sh
sudo /opt/mali/itsec-policies/bin/healthcheck.sh
sudo /opt/mali/itsec-policies/bin/serve-local.sh
# เปิด http://<IP>:8080/
```

ค่าเริ่มต้นติดตั้งที่ `/opt/mali/itsec-policies`

เอกสารเป็น Controlled Document ตาม STD-QMS-001 (ISO 9001:2015 ข้อ 7.5)  
แนะนำจำกัดการเข้าถึงด้วย VPN / IP allowlist / Basic Auth ก่อนเปิดสาธารณะ
