# LiftCare — ระบบบำรุงรักษารถกระเช้าไฟฟ้า

เว็บแอป PHP สำหรับจัดการ Maintenance รถกระเช้าไฟฟ้า (บูมลิฟต์ / กรรไกร)  
โครงสร้างคล้ายระบบ Visual Maintenance (header · sidebar · content · footer)  
พร้อม UI ที่ใช้งานจริงได้ด้วย **Bootstrap 5 + DataTables + SweetAlert 2 + mysqli**

## ความต้องการของระบบ

- PHP 8.1+ พร้อมส่วนขยาย `mysqli`, `mbstring`
- MySQL 8+ / MariaDB + phpMyAdmin
- Apache (XAMPP / Laragon) หรือ PHP built-in server

## ติดตั้งแบบ Step by Step

### 1) คัดลอกโปรเจกต์

วางโฟลเดอร์โปรเจกต์ไว้ที่ เช่น `C:\xampp\htdocs\liftcare`

### 2) สร้างฐานข้อมูลใน phpMyAdmin

1. เปิด phpMyAdmin → สร้างฐานข้อมูลชื่อ `liftcare` (collation `utf8mb4_thai_520_w2` หรือ `utf8mb4_general_ci`)
2. เลือกฐานข้อมูล → แท็บ **Import** → เลือกไฟล์ `sql/schema.sql` → Go
3. (ทางเลือก) สร้าง user `liftcare` / รหัส `liftcare` แล้วให้สิทธิ์บนฐาน `liftcare`

### 3) ตั้งค่าการเชื่อมต่อ

คัดลอกไฟล์:

```
config/database.example.php  →  config/database.php
```

แก้ค่า `host`, `username`, `password`, `database` ให้ตรงกับเครื่องคุณ

### 4) ชี้ Document Root

- แนะนำให้ชี้เว็บไปที่โฟลเดอร์ **`public/`**
- หรือเปิด `http://localhost/liftcare/` (จะ redirect ไป `public/`)

### 5) เข้าสู่ระบบ

| ผู้ใช้ | รหัสผ่าน | บทบาท |
|--------|----------|--------|
| `admin` | `admin123` | ผู้ดูแลระบบ |
| `manager` | `admin123` | หัวหน้าช่าง |
| `tech01` | `admin123` | ช่างเทคนิค |

**ควรเปลี่ยนรหัสผ่านทันทีหลังติดตั้ง**

## โครงสร้างหลัก (Layout)

```
header   → includes/layout/header.php   (แถบบน + ผู้ใช้)
sidebar  → includes/layout/sidebar.php  (เมนูซ้าย)
content  → modules/*.php                (เนื้อหาแต่ละหน้า)
footer   → includes/layout/footer.php   (ท้ายหน้า + JS)
```

โครงไฟล์สำคัญ:

```
├── config/                 # การเชื่อมต่อฐานข้อมูล
├── includes/               # bootstrap, Auth, helpers, layout
├── modules/                # หน้าจอแต่ละเมนู
├── public/                 # จุดเข้าใช้งานเว็บ + assets
├── sql/schema.sql          # สคีมา + ข้อมูลตัวอย่าง
└── index.php               # redirect → public/
```

## เมนูระบบ

1. **แดชบอร์ด** — สรุปฟลีท / ใบงานค้าง / PM ค้างกำหนด  
2. **ทะเบียนรถกระเช้า** — ข้อมูลทรัพย์สิน ชั่วโมงใช้งาน สถานะ  
3. **ใบงานซ่อมบำรุง** — Job Request (PM / CM / ตรวจสภาพ) + checklist  
4. **แผน PM** — รอบบำรุง + รายการตรวจ  
5. **อะไหล่** — คลังอะไหล่ + จุดสั่งซื้อ  
6. **ลูกค้า / ไซต์งาน** — ข้อมูลลูกค้าเช่า  
7. **รายงาน** — สรุปสถานะและค่าใช้จ่าย  
8. **ผู้ใช้งาน / ตั้งค่า**

## เทคโนโลยี

- PHP + **mysqli** (Prepared Statements)
- Bootstrap 5.3
- DataTables 1.13
- SweetAlert 2
- Font Awesome 6
- ฟอนต์ IBM Plex Sans Thai + Sora

## ทดสอบด้วย PHP built-in server

```bash
cd public
php -S localhost:8080
```

เปิด http://localhost:8080/login.php
