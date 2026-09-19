# Mali — โปรแกรมควบคุมรายจ่ายจากเงินเดือน

เว็บแอป PHP 8.1+ สำหรับจัดสรรงบเงินเดือนแบบทีละขั้นตอน จัดการสินเชื่อส่วนบุคคล บัญชีรายรับ-รายจ่าย และแนบหลักฐานเอกสาร

## ความต้องการของระบบ

- PHP 8.1+ (แนะนำ 8.1–8.3) พร้อมส่วนขยาย `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `zip`
- MySQL 8+ / MariaDB
- Apache (XAMPP / Laragon / phpMyAdmin) หรือ PHP built-in server สำหรับทดสอบ
- โฟลเดอร์ `vendor/` (มากับชุด ZIP หรือรัน `composer install`)

## รายงาน (PDF / Excel / PowerPoint / พิมพ์)

เมนู **รายงาน** → เลือกเดือน → กดปุ่ม:

| ปุ่ม | ผลลัพธ์ |
|------|---------|
| PDF | ดาวน์โหลดไฟล์ `.pdf` |
| Excel | ดาวน์โหลดไฟล์ `.xlsx` (หลายชีท) |
| PowerPoint | ดาวน์โหลดไฟล์ `.pptx` สรุปภาพรวม 5 สไลด์ |
| พิมพ์ | เปิดหน้าพิมพ์ / Save as PDF จากเบราว์เซอร์ |

## ชุดติดตั้งบน Server

สร้างไฟล์ ZIP พร้อมอัปโหลด:

```bash
bash scripts/build-release.sh
# ได้ไฟล์ dist/Mali-v1.0.0-install.zip
```

อ่านขั้นตอนละเอียดใน [`INSTALL.txt`](INSTALL.txt)

- XAMPP / เครื่องตัวเอง: import [`sql/schema.sql`](sql/schema.sql)
- Shared hosting (cPanel): สร้างฐานก่อน แล้ว import [`sql/schema-tables-only.sql`](sql/schema-tables-only.sql)
- ตรวจระบบหลังติดตั้ง: `public/setup-check.php` (ลบทิ้งหลังใช้)

## การติดตั้ง (XAMPP / phpMyAdmin)

1. คัดลอกโปรเจกต์ไปที่โฟลเดอร์เว็บ เช่น `C:\xampp\htdocs\mali`
2. เปิด **phpMyAdmin** → แท็บ **Import** → เลือกไฟล์ [`sql/schema.sql`](sql/schema.sql) → Go
3. แก้ค่าเชื่อมต่อใน [`config/database.php`](config/database.php) ให้ตรงกับ MySQL ของคุณ

```php
return [
    'host' => '127.0.0.1',
    'port' => '3306',
    'dbname' => 'mali',
    'username' => 'root',      // ค่าเริ่มต้นของ XAMPP
    'password' => '',          // ว่างถ้ายังไม่ได้ตั้งรหัส
    'charset' => 'utf8mb4',
];
```

4. ตั้ง Document Root ไปที่โฟลเดอร์ `public/`  
   - หรือเปิด `http://localhost/mali/public/`
5. เข้าสู่ระบบด้วยบัญชีเริ่มต้น:
   - **ผู้ใช้:** `admin`
   - **รหัสผ่าน:** `admin123`
6. เปลี่ยนรหัสผ่านหลังติดตั้งจริง (อัปเดต `password_hash` ในตาราง `users` ด้วย `password_hash()` ของ PHP)

โฟลเดอร์อัปโหลดหลักฐานอยู่ที่ `public/uploads/receipts/` ต้องเขียนไฟล์ได้ (chmod 775)

## การทดสอบด้วย PHP built-in server

```bash
# สร้างฐานข้อมูลและ import schema ก่อน
mysql -u root -p < sql/schema.sql

cd public
php -S localhost:8080
```

เปิดเบราว์เซอร์ที่ http://localhost:8080

## วิธีใช้งานแบบทีละขั้นตอน

1. เข้า **เปิดเดือน (Step)**
2. **ขั้น 1** ยืนยันยอดยกมา + เงินเดือน (เงินเดือนเข้าสิ้นเดือน ใช้ในเดือนถัดไป)
3. **ขั้น 2** จัดสรรงบชำระสินเชื่อ 5 วงเงิน
4. **ขั้น 3** กำหนดให้คุณแม่และงบส่วนตัว
5. **ขั้น 4** ดูสรุปกรอบเดือน / คาดการณ์ออม 5% และยอดยกไป
6. **เปิดใช้งานเดือน** แล้วบันทึกรายรับ-รายจ่ายพร้อมแนบหลักฐาน
7. เมื่อสิ้นเดือน กด **ปิดเดือน** — ระบบตัดออม 5% จากยอดคงเหลือ และยกส่วนที่เหลือไปเดือนถัดไป

หน้า **รายรับ-รายจ่าย** รองรับแก้ไขรายการ พร้อมฟิลด์ผู้รับ/ร้าน ช่องทางชำระ เลขอ้างอิง และหมายเหตุ  
หน้า **แดชบอร์ด** แสดงกราฟวงกลม (pie) สัดส่วนรายจ่ายตามหมวด และสัดส่วนชำระสินเชื่อ  
หน้า **รายงาน** ส่งออก PDF / Excel / PowerPoint และพิมพ์ได้

## ข้อมูลเริ่มต้นที่ seed ไว้

| รายการ | ค่า |
|--------|-----|
| เงินเดือน | 31,364 บาท |
| Line BK | กู้ 29,800 / จ่าย ~2,500 / 12 เดือน / ดอก 33% |
| Finnix | กู้ 13,500 / จ่าย ~1,500 / 6 เดือน / ดอก 33% |
| Promise | กู้ 20,000 / จ่าย ~1,500 / 12 เดือน / ดอก 33% |
| TTB Global House | กู้ 156,000 / จ่าย ~5,000 / 72 เดือน / ดอก 33% |
| TTB Credits Card | กู้ 94,000 / จ่าย ~5,000 / 6 เดือน / ดอก 33% |
| ค่าใช้จ่ายส่วนตัว | 5,000 บาท/เดือน |
| ให้คุณแม่ | 10,000 บาท/เดือน |
| ออม | 5% ของยอดคงเหลือเมื่อปิดเดือน |

## โครงสร้างโปรเจกต์

```
config/database.php   การเชื่อมต่อ MySQL
sql/schema.sql        สคีมา + ข้อมูลเริ่มต้น
public/               Document root (index.php, assets, uploads)
src/                  Database, Auth, BudgetService, helpers
views/                หน้าจอ UI (ภาษาไทย)
```

## เทคโนโลยี

- PHP 8.1+
- MySQL (phpMyAdmin)
- Bootstrap 5
- SweetAlert2
- Font Awesome 6
- mPDF / PhpSpreadsheet / PhpPresentation (รายงาน)
