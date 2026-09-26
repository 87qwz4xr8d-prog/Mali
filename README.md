# ปฏิทินกลางบริษัท

พนักงานแต่ละแผนกเข้าสู่ระบบแล้วบันทึกงานของตนเองบนปฏิทินกลาง มีมุมมองเดือนและสัปดาห์ กรองตามแผนกได้ ผู้ดูแลดูได้ทุกแผนก และจัดการผู้ใช้กับแผนก

งานที่สร้าง แก้ไข หรือลบจะซิงก์ไปยัง Google Calendar ของบริษัทหนึ่งใบได้ ถ้ายังไม่ตั้งค่าคีย์ ระบบยังใช้งานได้ และจะขึ้น SweetAlert ว่ายังไม่ซิงก์

ทุกคนที่ล็อกอินเห็นงานของบริษัท แต่สร้าง แก้ไข และลบได้เฉพาะงานของตนเอง รวมถึงผู้ดูแล

ทำตามลำดับด้านล่างจากข้อ 1 ถึงข้อ 6 จะได้ปฏิทินที่ใช้งานในเครื่อง ข้อ 7 เป็นการซิงก์ Google Calendar ทำทีหลังได้

---

## สิ่งที่ต้องมี

- Windows 10 หรือ 11 (ขั้นตอนหลักใช้ XAMPP) หรือ macOS / Linux ที่มี PHP 8.1 ขึ้นไป
- เบราว์เซอร์รุ่นปัจจุบัน เช่น Chrome หรือ Edge
- อินเทอร์เน็ตตอนดาวน์โหลดโปรแกรม และตอนติดตั้งไลบรารี Google
- บัญชี Google ของบริษัท เฉพาะตอนจะซิงก์ปฏิทิน

โปรแกรมนี้ใช้ PHP, MySQL และ phpMyAdmin XAMPP รวมทั้งสามอย่างไว้ให้แล้ว

---

## 1. ติดตั้ง XAMPP

1. เปิด https://www.apachefriends.org/download.html
2. ดาวน์โหลด XAMPP รุ่นที่ระบุ PHP 8.1 หรือใหม่กว่า เช่น PHP 8.2
3. ติดตั้งที่ `C:\xampp` ระหว่างติดตั้งให้เลือก Apache, MySQL และ PHP
4. เปิด **XAMPP Control Panel**
5. กด **Start** ที่แถว Apache และแถว MySQL จนพื้นหลังเป็นสีเขียว
6. ถ้า Windows ถามไฟร์วอลล์ ให้กด Allow

ตรวจว่า PHP พร้อมใช้ เปิด Command Prompt แล้วรัน:

```bat
C:\xampp\php\php.exe -v
```

ต้องขึ้น `PHP 8.1` หรือสูงกว่า ถ้าพอร์ต 80 หรือ 3306 ถูกโปรแกรมอื่นใช้อยู่ XAMPP จะขึ้นสีแดง ให้ปิดโปรแกรมที่ใช้พอร์ตนั้นแล้วกด Start ใหม่

เปิดเบราว์เซอร์ไปที่ http://localhost/phpmyadmin ต้องเห็นหน้า phpMyAdmin จึงไปข้อถัดไป

---

## 2. ดาวน์โหลดไฟล์โปรแกรม

เลือกอย่างใดอย่างหนึ่ง

### วิธีที่ง่าย: ดาวน์โหลด ZIP

1. เปิด https://github.com/87qwz4xr8d-prog/Mali/archive/refs/heads/cursor/company-calendar-015d.zip
2. แตกไฟล์ ZIP
3. ย้ายโฟลเดอร์ที่แตกออกมาไปไว้ที่ `C:\xampp\htdocs\company-calendar`

### วิธีใช้ Git

```bat
cd C:\xampp\htdocs
git clone https://github.com/87qwz4xr8d-prog/Mali.git company-calendar
cd company-calendar
git checkout cursor/company-calendar-015d
```

เมื่อวางถูกที่ โฟลเดอร์ `C:\xampp\htdocs\company-calendar` ต้องมีไฟล์เหล่านี้คู่กัน ไม่ใช่ซ้อนอยู่ในโฟลเดอร์ซ้อนอีกชั้น:

- `public`
- `src`
- `sql`
- `config`
- `composer.json`
- `README.md`

ใน `sql` ต้องมีไฟล์ `company_calendar.sql`

---

## 3. นำเข้าฐานข้อมูลใน phpMyAdmin

