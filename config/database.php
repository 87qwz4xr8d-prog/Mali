<?php

declare(strict_types=1);

/**
 * แก้ค่าการเชื่อมต่อ MySQL ให้ตรงกับเครื่อง/เซิร์ฟเวอร์
 * รองรับตัวแปรแวดล้อม MALI_DB_* ด้วย
 *
 * XAMPP ค่าเริ่มต้นมักเป็น root / รหัสว่าง
 */
$host = getenv('MALI_DB_HOST');
$port = getenv('MALI_DB_PORT');
$dbname = getenv('MALI_DB_NAME');
$user = getenv('MALI_DB_USER');
$pass = getenv('MALI_DB_PASS');

return [
    'host' => ($host !== false && $host !== '') ? $host : '127.0.0.1',
    'port' => ($port !== false && $port !== '') ? $port : '3306',
    'dbname' => ($dbname !== false && $dbname !== '') ? $dbname : 'mali',
    'username' => ($user !== false && $user !== '') ? $user : 'root',
    'password' => $pass !== false ? (string) $pass : '',
    'charset' => 'utf8mb4',
];
