<?php
/** @var string $content */
/** @var string $title */
/** @var string $active */
$user = Auth::user();
$flash = take_flash();
$appName = (new BudgetService())->getSetting('app_name', 'Mali');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Mali') ?> | <?= e($appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
<?php if ($user): ?>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-mark">M</div>
            <div>
                <h1><?= e($appName) ?></h1>
                <small>ควบคุมรายจ่ายเงินเดือน</small>
            </div>
        </div>
        <nav>
            <a class="nav-link <?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>" href="index.php?page=dashboard">
                <i class="fa-solid fa-gauge-high"></i> แดชบอร์ด
            </a>
            <a class="nav-link <?= ($active ?? '') === 'wizard' ? 'active' : '' ?>" href="index.php?page=wizard">
                <i class="fa-solid fa-list-check"></i> เปิดเดือน (Step)
            </a>
            <a class="nav-link <?= ($active ?? '') === 'transactions' ? 'active' : '' ?>" href="index.php?page=transactions">
                <i class="fa-solid fa-book"></i> รายรับ-รายจ่าย
            </a>
            <a class="nav-link <?= ($active ?? '') === 'loans' ? 'active' : '' ?>" href="index.php?page=loans">
                <i class="fa-solid fa-building-columns"></i> สินเชื่อ
            </a>
            <a class="nav-link <?= ($active ?? '') === 'months' ? 'active' : '' ?>" href="index.php?page=months">
                <i class="fa-solid fa-calendar-days"></i> ประวัติเดือน
            </a>
            <a class="nav-link <?= ($active ?? '') === 'reports' ? 'active' : '' ?>" href="index.php?page=reports">
                <i class="fa-solid fa-chart-pie"></i> รายงาน
            </a>
            <a class="nav-link <?= ($active ?? '') === 'settings' ? 'active' : '' ?>" href="index.php?page=settings">
                <i class="fa-solid fa-gear"></i> ตั้งค่า
            </a>
            <a class="nav-link" href="index.php?page=logout" data-confirm="ต้องการออกจากระบบหรือไม่?">
                <i class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ
            </a>
        </nav>
        <div class="mt-4 px-2 small text-white-50">
            สวัสดี, <?= e($user['display_name']) ?>
        </div>
    </aside>
    <main class="main">
        <?= $content ?>
    </main>
</div>
<?php else: ?>
    <?= $content ?>
<?php endif; ?>

<?php if ($flash): ?>
<div id="flash-data" data-type="<?= e($flash['type']) ?>" data-message="<?= e($flash['message']) ?>" hidden></div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
