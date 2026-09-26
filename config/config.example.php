<?php

declare(strict_types=1);

/**
 * คัดลอกไฟล์นี้เป็น config/config.php แล้วใส่ค่าจริงของเครื่องคุณ
 * config.php และไฟล์คีย์ Google ไม่ถูก commit
 */
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'company_calendar',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'google' => [
        // รหัสปฏิทินบริษัท จาก Google Calendar → ตั้งค่าปฏิทิน → Integrate calendar
        // เว้นว่างไว้ถ้ายังไม่ซิงก์ อย่าใส่คีย์หรือ JSON จริงในไฟล์ตัวอย่างนี้
        'calendar_id' => '',
        'credentials_path' => __DIR__ . '/google-service-account.json',
        'timezone' => 'Asia/Bangkok',
    ],
    'app' => [
        'name' => 'ปฏิทินกลางบริษัท',
        'timezone' => 'Asia/Bangkok',
    ],
];
