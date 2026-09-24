<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (Auth::check()) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) post('username', ''));
    $password = (string) post('password', '');

    if ($username === '' || $password === '') {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } elseif (Auth::attempt($username, $password)) {
        flash('success', 'เข้าสู่ระบบสำเร็จ');
        redirect('index.php');
    } else {
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}

$appName = 'LiftCare';
try {
    $appName = setting('app_name', 'LiftCare');
} catch (Throwable $e) {
    // DB ยังไม่พร้อม
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>เข้าสู่ระบบ | <?= e($appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="auth-shell">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="brand-mark"><i class="fa-solid fa-elevator"></i></div>
            <div>
                <h1><?= e($appName) ?></h1>
                <p>ระบบบำรุงรักษารถกระเช้าไฟฟ้า</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="mb-3">
                <label class="form-label" for="username">ชื่อผู้ใช้</label>
                <input type="text" class="form-control" id="username" name="username" required autofocus
                       value="<?= e((string) post('username', '')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">รหัสผ่าน</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-steel w-100 py-2">
                <i class="fa-solid fa-right-to-bracket me-1"></i> เข้าสู่ระบบ
            </button>
        </form>

        <div class="auth-hint">
            <strong>บัญชีทดสอบ:</strong> admin / admin123<br>
            นำเข้า <code>sql/schema.sql</code> ผ่าน phpMyAdmin ก่อนใช้งาน
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
