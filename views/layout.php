<?php

declare(strict_types=1);

$showNav = $showNav ?? false;
$activeNav = $activeNav ?? '';
$pageScript = $pageScript ?? '';
$bootSwal = $bootSwal ?? null;
$user = $user ?? null;
$appName = $appName ?? 'ปฏิทินกลางบริษัท';
$title = $title ?? $appName;
$allowedScripts = ['/assets/js/calendar.js', '/assets/js/admin.js'];
if (!in_array($pageScript, $allowedScripts, true)) {
    $pageScript = '';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="csrf-token" content="<?= e($csrf ?? '') ?>">
    <title><?= e($title) ?> · <?= e($appName) ?></title>
    <link rel="stylesheet" href="/assets/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php if ($showNav && is_array($user)): ?>
    <header class="app-header">
        <div class="container-xl d-flex flex-wrap align-items-center gap-2 py-2">
            <a class="app-brand" href="/calendar"><?= e($appName) ?></a>
            <nav class="nav app-nav">
                <a class="nav-link<?= $activeNav === 'calendar' ? ' active' : '' ?>" href="/calendar">ปฏิทิน</a>
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                    <a class="nav-link<?= $activeNav === 'users' ? ' active' : '' ?>" href="/admin/users">ผู้ใช้</a>
                    <a class="nav-link<?= $activeNav === 'departments' ? ' active' : '' ?>" href="/admin/departments">แผนก</a>
                <?php endif; ?>
            </nav>
            <div class="ms-auto d-flex align-items-center gap-2">
                <div class="user-chip">
                    <strong><?= e($user['full_name'] ?? '') ?></strong>
                    <span><?= e($user['department_name'] ?? '') ?></span>
                </div>
                <form method="post" action="/logout" class="m-0">
                    <input type="hidden" name="_csrf" value="<?= e($csrf ?? '') ?>">
                    <button type="submit" class="btn btn-sm btn-outline-light">ออกจากระบบ</button>
                </form>
            </div>
        </div>
    </header>
<?php endif; ?>
<main class="container-xl py-4">
    <?php require $contentView; ?>
</main>
<?php if (is_array($bootSwal)): ?>
    <script id="boot-swal" type="application/json"><?= \App\Http::jsonScript($bootSwal) ?></script>
<?php endif; ?>
<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script src="/assets/vendor/sweetalert2.all.min.js"></script>
<script src="/assets/js/app.js"></script>
<?php if ($pageScript !== ''): ?>
    <script src="<?= e($pageScript) ?>"></script>
<?php endif; ?>
</body>
</html>
