<?php
/** @var string $activePage */
$appName = setting('app_name', 'LiftCare');
$appSub = setting('app_subtitle', 'ระบบบำรุงรักษารถกระเช้าไฟฟ้า');
$menus = [
    ['page' => 'dashboard', 'label' => 'แดชบอร์ด', 'icon' => 'fa-gauge-high'],
    ['page' => 'vehicles', 'label' => 'ทะเบียนรถกระเช้า', 'icon' => 'fa-truck-ramp-box'],
    ['page' => 'work_orders', 'label' => 'ใบงานซ่อมบำรุง', 'icon' => 'fa-clipboard-check'],
    ['page' => 'pm_plans', 'label' => 'แผน PM', 'icon' => 'fa-calendar-check'],
    ['page' => 'parts', 'label' => 'อะไหล่', 'icon' => 'fa-boxes-stacked'],
    ['page' => 'customers', 'label' => 'ลูกค้า / ไซต์งาน', 'icon' => 'fa-building'],
    ['page' => 'reports', 'label' => 'รายงาน', 'icon' => 'fa-chart-column'],
];
if (Auth::isAdmin() || Auth::canManage()) {
    $menus[] = ['page' => 'users', 'label' => 'ผู้ใช้งาน', 'icon' => 'fa-users-gear'];
}
$menus[] = ['page' => 'settings', 'label' => 'ตั้งค่า', 'icon' => 'fa-sliders'];
?>
<!-- ===== SIDEBAR ===== -->
<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <div class="brand-mark" aria-hidden="true">
            <i class="fa-solid fa-elevator"></i>
        </div>
        <div class="brand-text">
            <span class="brand-name"><?= e($appName) ?></span>
            <span class="brand-tag"><?= e($appSub) ?></span>
        </div>
    </div>

    <nav class="sidebar-nav" aria-label="เมนูหลัก">
        <div class="nav-section-label">เมนูหลัก</div>
        <?php foreach ($menus as $item): ?>
            <a class="sidebar-link <?= is_active_page($item['page'], $activePage) ?>"
               href="<?= e(page_url($item['page'])) ?>">
                <i class="fa-solid <?= e($item['icon']) ?>"></i>
                <span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer-card">
        <div class="mini-stat">
            <i class="fa-solid fa-bolt"></i>
            <div>
                <strong>Electric Fleet</strong>
                <small>บำรุงรักษาครบวงจร</small>
            </div>
        </div>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
