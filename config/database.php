<?php

declare(strict_types=1);

/**
 * คัดลอกจาก database.example.php สำหรับชุดติดตั้ง Server
 * แก้ค่าให้ตรงกับ MySQL บนเซิร์ฟเวอร์ของคุณหลังอัปโหลด
 *
 * XAMPP ค่าเริ่มต้นมักเป็น: username=root, password ว่าง
 */
return [
    'host' => '127.0.0.1',
    'port' => '3306',
    'dbname' => 'mali',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];