1. เปิด http://localhost/phpmyadmin
2. เข้าสู่ระบบ เครื่อง XAMPP ใหม่ๆ ผู้ใช้คือ `root` และรหัสผ่านเว้นว่าง
3. แท็บด้านบนเลือก **Import**
4. กด **Choose File** แล้วเลือก `C:\xampp\htdocs\company-calendar\sql\company_calendar.sql`
5. เลื่อนลงไปกด **Import** หรือ **Go**
6. ต้องขึ้นข้อความสีเขียวว่านำเข้าสำเร็จ

ตรวจผลทางซ้ายมือ ต้องมีฐานข้อมูลชื่อ `company_calendar` และข้างในมี 3 ตาราง:

- `departments` มี 5 แผนก
- `users` มี 6 คน
- `events` มีงานตัวอย่าง

การนำเข้าซ้ำจะลบตารางเดิมในฐานข้อมูลนี้แล้วสร้างใหม่ งานที่พนักงานบันทึกหลังติดตั้งจะหาย ถ้ากด Import ไฟล์นี้อีกครั้ง

ถ้าขึ้นว่าไม่มีสิทธิ์ `CREATE DATABASE` ให้ทำเองก่อน:

1. ใน phpMyAdmin กด **New**
2. ชื่อฐานข้อมูล `company_calendar`
3. Collation เลือก `utf8mb4_unicode_ci` แล้วกดสร้าง
4. คลิกชื่อฐานข้อมูลนั้น
5. เปิดไฟล์ `sql\company_calendar.sql` ด้วย Notepad ลบสองบรรทัด `CREATE DATABASE ...` และ `USE ...` ออก บันทึกเป็นไฟล์ใหม่
6. Import ไฟล์ที่แก้แล้ว โดยเลือกฐานข้อมูล `company_calendar` ไว้ก่อน

---

## 4. ตั้งค่าการเชื่อมต่อฐานข้อมูล

1. ไปที่ `C:\xampp\htdocs\company-calendar\config`
2. คัดลอกไฟล์ `config.example.php` แล้วเปลี่ยนชื่อสำเนาเป็น `config.php`
3. เปิด `config.php` ด้วย Notepad

สำหรับ XAMPP ที่ยังไม่เคยตั้งรหัสผ่าน root ให้เหลือส่วนฐานข้อมูลแบบนี้:

```php
'db' => [
    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'company_calendar',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
],
```

ถ้า MySQL ของคุณมีรหัสผ่าน ให้ใส่รหัสนั้นใน `'pass'`. ชื่อฐานข้อมูลต้องเป็น `company_calendar` ให้ตรงกับไฟล์ SQL

ส่วน Google ให้เว้น `calendar_id` เป็นค่าว่างไว้ก่อนได้:

```php
'google' => [
    'calendar_id' => '',
    'credentials_path' => __DIR__ . '/google-service-account.json',
    'timezone' => 'Asia/Bangkok',
],
```

บันทึกไฟล์ ห้ามเปลี่ยนชื่อกลับไปเป็น `config.example.php` ไฟล์ `config.php` เป็นค่าของเครื่องคุณ ไม่ต้องส่งขึ้น Git

---

## 5. เปิดเว็บ

หน้า http://localhost:8080 จะขึ้น **Error Code: -102** ถ้ายังไม่มีโปรแกรม PHP เปิดรับสายอยู่ การเปิดเบราว์เซอร์อย่างเดียวไม่พอ

วิธีที่ง่ายที่สุดบน Windows:

1. ใน XAMPP Control Panel กด **Start** ที่ MySQL ให้เป็นสีเขียว
2. ไปที่โฟลเดอร์ `C:\xampp\htdocs\company-calendar`
3. ดับเบิลคลิกไฟล์ `start.bat`
4. จะมีหน้าต่างสีดำเปิดขึ้น ต้องปล่อยให้เปิดค้างไว้ ถ้าปิดหน้าต่างนี้ เว็บจะดับและกลับไปขึ้น Error -102
5. เบราว์เซอร์จะเปิด http://127.0.0.1:8080 ให้เอง ถ้าไม่เปิด ให้พิมพ์ที่อยู่นี้เอง

ต้องเห็นบรรทัดประมาณ `Development Server (http://127.0.0.1:8080) started` ในหน้าต่างสีดำ และเห็นหน้าเข้าสู่ระบบหัวข้อ **ปฏิทินกลางบริษัท**

ถ้าดับเบิลคลิกไม่ได้ ให้เปิด Command Prompt แล้วรัน:

```bat
cd C:\xampp\htdocs\company-calendar
C:\xampp\php\php.exe -S 127.0.0.1:8080 -t public public/router.php
```

