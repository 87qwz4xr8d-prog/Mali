@echo off
chcp 65001 >nul
cd /d "%~dp0"

if not exist "public\router.php" (
  echo ไม่พบไฟล์โปรแกรมในโฟลเดอร์นี้
  echo ให้ดับเบิลคลิก start.bat จากโฟลเดอร์ company-calendar ที่มีโฟลเดอร์ public และ sql
  pause
  exit /b 1
)

set "PHP_EXE="
if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if not defined PHP_EXE if exist "D:\xampp\php\php.exe" set "PHP_EXE=D:\xampp\php\php.exe"
if not defined PHP_EXE set "PHP_EXE=php"

echo.
echo กำลังเปิดปฏิทินที่ http://127.0.0.1:8080
echo อย่าปิดหน้าต่างนี้ ถ้าปิดแล้วไปเปิดเบราว์เซอร์ จะขึ้น Error Code: -102
echo ให้เปิด MySQL ใน XAMPP ค้างไว้ด้วย
echo.
start "" cmd /c "timeout /t 2 /nobreak >nul & start http://127.0.0.1:8080/"
"%PHP_EXE%" -S 127.0.0.1:8080 -t public public/router.php
echo.
echo เซิร์ฟเวอร์หยุดทำงาน จึงเปิดหน้าเว็บไม่ได้
echo ถ้าขึ้นว่า php ไม่ใช่คำสั่ง ให้ติดตั้ง XAMPP ที่ C:\xampp แล้วลองใหม่
pause
