# ปฏิทินกลางบริษัท

พนักงานแต่ละแผนกเข้าสู่ระบบแล้วบันทึกงานของตนเองบนปฏิทินกลาง มีมุมมองเดือนและสัปดาห์ กรองตามแผนกได้ ผู้ดูแลดูได้ทุกแผนก และจัดการผู้ใช้กับแผนก

งานที่สร้าง แก้ไข หรือลบจะซิงก์ไปยัง Google Calendar ของบริษัทหนึ่งใบได้ ถ้ายังไม่ตั้งค่าคีย์ ระบบยังใช้งานได้ และจะขึ้น SweetAlert ว่ายังไม่ซิงก์

ทุกคนที่ล็อกอินเห็นงานของบริษัท แต่สร้าง แก้ไข และลบได้เฉพาะงานของตนเอง รวมถึงผู้ดูแล

การทดสอบเบื้องต้นทำบน macOS ตามข้อ 1 ถึง 5 ด้านล่าง ข้อ Google Calendar ทำทีหลังได้

---

## ทดสอบบน macOS

ใช้ Terminal กับ Homebrew ติดตั้ง PHP และ MySQL แล้วเปิดเว็บที่ http://127.0.0.1:8080

macOS รุ่นใหม่ไม่มี PHP มากับเครื่อง ถ้าเปิดเบราว์เซอร์ที่พอร์ต 8080 ทั้งที่ยังไม่ได้รันคำสั่ง PHP จะขึ้น **Error Code: -102**

### 1. ติดตั้ง Homebrew, PHP และ MySQL

เปิดแอป Terminal แล้วตรวจว่ามี Homebrew หรือยัง:

```bash
brew -v
```

ถ้าขึ้นว่าไม่พบคำสั่ง ให้ติดตั้งจาก https://brew.sh ด้วยคำสั่งที่หน้าเว็บนั้นบอก แล้วปิดแล้วเปิด Terminal ใหม่

จากนั้นติดตั้งและเปิด MySQL:

```bash
brew install php mysql
brew services start mysql
php -v
```

`php -v` ต้องขึ้น PHP 8.1 หรือใหม่กว่า ถ้ายังขึ้น `command not found` ให้รันบรรทัดนี้แล้วเปิด Terminal ใหม่ เครื่อง Apple Silicon ใช้พาธแรก เครื่อง Intel ใช้พาธที่สอง:

```bash
echo 'export PATH="/opt/homebrew/bin:$PATH"' >> ~/.zprofile
echo 'export PATH="/usr/local/bin:$PATH"' >> ~/.zprofile
```

### 2. ดาวน์โหลดโปรแกรม

```bash
cd ~
curl -L -o company-calendar.zip https://github.com/87qwz4xr8d-prog/Mali/archive/refs/heads/cursor/company-calendar-015d.zip
unzip company-calendar.zip
mv Mali-cursor-company-calendar-015d company-calendar
cd company-calendar
```

ในโฟลเดอร์นี้ต้องเห็น `public`, `sql`, `config`, `start.sh` และ `README.md` อยู่ในระดับเดียวกัน

ถ้ามี Git อยู่แล้ว ใช้แบบนี้แทนได้:

```bash
cd ~
git clone https://github.com/87qwz4xr8d-prog/Mali.git company-calendar
cd company-calendar
git checkout cursor/company-calendar-015d
```

### 3. สร้างฐานข้อมูล

ใน Terminal ที่อยู่ในโฟลเดอร์ `company-calendar`:

```bash
mysql -u root <<'SQL'
CREATE USER IF NOT EXISTS 'calendar'@'127.0.0.1' IDENTIFIED BY 'calendar';
GRANT ALL PRIVILEGES ON company_calendar.* TO 'calendar'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL
mysql -u root < sql/company_calendar.sql
```

ถ้า `mysql -u root` ถามรหัสผ่าน ให้ใส่รหัสของ MySQL แล้วใช้ `mysql -u root -p` แทนทั้งสองคำสั่ง

ตรวจว่ามีข้อมูล:

```bash
mysql -u calendar -pcalendar -h 127.0.0.1 -e "SHOW TABLES FROM company_calendar;"
```

ต้องเห็นตาราง `departments`, `users` และ `events`

การนำเข้าไฟล์ SQL ซ้ำจะลบตารางเดิมในฐานข้อมูลนี้แล้วสร้างใหม่

### 4. ตั้งค่า config.php

```bash
cp config/config.example.php config/config.php
```

เปิด `config/config.php` ด้วย TextEdit หรือ VS Code แล้วให้ส่วนฐานข้อมูลเป็นแบบนี้:

```php
'db' => [
    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'company_calendar',
    'user' => 'calendar',
    'pass' => 'calendar',
    'charset' => 'utf8mb4',
],
```

