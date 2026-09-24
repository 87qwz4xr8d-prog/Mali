# LiftCare — ระบบบำรุงรักษารถกระเช้าไฟฟ้า (PHP)

เว็บแอป PHP + mysqli สำหรับจัดการ Maintenance รถกระเช้าไฟฟ้า  
โครงสร้าง **header · sidebar · content · footer** แบบมืออาชีพ  
UI: **Bootstrap 5 + DataTables + SweetAlert 2** · ฐานข้อมูลผ่าน **phpMyAdmin**

อ้างอิงแนวคิดจากระบบ Visual Maintenance (เช่น vm-yutapak.com) แต่เขียนใหม่ด้วยสแต็กที่ใช้งานบน XAMPP ได้จริง

## ความต้องการ

- PHP 8.1+ (`mysqli`, `mbstring`)
- MySQL 8+ / MariaDB + phpMyAdmin
- Apache (XAMPP / Laragon) หรือ `php -S`

## ติดตั้ง Step by Step

### 1) วางโปรเจกต์

คัดลอกโฟลเดอร์ `liftcare/` ไปที่ เช่น `C:\xampp\htdocs\liftcare`

### 2) สร้างฐานข้อมูล (phpMyAdmin)

1. สร้างฐานข้อมูล `liftcare` (แนะนำ `utf8mb4_unicode_ci` หรือ `utf8mb4_thai_520_w2`)
2. Import ไฟล์ `sql/schema.sql`
3. (ทางเลือก) สร้าง user `liftcare` / รหัส `liftcare` แล้ว Grant สิทธิ์

### 3) ตั้งค่าการเชื่อมต่อ

```
config/database.example.php  →  config/database.php
```

แก้ `host`, `username`, `password`, `database`

### 4) เปิดเว็บ

ชี้ไปที่โฟลเดอร์ **`public/`**

```
http://localhost/liftcare/public/
```

หรือทดสอบ:

```bash
cd public && php -S localhost:8080
```

### 5) เข้าสู่ระบบ

| ผู้ใช้ | รหัสผ่าน | บทบาท |
|--------|----------|--------|
| admin | admin123 | ผู้ดูแลระบบ |
| manager | admin123 | หัวหน้าช่าง |
| tech01 | admin123 | ช่างเทคนิค |

## โครงสร้าง Layout

| ส่วน | ไฟล์ |
|------|------|
| Header | `includes/layout/header.php` |
| Sidebar | `includes/layout/sidebar.php` |
| Content | `modules/*.php` |
| Footer | `includes/layout/footer.php` |

```
liftcare/
├── config/           # database.php
├── includes/         # Auth, Database, helpers, layout
├── modules/          # หน้าจอแต่ละเมนู
├── public/           # จุดเข้าใช้งาน + CSS/JS
├── sql/schema.sql
├── index.php         # redirect → public/
└── INSTALL.txt
```

## เมนูระบบ

1. แดชบอร์ด — สรุปฟลีท / ใบงานค้าง / PM ค้าง  
2. ทะเบียนรถกระเช้า — ทรัพย์สิน ชั่วโมง สถานะ  
3. ใบงานซ่อมบำรุง — Job Request (PM/CM/ตรวจ) + checklist  
4. แผน PM — รอบบำรุง + รายการตรวจ  
5. อะไหล่ — คลัง + จุดสั่งซื้อ  
6. ลูกค้า / ไซต์งาน  
7. รายงาน  
8. ผู้ใช้งาน / ตั้งค่า  

## เทคโนโลยี

PHP · mysqli (Prepared Statements) · Bootstrap 5.3 · DataTables · SweetAlert2 · Font Awesome 6
