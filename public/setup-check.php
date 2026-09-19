<?php

declare(strict_types=1);

/**
 * ตรวจสภาพแวดล้อมก่อนใช้งาน Mali
 * ลบไฟล์นี้ทิ้งหลังติดตั้งสำเร็จเพื่อความปลอดภัย
 */

$checks = [];

$checks[] = [
    'label' => 'เวอร์ชัน PHP ≥ 8.2',
    'ok' => version_compare(PHP_VERSION, '8.2.0', '>='),
    'detail' => 'ปัจจุบัน: ' . PHP_VERSION,
];

foreach (['pdo', 'pdo_mysql', 'mbstring', 'fileinfo'] as $ext) {
    $checks[] = [
        'label' => "ส่วนขยาย {$ext}",
        'ok' => extension_loaded($ext),
        'detail' => extension_loaded($ext) ? 'พร้อมใช้งาน' : 'ยังไม่มี — ต้องเปิดใน php.ini',
    ];
}

$uploadDir = __DIR__ . '/uploads/receipts';
$writable = is_dir($uploadDir) && is_writable($uploadDir);
$checks[] = [
    'label' => 'โฟลเดอร์อัปโหลดเขียนได้',
    'ok' => $writable,
    'detail' => $uploadDir,
];

$configPath = dirname(__DIR__) . '/config/database.php';
$dbOk = false;
$dbDetail = 'ยังไม่ได้ทดสอบ';
if (is_file($configPath) && extension_loaded('pdo_mysql')) {
    try {
        $config = require $configPath;
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['dbname'],
            $config['charset']
        );
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $dbOk = count($tables) > 0;
        $dbDetail = $dbOk
            ? 'เชื่อมต่อสำเร็จ · พบตาราง ' . count($tables) . ' ตาราง'
            : 'เชื่อมต่อได้ แต่ยังไม่มีตาราง — ให้ import sql/schema.sql';
    } catch (Throwable $e) {
        $dbDetail = 'เชื่อมต่อไม่สำเร็จ: ' . $e->getMessage();
    }
}

$checks[] = [
    'label' => 'การเชื่อมต่อ MySQL',
    'ok' => $dbOk,
    'detail' => $dbDetail,
];

$allOk = true;
foreach ($checks as $c) {
    if (!$c['ok']) {
        $allOk = false;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mali · ตรวจการติดตั้ง</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:720px">
    <h1 class="h3 mb-1">ตรวจการติดตั้ง Mali</h1>
    <p class="text-muted">ลบไฟล์ <code>setup-check.php</code> หลังติดตั้งสำเร็จ</p>
    <div class="card shadow-sm">
        <ul class="list-group list-group-flush">
            <?php foreach ($checks as $c): ?>
                <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-semibold"><?= htmlspecialchars($c['label']) ?></div>
                        <div class="small text-muted"><?= htmlspecialchars($c['detail']) ?></div>
                    </div>
                    <span class="badge text-bg-<?= $c['ok'] ? 'success' : 'danger' ?>">
                        <?= $c['ok'] ? 'ผ่าน' : 'ไม่ผ่าน' ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="alert alert-<?= $allOk ? 'success' : 'warning' ?> mt-3">
        <?php if ($allOk): ?>
            พร้อมใช้งาน — ไปที่ <a href="index.php">หน้าเข้าสู่ระบบ</a>
            (admin / admin123)
        <?php else: ?>
            ยังติดตั้งไม่ครบ ตามรายการด้านบน แล้วอ่าน <code>INSTALL.txt</code>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
