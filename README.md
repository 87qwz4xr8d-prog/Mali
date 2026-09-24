# Mali — ระบบซ่อมบำรุงรถกระเช้าไฟฟ้า

มี 2 ชุดใน repo นี้:

| โฟลเดอร์ | สแต็ก | ใช้เมื่อ |
|---------|--------|---------|
| รากโปรเจกต์ (Next.js) | Next.js + SQLite | UI สมัยใหม่ / โหนด |
| **`liftcare/`** | **PHP + mysqli + Bootstrap 5 + DataTables + SweetAlert 2** | **XAMPP / phpMyAdmin ตามที่ขอ** |

---

## LiftCare (PHP) — แนะนำสำหรับ XAMPP

ดูคู่มือเต็มใน [`liftcare/README.md`](liftcare/README.md)

สรุปติดตั้ง:

1. Import `liftcare/sql/schema.sql` ผ่าน phpMyAdmin
2. คัดลอก `liftcare/config/database.example.php` → `database.php`
3. เปิด `http://localhost/liftcare/public/`
4. เข้าสู่ระบบ `admin` / `admin123`

โครงสร้างหน้าจอ: **Header · Sidebar · Content · Footer**

---

## Mali (Next.js)

Visual Maintenance สำหรับอู่ **บจก.ยุธาภัคร์**: ใบแจ้งซ่อม → ใบงานซ่อม (MC) → แผน PM → เครื่องจักร → อะไหล่ → ข้อมูลหลัก → รายงาน

Thai-first UI, SQLite, Next.js. ไม่ได้คัดลอกหน้าตาของระบบเก่า

## รันบนเครื่อง (Next.js)

ต้องการ Node.js 22+ (ใช้ `node:sqlite` ในตัว)

```bash
npm install
cp .env.example .env.local   # ตั้ง MALI_SESSION_SECRET ก่อนขึ้นโปรดักชัน
npm run dev
```

เปิด [http://localhost:3000](http://localhost:3000)

โปรดักชัน:

```bash
npm run build
npm start
```

ฐานข้อมูล SQLite สร้างที่ `data/mali.db` อัตโนมัติ พร้อมข้อมูลตัวอย่างรถกระเช้า Genie / Sinoboom / JLG

## บัญชีทดลอง Next.js (รหัสผ่านเดียวกัน `Mali@2569`)

| ผู้ใช้ | สิทธิ์ | ใช้ทำอะไร |
|---|---|---|
| `admin` | Admin · อรรควุฒิ ศรีชู | ทุกอย่าง รวมสิทธิ์ผู้ใช้ |
| `engineer` | Engineer Review | เปิดใบงานซ่อม, ข้อมูลหลัก |
| `manager` | Manager Approve | อนุมัติปิดงาน |
| `tech` | Technician | บันทึกการซ่อม เบิกอะไหล่ |
| `requestor` | Requestor | สร้างใบแจ้งซ่อม |
| `store` | STORE | รับ/แก้สต็อกอะไหล่ |

## โมดูล (Next.js)

1. **แดชบอร์ด** — งานค้าง, PM เลยกำหนด, อะไหล่ต่ำกว่าขั้นต่ำ, รถเบรคดาวน์
2. **ใบแจ้งซ่อม** — สร้าง/แก้ไข/ดู + ใบตรวจก่อนแจ้ง + ลายเซ็นผู้แจ้ง
3. **ใบงานซ่อม** — เปิดได้จากใบแจ้งเท่านั้น, มอบหมายทีม/ช่าง, วิธีแก้ไข, เบิกอะไหล่, ตรวจหลังซ่อม, ปิดจบ/Renew
4. **แผน PM** — รอบรายเดือนต่อคัน, กดสร้างงาน PM
5. **เครื่องจักร** — รหัส YB/YS/YP, ยี่ห้อ รุ่น ซีเรียล หน้างาน ประวัติ
6. **อะไหล่** — รหัส YTP + พาสโค้ด OEM, min/reorder/max, รับเข้าคลัง
7. **ข้อมูลหลัก** — หน่วยงาน, ทีม, ผู้ใช้/ช่าง, ร้านค้า, ประเภทงาน BD/PM/CM/SV/Drive
8. **รายงาน** — BD เปิดอยู่, ความครบกำหนด PM, อะไหล่ในใบงาน
9. **สถานะรถ** — พร้อมใช้ / รอตรวจ / เบรคดาวน์ / อยู่หน้างาน + งานเช่าหน้างาน

ประเภทงานและสถานะงานตรงกับ Visual Maintenance Online (รอมอบหมายทีมซ่อม → … → ปิดจบงาน)

## สิทธิ์

Requestor, Technician, Engineer Review, Manager Approve, Admin, STORE ตามตารางสิทธิ์ของระบบเดิม