`google.calendar_id` เว้นว่างไว้ได้ก่อน บันทึกไฟล์ ไฟล์นี้เป็นค่าของเครื่องคุณ ไม่ต้องส่งขึ้น Git

### 5. เปิดเว็บ

ยังอยู่ในโฟลเดอร์ `company-calendar` รัน:

```bash
sh start.sh
```

หน้าต่าง Terminal ต้องเปิดค้างไว้ ต้องเห็นบรรทัด `Development Server (http://127.0.0.1:8080) started` เบราว์เซอร์จะเปิดให้เอง ถ้าไม่เปิด ให้พิมพ์ที่อยู่นี้เอง:

http://127.0.0.1:8080

ต้องเห็นหน้าเข้าสู่ระบบหัวข้อ **ปฏิทินกลางบริษัท**

ถ้าขึ้น Error Code: -102 ให้เช็คสามอย่างนี้:

1. หน้าต่าง Terminal ที่รัน `sh start.sh` ยังเปิดอยู่ ถ้าปิดไปแล้ว เว็บดับ
2. เปิด http://127.0.0.1:8080 ไม่ใช่ http://localhost:8080 บน Mac คำว่า localhost บางทีชี้ไปคนละทางกับที่ PHP เปิดรับไว้
3. ใน Terminal ไม่มีข้อความว่าไม่พบคำสั่ง `php` ถ้ามี ให้กลับไปทำข้อ 1

อย่าเปิดผ่านโฟลเดอร์อื่น เช่น `http://localhost/company-calendar` เพราะไฟล์หน้าตาและเมนูจะโหลดไม่ครบ

### 6. เข้าสู่ระบบ

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
3. เมนูพนักงานมีแค่ **ปฏิทิน**
4. ออกจากระบบ แล้วเข้าด้วย `admin` / `Admin@2569`
5. ต้องเห็นเมนู **ผู้ใช้** และ **แผนก**

รหัสผ่านมีตัวพิมพ์เล็กใหญ่ ควรเปลี่ยนหลังเริ่มใช้งานจริง

---

## ใช้ phpMyAdmin บน Mac ผ่าน MAMP

ถ้าต้องการกดนำเข้า SQL ในหน้าเว็บแทนคำสั่ง `mysql` ให้ใช้ MAMP

1. ดาวน์โหลด MAMP จาก https://www.mamp.info แล้วลากไปที่ Applications
2. เปิด MAMP กด **Start** ให้ Apache และ MySQL เป็นสีเขียว
3. เปิด http://localhost:8888/phpMyAdmin เข้าด้วยผู้ใช้ `root` รหัสผ่าน `root`
4. แท็บ **Import** เลือกไฟล์ `sql/company_calendar.sql` ในโฟลเดอร์โปรแกรม แล้วกด Import
5. ใน `config/config.php` ใช้พอร์ตของ MAMP ซึ่งค่าเริ่มต้นคือ 8889 ไม่ใช่ 3306:

```php
'db' => [
    'host' => '127.0.0.1',
    'port' => 8889,
    'name' => 'company_calendar',
    'user' => 'root',
    'pass' => 'root',
    'charset' => 'utf8mb4',
],
```

6. PHP ของ Mac อาจยังไม่มี ตอนรันเว็บให้ใช้ PHP ของ MAMP แทน ชื่อโฟลเดอร์รุ่น PHP ในเครื่องคุณอาจต่างจากตัวอย่าง ให้ดูใน `/Applications/MAMP/bin/php/`:

```bash
cd ~/company-calendar
/Applications/MAMP/bin/php/php8.3.14/bin/php -S 127.0.0.1:8080 -t public public/router.php
```

เปิด http://127.0.0.1:8080 และปล่อยให้หน้าต่าง Terminal เปิดค้างไว้เหมือนข้อ 5

---

## ตั้งค่า Google Calendar

ข้ามหัวข้อนี้ได้ระหว่างทดสอบ งานยังบันทึกลง MySQL และจะขึ้น SweetAlert ว่ายังไม่ซิงก์

เมื่อพร้อม ให้ใช้ service account หนึ่งตัวซิงก์ไปปฏิทินบริษัทใบเดียว พนักงานไม่ต้องล็อกอิน Google เอง

### เปิด Calendar API

1. เปิด https://console.cloud.google.com/ ด้วยบัญชี Google ของบริษัท
2. สร้างโปรเจกต์ชื่อ `company-calendar` แล้วสลับมาใช้โปรเจกต์นี้
3. เมนู **APIs & Services → Library** ค้นหา **Google Calendar API** แล้วกด **Enable**

### สร้างคีย์

1. **APIs & Services → Credentials → Create credentials → Service account**
2. ตั้งชื่อ `company-calendar` แล้วกด **Done**
3. กดที่อีเมลของ service account นั้น → **Keys → Add key → Create new key → JSON**
4. ย้ายไฟล์ที่ดาวน์โหลดมาไว้ที่ `config/google-service-account.json` ในโฟลเดอร์โปรแกรม
5. เปิดไฟล์ JSON แล้วคัดลอกค่า `client_email`