ใช้ที่อยู่ http://127.0.0.1:8080 ไม่ใช่ http://localhost:8080 บน Windows คำว่า localhost บางทีชี้คนละทางกับที่ PHP เปิดรับไว้ จึงขึ้น Error -102 ทั้งที่เซิร์ฟเวอร์ทำงานอยู่

อย่าเปิดผ่าน `http://localhost/company-calendar/...` เพราะที่อยู่ไฟล์รูปและหน้าภายในจะชี้ผิด Apache ของ XAMPP ไม่ต้อง Start สำหรับวิธีนี้ PHP ในหน้าต่างสีดำเป็นคนแจกหน้าเว็บเอง

---

## 6. ทดลองเข้าสู่ระบบ

ใช้บัญชีจากไฟล์ SQL นี้ได้ทันที ควรเปลี่ยนรหัสผ่านหลังทดลองใช้งานจริง

| ชื่อผู้ใช้ | รหัสผ่าน | บทบาท | แผนก | ชื่อ |
|---|---|---|---|---|
| `admin` | `Admin@2569` | ผู้ดูแล | ฝ่ายเทคโนโลยีสารสนเทศ | กานดา ตั้งตรง |
| `somchai` | `Staff@2569` | พนักงาน | ฝ่ายเทคโนโลยีสารสนเทศ | สมชาย ใจดี |
| `wanida` | `Staff@2569` | พนักงาน | ฝ่ายบุคคล | วนิดา ศรีสุข |
| `pranee` | `Staff@2569` | พนักงาน | ฝ่ายการเงิน | ปราณี มั่นคง |
| `anuwat` | `Staff@2569` | พนักงาน | ฝ่ายขาย | อนุวัฒน์ ขายดี |
| `manee` | `Staff@2569` | พนักงาน | ฝ่ายปฏิบัติการ | มานี ทำงาน |

ลำดับตรวจว่าติดตั้งสำเร็จ:

1. เข้าด้วย `somchai` / `Staff@2569`
2. ต้องขึ้น SweetAlert ว่ายังไม่ได้ตั้งค่า Google Calendar กดตกลง แล้วเห็นปฏิทินเดือนภาษาไทย
3. เมนูพนักงานมีแค่ **ปฏิทิน** ไม่มีเมนูผู้ใช้หรือแผนก
4. ออกจากระบบ แล้วเข้าด้วย `admin` / `Admin@2569`
5. ต้องเห็นเมนู **ผู้ใช้** และ **แผนก**

ผู้ดูแลเพิ่มพนักงานคนจริงได้ที่เมนูผู้ใช้ รหัสผ่านอย่างน้อย 8 ตัวอักษร แต่ละคนอยู่ได้หนึ่งแผนก

---

## 7. ตั้งค่า Google Calendar

ข้ามข้อนี้ได้ ปฏิทินในระบบยังบันทึกงานลง MySQL ตามปกติ ทุกครั้งที่บันทึก แก้ไข หรือลบ จะขึ้น SweetAlert ว่ายังไม่ซิงก์

เมื่อพร้อม ให้ใช้ service account หนึ่งตัวซิงก์ไปปฏิทินบริษัทใบเดียว พนักงานไม่ต้องล็อกอิน Google เอง

### 7.1 เปิด Calendar API

1. เปิด https://console.cloud.google.com/ ด้วยบัญชี Google ของบริษัท
2. แถบบน กดเลือกโปรเจกต์ แล้วกด **New Project**
3. ตั้งชื่อ เช่น `company-calendar` แล้วกดสร้าง สลับมาใช้โปรเจกต์นี้
4. เมนู **APIs & Services → Library**
5. ค้นหา **Google Calendar API** แล้วกด **Enable**

### 7.2 สร้าง service account และดาวน์โหลดคีย์

1. เมนู **APIs & Services → Credentials**
2. กด **Create credentials → Service account**
3. ตั้งชื่อ เช่น `company-calendar` แล้วกด **Done** ไม่ต้องใส่บทบาทเพิ่ม
4. ในรายการ Credentials กดที่อีเมลของ service account ที่เพิ่งสร้าง
5. แท็บ **Keys → Add key → Create new key → JSON → Create**
6. เครื่องจะดาวน์โหลดไฟล์ `.json` หนึ่งไฟล์
7. ย้ายไฟล์นั้นไปที่ `C:\xampp\htdocs\company-calendar\config\google-service-account.json`
8. เปิดไฟล์ JSON ด้วย Notepad หาบรรทัด `"client_email"` แล้วคัดลอกอีเมล ทั้งบรรทัดจะหน้าตาประมาณ `company-calendar@ชื่อโปรเจกต์.iam.gserviceaccount.com`

