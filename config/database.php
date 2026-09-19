<?php

declare(strict_types=1);

/**
 * แก้ค่าการเชื่อมต่อ MySQL ให้ตรงกับเครื่องของคุณ (XAMPP/Laragon)
 */
return [
    'host' => getenv('MALI_DB_HOST') ?: '127.0.0.1',
    'port' => getenv('MALI_DB_PORT') ?: '3306',
    'dbname' => getenv('MALI_DB_NAME') ?: 'mali',
    'username' => getenv('MALI_DB_USER') ?: 'mali',
    'password' => getenv('MALI_DB_PASS') ?: 'mali123',
    'charset' => 'utf8mb4',
];