ห้ามส่งไฟล์ JSON นี้ขึ้น Git

### แชร์ปฏิทิน

1. เปิด https://calendar.google.com แล้วเลือกหรือสร้างปฏิทิน **ปฏิทินกลางบริษัท**
2. **Settings and sharing → Share with specific people** ใส่อีเมล `client_email` สิทธิ์ **Make changes to events**
3. ที่หัวข้อ **Integrate calendar** คัดลอก **Calendar ID** มักลงท้าย `@group.calendar.google.com`
4. อย่าใส่คำว่า `primary`

### ใส่ค่าใน config.php

```php
'google' => [
    'calendar_id' => 'ใส่ Calendar ID ที่นี่',
    'credentials_path' => __DIR__ . '/google-service-account.json',
    'timezone' => 'Asia/Bangkok',
],
```

ติดตั้งไลบรารีแล้วเปิดเว็บใหม่:

```bash
brew install composer
cd ~/company-calendar
composer install
sh start.sh
```

เพิ่มงานหนึ่งรายการ SweetAlert ต้องบอกว่าซิงก์แล้ว และงานต้องไปโผล่บน Google Calendar ใบนั้น ถ้าซิงก์ไม่สำเร็จ งานใน MySQL ยังอยู่ ให้ตรวจว่าไฟล์ JSON อยู่ถูกที่, Calendar ID ไม่ใช่ `primary`, และแชร์ปฏิทินให้ `client_email` แล้ว

---

## ติดตั้งบน Windows

ใช้ XAMPP ที่มี PHP 8.1 ขึ้นไป เปิด Apache กับ MySQL แล้วนำเข้า `sql/company_calendar.sql` ที่ http://localhost/phpmyadmin

วางโปรแกรมที่ `C:\xampp\htdocs\company-calendar` คัดลอก `config\config.example.php` เป็น `config\config.php` สำหรับ XAMPP ที่รหัส root ยังว่าง ใช้ผู้ใช้ `root` และ `'pass' => ''` พอร์ต `3306`

ดับเบิลคลิก `start.bat` แล้วเปิด http://127.0.0.1:8080 หน้าต่างสีดำต้องเปิดค้างไว้ ถ้าปิดจะขึ้น Error Code: -102

---

## ปัญหาที่พบบ่อย

| อาการ | สาเหตุที่พบบ่อย | วิธีแก้ |
|---|---|---|
| Error Code: -102 | Terminal ที่รันเว็บถูกปิด หรือเปิดคำว่า localhost | รัน `sh start.sh` ค้างไว้ แล้วเปิด http://127.0.0.1:8080 |
| `command not found: php` | Mac ยังไม่ได้ติดตั้ง PHP | `brew install php` แล้วเปิด Terminal ใหม่ |
| หน้าเว็บขึ้นว่าเชื่อมต่อฐานข้อมูลไม่สำเร็จ | MySQL ไม่ทำงาน หรือ user/รหัสใน config ไม่ตรง | `brew services start mysql` แล้วตรวจข้อ 4 หรือพอร์ต 8889 ถ้าใช้ MAMP |
| ขึ้นว่ายังไม่พบตาราง | ยังไม่นำเข้าไฟล์ SQL | รัน `mysql -u root < sql/company_calendar.sql` อีกครั้ง |
| หน้าไม่มีรูปแบบ หรือกดเมนูไม่เจอ | เปิดคนละที่อยู่ | ใช้ http://127.0.0.1:8080 เท่านั้น |
| พอร์ต 8080 ถูกใช้อยู่ | โปรแกรมอื่นจองพอร์ต | ปิดโปรแกรมนั้น หรือเปลี่ยนเลขในคำสั่งเป็น 8081 |
| ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง | พิมพ์ผิด หรือยังไม่มีข้อมูลตัวอย่าง | ใช้รหัสในตารางด้านบน ตัวพิมพ์เล็กใหญ่มีผล |
| ขึ้น SweetAlert ว่ายังไม่ซิงก์ Google | ยังไม่ได้ตั้งคีย์ | ใช้งานต่อได้ งานอยู่ใน MySQL แล้ว |

---

## สิทธิ์หลังติดตั้ง

- พนักงานดูงานทุกแผนก กรองแผนกได้ และเพิ่ม แก้ไข ลบได้เฉพาะงานของตนเอง
- ผู้ดูแลทำอย่างเดียวกับพนักงานในงานของตนเอง และจัดการผู้ใช้กับแผนกเพิ่มได้
- ลบแผนกได้เมื่อไม่มีผู้ใช้และไม่มีงานผูกอยู่
- ลบผู้ใช้ได้เมื่อคนนั้นไม่มีงานค้าง ถ้ามีงานแล้วให้ปิดใช้งานแทน