ห้ามส่งไฟล์ JSON นี้ให้คนอื่น หรืออัปโหลดขึ้น Git เป็นกุญแจของปฏิทินบริษัท

### 7.3 แชร์ปฏิทินบริษัทให้ service account

1. เปิด https://calendar.google.com
2. ถ้ายังไม่มีปฏิทินกลาง ให้สร้างปฏิทินใหม่ชื่อ **ปฏิทินกลางบริษัท** ที่เมนูซ้าย **Other calendars → Create new calendar**
3. ชี้ที่ปฏิทินนั้น กดจุดสามจุด แล้วเลือก **Settings and sharing**
4. หัวข้อ **Share with specific people or groups** กด **Add people and groups**
5. วางอีเมล `client_email` จากข้อ 7.2
6. สิทธิ์เลือก **Make changes to events** แล้วกดส่ง
7. ที่หน้าตั้งค่าเดียวกัน เลื่อนไปหัวข้อ **Integrate calendar**
8. คัดลอก **Calendar ID** มักลงท้ายด้วย `@group.calendar.google.com`

อย่าใส่คำว่า `primary` service account ไม่มีปฏิทินส่วนตัว ต้องใช้ Calendar ID ของใบที่แชร์ให้มัน

### 7.4 ใส่รหัสปฏิทินใน config.php

เปิด `config\config.php` แล้วใส่ Calendar ID ที่คัดลอกมา:

```php
'google' => [
    'calendar_id' => 'ใส่ Calendar ID ที่นี่',
    'credentials_path' => __DIR__ . '/google-service-account.json',
    'timezone' => 'Asia/Bangkok',
],
```

บันทึกไฟล์ พาธคีย์ต้องชี้ไปที่ไฟล์ JSON จริง ถ้าคุณตั้งชื่อไฟล์อย่างอื่น ให้แก้ `credentials_path` ให้ตรง

### 7.5 ติดตั้งไลบรารี Google

1. ติดตั้ง Composer จาก https://getcomposer.org/download/
2. ตอนติดตั้ง ให้ชี้ไปที่ `C:\xampp\php\php.exe` เมื่อโปรแกรมถามหา PHP
3. เปิด Command Prompt:

```bat
cd C:\xampp\htdocs\company-calendar
composer install
```

ต้องสร้างโฟลเดอร์ `vendor` สำเร็จ แล้วปิดหน้าต่าง PHP ที่เปิดค้างอยู่ แล้วรันคำสั่งเปิดเว็บในข้อ 5 ใหม่

### 7.6 ตรวจว่าซิงก์ได้

1. เปิด http://127.0.0.1:8080 แล้วเข้าสู่ระบบ
2. ครั้งนี้ไม่ควรขึ้น SweetAlert ว่ายังไม่ได้ตั้งค่า ตอนเปิดหน้าปฏิทิน
3. กด **เพิ่มงาน** ใส่หัวข้อและเวลา แล้วบันทึก
4. SweetAlert ต้องบอกว่าบันทึกและซิงก์ไป Google Calendar แล้ว
5. เปิด Google Calendar ใบเดิม ต้องเห็นงานนั้นในช่วงเวลาเดียวกัน เขตเวลาประเทศไทย

ถ้าบันทึกในระบบได้แต่ SweetAlert บอกว่าซิงก์ไม่สำเร็จ งานยังอยู่ใน MySQL ให้ตรวจสามจุดนี้: ไฟล์ JSON อยู่ที่พาธใน config, Calendar ID ไม่ใช่ `primary`, และแชร์ปฏิทินให้ `client_email` ด้วยสิทธิ์ Make changes to events แล้ว

---

## ทางเลือก: เปิดผ่าน Apache ของ XAMPP

คำสั่งในข้อ 5 ใช้งานได้เลยและแนะนำให้ใช้แบบนั้น ถ้าต้องการเปิดที่ http://calendar.local โดยไม่ต้องเปิดหน้าต่างคำสั่งค้างไว้ ให้ทำเพิ่มดังนี้

1. เปิด `C:\xampp\apache\conf\extra\httpd-vhosts.conf` แล้วเติมท้ายไฟล์:

