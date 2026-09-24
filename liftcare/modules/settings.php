<?php

declare(strict_types=1);

Auth::requireRole(['admin', 'manager']);
$db = Database::conn();

$keys = ['app_name', 'app_subtitle', 'company_name', 'company_phone', 'wo_prefix'];
$values = [];
foreach ($keys as $k) {
    $values[$k] = setting($k, '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $stmt = $db->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    foreach ($keys as $k) {
        $val = trim((string) post($k, ''));
        $stmt->bind_param('ss', $k, $val);
        $stmt->execute();
    }
    $stmt->close();
    flash('success', 'บันทึกการตั้งค่าแล้ว');
    redirect(page_url('settings'));
}

$pageTitle = 'ตั้งค่า';
$activePage = 'settings';
require dirname(__DIR__) . '/includes/layout/header.php';
?>

<div class="page-toolbar">
    <p class="page-lead mb-0">ชื่อระบบ บริษัท และรูปแบบเลขที่ใบงาน</p>
</div>

<div class="panel">
    <div class="panel-body">
        <form method="post" data-confirm-submit="ยืนยันบันทึกการตั้งค่า?">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">ชื่อระบบ</label>
                    <input name="app_name" class="form-control" value="<?= e($values['app_name']) ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label">คำอธิบายสั้น</label>
                    <input name="app_subtitle" class="form-control" value="<?= e($values['app_subtitle']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">ชื่อบริษัท</label>
                    <input name="company_name" class="form-control" value="<?= e($values['company_name']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">โทรศัพท์</label>
                    <input name="company_phone" class="form-control" value="<?= e($values['company_phone']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">คำนำหน้าเลขใบงาน</label>
                    <input name="wo_prefix" class="form-control" value="<?= e($values['wo_prefix']) ?>">
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-accent"><i class="fa-solid fa-floppy-disk"></i> บันทึก</button>
            </div>
        </form>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/layout/footer.php'; ?>
