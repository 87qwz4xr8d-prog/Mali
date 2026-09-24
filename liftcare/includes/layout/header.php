<?php
/** @var string $pageTitle */
/** @var string $activePage */
/** @var array|null $currentUser */

$appName = setting('app_name', 'LiftCare');
$appSub = setting('app_subtitle', 'ระบบบำรุงรักษารถกระเช้าไฟฟ้า');
$pageTitle = $pageTitle ?? $appName;
$activePage = $activePage ?? '';
$currentUser = Auth::user();
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e($appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="<?= e($bodyClass) ?>">
<?php if ($currentUser): ?>
<div class="app-wrapper" id="appWrapper">
    <?php require __DIR__ . '/sidebar.php'; ?>

    <div class="app-main">
        <!-- ===== HEADER ===== -->
        <header class="app-header">
            <div class="header-left">
                <button type="button" class="btn btn-icon" id="sidebarToggle" aria-label="สลับเมนู">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="header-title-wrap">
                    <h1 class="header-title"><?= e($pageTitle) ?></h1>
                    <p class="header-sub d-none d-md-block"><?= e($appSub) ?></p>
                </div>
            </div>
            <div class="header-right">
                <a href="<?= e(page_url('work_orders', ['status' => 'open'])) ?>" class="btn btn-sm btn-outline-steel">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <span class="d-none d-sm-inline">ใบงานเปิด</span>
                </a>
                <div class="dropdown">
                    <button class="btn user-chip dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="user-avatar"><?= e(mb_substr($currentUser['full_name'], 0, 1)) ?></span>
                        <span class="user-meta d-none d-md-inline">
                            <strong><?= e($currentUser['full_name']) ?></strong>
                            <small><?= e(role_label($currentUser['role'])) ?></small>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="<?= e(page_url('settings')) ?>"><i class="fa-solid fa-gear me-2"></i>ตั้งค่า</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php" data-confirm="ต้องการออกจากระบบหรือไม่?"><i class="fa-solid fa-right-from-bracket me-2"></i>ออกจากระบบ</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- ===== CONTENT ===== -->
        <main class="app-content">
<?php else: ?>
<div class="auth-shell">
<?php endif; ?>
