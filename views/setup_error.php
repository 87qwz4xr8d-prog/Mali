<section class="login-wrap">
    <div class="login-card">
        <h1>ตั้งค่าฐานข้อมูล</h1>
        <p class="login-lead"><?= e($message ?? 'เชื่อมต่อฐานข้อมูลไม่สำเร็จ') ?></p>
        <ol class="setup-steps">
            <li>เปิด phpMyAdmin แล้วนำเข้า <code>sql/company_calendar.sql</code></li>
            <li>คัดลอก <code>config/config.example.php</code> เป็น <code>config/config.php</code></li>
            <li>ใส่โฮสต์ ชื่อฐานข้อมูล ผู้ใช้ และรหัสผ่านของ MySQL</li>
        </ol>
    </div>
</section>