```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/company-calendar/public"
    ServerName calendar.local
    <Directory "C:/xampp/htdocs/company-calendar/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

2. เปิด `C:\Windows\System32\drivers\etc\hosts` ด้วย Notepad แบบ Run as administrator แล้วเติมบรรทัด:

```text
127.0.0.1 calendar.local
```

3. ใน XAMPP Control Panel กด **Stop** แล้ว **Start** ที่ Apache
4. เปิด http://calendar.local

DocumentRoot ต้องชี้ที่โฟลเดอร์ `public` เท่านั้น ไฟล์ `public\.htaccess` จะส่งหน้าที่ไม่มีไฟล์จริงเข้า `index.php`

---

## ติดตั้งบน macOS หรือ Linux

ต้องมี PHP 8.1 ขึ้นไป พร้อมส่วนขยาย `mysqli`, `json`, `mbstring`, `curl`, `openssl` และมี MySQL หรือ MariaDB กับ phpMyAdmin

```bash
git clone https://github.com/87qwz4xr8d-prog/Mali.git company-calendar
cd company-calendar
git checkout cursor/company-calendar-015d
cp config/config.example.php config/config.php
```

นำเข้า `sql/company_calendar.sql` ใน phpMyAdmin แล้วแก้ `config/config.php` ให้ตรงกับผู้ใช้ MySQL ของเครื่อง จากนั้น:

```bash
php -S 127.0.0.1:8080 -t public public/router.php
```

หรือรัน `sh start.sh` แล้วเปิด http://127.0.0.1:8080 อย่าปิดหน้าต่างนั้น ขั้นตอน Google Calendar ใช้ข้อ 7 เหมือนกัน และรัน `composer install` ในโฟลเดอร์โปรเจกต์ก่อนซิงก์

---

## ปัญหาที่พบบ่อย

| อาการ | สาเหตุที่พบบ่อย | วิธีแก้ |
|---|---|---|
| หน้าขาว หรือขึ้นว่าเชื่อมต่อฐานข้อมูลไม่สำเร็จ | MySQL ยังไม่ Start หรือ `config.php` ไม่ตรง | Start MySQL ใน XAMPP แล้วตรวจ host, user, pass, ชื่อฐานข้อมูล |
| ขึ้นว่ายังไม่พบตาราง | ยังไม่ Import SQL หรือ Import ไม่สำเร็จ | ทำข้อ 3 ใหม่ แล้วดูว่ามีฐานข้อมูล `company_calendar` |
| เบราว์เซอร์ขึ้น Error Code: -102 ที่ http://localhost:8080 | ยังไม่ได้เปิดเซิร์ฟเวอร์ หรือปิดหน้าต่างสีดำไปแล้ว หรือเปิดคำว่า localhost | ดับเบิลคลิก `start.bat` แล้วเปิด http://127.0.0.1:8080 โดยไม่ปิดหน้าต่างสีดำ |
| เข้าสู่ระบบแล้วหน้าไม่มีรูปแบบ หรือกดเมนูไม่เจอ | เปิดคนละที่อยู่กับพอร์ต 8080 | ใช้ http://127.0.0.1:8080 หลังรันข้อ 5 |
| `'php' is not recognized` | Windows ยังไม่รู้จักคำสั่ง php | ใช้ `C:\xampp\php\php.exe` ตามคำสั่งในข้อ 5 |
| พอร์ต 8080 ถูกใช้อยู่ | โปรแกรมอื่นจองพอร์ตนี้ | เปลี่ยนเลขในคำสั่งเป็น `8081` แล้วเปิด http://localhost:8081 |
| ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง | พิมพ์ผิด หรือยังไม่ Import ข้อมูลตัวอย่าง | ใช้รหัสในตารางข้อ 6 ตัวพิมพ์เล็กใหญ่มีผล |
| บัญชีนี้ถูกปิดใช้งาน | ผู้ดูแลปิดบัญชีนั้น | ให้ผู้ดูแลเปิดสถานะกลับเป็นใช้งาน |
| ขึ้น SweetAlert ว่ายังไม่ซิงก์ Google | ยังไม่ได้ทำข้อ 7 | ใช้งานต่อได้ งานถูกบันทึกใน MySQL แล้ว |
| ซิงก์ Google ไม่สำเร็จ | คีย์ รหัสปฏิทิน หรือสิทธิ์แชร์ไม่ครบ | ตรวจข้อ 7.6 งานในระบบยังไม่หาย |

---

## สิทธิ์หลังติดตั้ง

- พนักงานดูงานทุกแผนก กรองแผนกได้ และเพิ่ม แก้ไข ลบได้เฉพาะงานของตนเอง
- ผู้ดูแลทำอย่างเดียวกับพนักงานในงานของตนเอง และจัดการผู้ใช้กับแผนกเพิ่มได้
- ลบแผนกได้เมื่อไม่มีผู้ใช้และไม่มีงานผูกอยู่
- ลบผู้ใช้ได้เมื่อคนนั้นไม่มีงานค้าง ถ้ามีงานแล้วให้ปิดใช้งานแทน
