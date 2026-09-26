# ปฏิทินกลางบริษัท

พนักงานแต่ละแผนกเข้าสู่ระบบแล้วบันทึกงานของตนเองบนปฏิทินกลาง มุมมองเดือนและสัปดาห์ กรองตามแผนกได้ ผู้ดูแลดูได้ทุกแผนก และจัดการผู้ใช้กับแผนก งานที่สร้าง แก้ไข หรือลบจะซิงก์ไปยัง Google Calendar ของบริษัทหนึ่งใบ ถ้ายังไม่ได้ตั้งค่าคีย์ ระบบยังใช้งานในเครื่องได้ และจะแจ้งผ่าน SweetAlert ว่ายังไม่ซิงก์

สิทธิ์ของงาน: ทุกคนที่ล็อกอินเห็นงานของบริษัท แต่สร้าง แก้ไข และลบได้เฉพาะงานของตนเอง รวมถึงผู้ดูแล

## ความต้องการของระบบ

- PHP 8.1 ขึ้นไป พร้อมส่วนขยาย `mysqli`, `json`, `mbstring`, `curl`, `openssl`
- MySQL 8 หรือ MariaDB 10.5 ขึ้นไป และ phpMyAdmin สำหรับนำเข้าไฟล์ SQL
- Composer เฉพาะตอนจะซิงก์ Google Calendar
- เบราว์เซอร์รุ่นปัจจุบัน

เอกสารรากของเว็บคือโฟลเดอร์ `public/`

## นำเข้าฐานข้อมูลด้วย phpMyAdmin

1. เปิด phpMyAdmin
2. ไปที่แท็บ **Import**
3. เลือกไฟล์ `sql/company_calendar.sql`
4. กด **Go**

ไฟล์นี้สร้างฐานข้อมูล `company_calendar` ตารางแผนก ผู้ใช้ และงานตัวอย่าง แล้วนำเข้าข้อมูลตั้งต้น การนำเข้าซ้ำจะลบตารางเดิมในฐานข้อมูลนี้แล้วสร้างใหม่

ถ้าบัญชี MySQL ไม่มีสิทธิ์ `CREATE DATABASE` ให้สร้างฐานข้อมูลชื่อ `company_calendar` (charset `utf8mb4_unicode_ci`) ใน phpMyAdmin ก่อน แล้วค่อยนำเข้าไฟล์เดิม ถ้าคำสั่ง `CREATE DATABASE` ฟ้องสิทธิ์ ให้ลบสองบรรทัด `CREATE DATABASE` กับ `USE` ออก เลือกฐานข้อมูลนั้น แล้วจึง Import

## ตั้งค่าการเชื่อมต่อฐานข้อมูล

```bash
cp config/config.example.php config/config.php
```

แก้ค่าใน `config/config.php` ให้ตรงกับ MySQL ของเครื่อง:

| ค่า | ความหมาย |
|---|---|
| `db.host` | โฮสต์ เช่น `127.0.0.1` |
| `db.port` | พอร์ต เช่น `3306` |
| `db.name` | `company_calendar` |
| `db.user` | ผู้ใช้ MySQL |
| `db.pass` | รหัสผ่าน MySQL |

`config/config.example.php` เป็นแค่ตัวอย่าง (ผู้ใช้ `root` และรหัสว่างแบบเครื่องพัฒนา XAMPP) ไฟล์ `config/config.php` ถูก gitignore ห้าม commit รหัสผ่านจริง

ถ้าไม่สร้าง `config/config.php` ระบบจะใช้ค่าจากไฟล์ตัวอย่าง

## ตั้งค่า Google Calendar

ใช้ service account ซิงก์ไปปฏิทินบริษัทใบเดียว ไม่ต้องให้พนักงานแต่ละคนล็อกอิน Google

1. เปิด [Google Cloud Console](https://console.cloud.google.com/) แล้วสร้างหรือเลือกโปรเจกต์ของบริษัท
2. ไปที่ **APIs & Services → Library** แล้วเปิดใช้ **Google Calendar API**
3. ไปที่ **APIs & Services → Credentials → Create credentials → Service account**
4. ตั้งชื่อ service account แล้วสร้าง
5. เปิด service account นั้น → **Keys → Add key → Create new key → JSON** แล้วดาวน์โหลดไฟล์
6. ย้ายไฟล์ JSON ไปที่ `config/google-service-account.json` ไฟล์นี้ถูก gitignore ห้าม commit
7. เปิด Google Calendar ใบที่เป็นปฏิทินกลางของบริษัท
8. **Settings and sharing → Share with specific people** เพิ่มอีเมล `client_email` จากไฟล์ JSON สิทธิ์ **Make changes to events**
9. ที่หน้าตั้งค่าปฏิทินเดียวกัน เปิด **Integrate calendar** แล้วคัดลอก **Calendar ID**
10. ใน `config/config.php` ตั้ง `google.calendar_id` เป็น Calendar ID นั้น และให้ `google.credentials_path` ชี้ไปที่ไฟล์ JSON (ค่าเริ่มต้นชี้ที่ `config/google-service-account.json` แล้ว)
11. รัน `composer install` ในโฟลเดอร์โปรเจกต์ เพื่อติดตั้ง Google API PHP client

อย่าใส่ `primary` เป็นรหัสปฏิทินของ service account เพราะบัญชีบริการไม่มีปฏิทินส่วนตัว ต้องใช้ Calendar ID ของปฏิทินที่แชร์ให้มัน

เมื่อบันทึก แก้ไข หรือลบงาน ระบบจะส่งการเปลี่ยนแปลงไปปฏิทินนั้น รายละเอียดบน Google จะมีแผนกและชื่อผู้บันทึก

ถ้ายังไม่มีไฟล์คีย์ หรือยังว่าง `google.calendar_id` ระบบบันทึกใน MySQL ตามปกติ และขึ้น SweetAlert ว่ายังไม่ได้ตั้งค่า Google Calendar ถ้ามีไฟล์คีย์แล้วแต่ยังไม่รัน `composer install` ข้อความจะบอกให้ติดตั้งไลบรารีก่อน ถ้าเรียก Google แล้วล้มเหลว งานในระบบยังอยู่ และ SweetAlert จะบอกสาเหตุโดยไม่แสดงคีย์

## วิธีรัน

ในโฟลเดอร์โปรเจกต์:

```bash
composer install
cp config/config.example.php config/config.php
php -S localhost:8080 -t public public/router.php
```

จากนั้นเปิด http://localhost:8080

`composer install` จำเป็นเมื่อจะซิงก์ Google Calendar ถ้ายังไม่ซิงก์ ข้ามคำสั่งนี้ได้ ปฏิทินในเครื่องยังทำงาน

บน Apache ให้ชี้ DocumentRoot ไปที่โฟลเดอร์ `public/` ไฟล์ `public/.htaccess` ส่งเส้นทางที่ไม่มีไฟล์จริงเข้า `index.php`

## บัญชีทดลอง

รหัสผ่านนี้ใช้กับข้อมูลที่มากับไฟล์ SQL ควรเปลี่ยนหลังทดลองใช้งาน

| ชื่อผู้ใช้ | รหัสผ่าน | บทบาท | แผนก | ชื่อ |
|---|---|---|---|---|
| `admin` | `Admin@2569` | ผู้ดูแล | ฝ่ายเทคโนโลยีสารสนเทศ | กานดา ตั้งตรง |
| `somchai` | `Staff@2569` | พนักงาน | ฝ่ายเทคโนโลยีสารสนเทศ | สมชาย ใจดี |
| `wanida` | `Staff@2569` | พนักงาน | ฝ่ายบุคคล | วนิดา ศรีสุข |
| `pranee` | `Staff@2569` | พนักงาน | ฝ่ายการเงิน | ปราณี มั่นคง |
| `anuwat` | `Staff@2569` | พนักงาน | ฝ่ายขาย | อนุวัฒน์ ขายดี |
| `manee` | `Staff@2569` | พนักงาน | ฝ่ายปฏิบัติการ | มานี ทำงาน |

ผู้ดูแลเปิดเมนู **ผู้ใช้** และ **แผนก** ได้ พนักงานสองเมนูนี้ใช้ไม่ได้
