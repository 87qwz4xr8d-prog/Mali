<?php

declare(strict_types=1);

$db = Database::conn();

$stats = [
    'vehicles' => (int) $db->query("SELECT COUNT(*) c FROM vehicles WHERE status <> 'retired'")->fetch_assoc()['c'],
    'ready' => (int) $db->query("SELECT COUNT(*) c FROM vehicles WHERE status = 'ready'")->fetch_assoc()['c'],
    'open_wo' => (int) $db->query("SELECT COUNT(*) c FROM work_orders WHERE status IN ('open','assigned','in_progress','waiting_parts')")->fetch_assoc()['c'],
    'overdue_pm' => (int) $db->query("SELECT COUNT(*) c FROM vehicles WHERE next_pm_date IS NOT NULL AND next_pm_date < CURDATE() AND status <> 'retired'")->fetch_assoc()['c'],
];

$recentWo = $db->query(
    "SELECT w.*, v.asset_code, v.brand, v.model, u.full_name AS tech_name
     FROM work_orders w
     JOIN vehicles v ON v.id = w.vehicle_id
     LEFT JOIN users u ON u.id = w.assigned_to
     ORDER BY w.id DESC LIMIT 8"
)->fetch_all(MYSQLI_ASSOC);

$pmDue = $db->query(
    "SELECT asset_code, brand, model, next_pm_date, next_pm_hours, hour_meter, status
     FROM vehicles
     WHERE status <> 'retired'
       AND (
         (next_pm_date IS NOT NULL AND next_pm_date <= DATE_ADD(CURDATE(), INTERVAL 14 DAY))
         OR (next_pm_hours IS NOT NULL AND hour_meter >= next_pm_hours - 50)
       )
     ORDER BY next_pm_date ASC
     LIMIT 8"
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'แดชบอร์ด';
$activePage = 'dashboard';
require dirname(__DIR__) . '/includes/layout/header.php';
?>

<div class="stat-grid">
    <div class="stat-tile accent-steel">
        <div class="label">รถกระเช้าทั้งหมด</div>
        <div class="value"><?= $stats['vehicles'] ?></div>
        <div class="hint">ไม่รวมปลดระวาง</div>
    </div>
    <div class="stat-tile accent-ok">
        <div class="label">พร้อมใช้งาน</div>
        <div class="value"><?= $stats['ready'] ?></div>
        <div class="hint">สถานะ Ready</div>
    </div>
    <div class="stat-tile accent-orange">
        <div class="label">ใบงานค้าง</div>
        <div class="value"><?= $stats['open_wo'] ?></div>
        <div class="hint">ยังไม่เสร็จสิ้น</div>
    </div>
    <div class="stat-tile accent-warn">
        <div class="label">PM ค้างกำหนด</div>
        <div class="value"><?= $stats['overdue_pm'] ?></div>
        <div class="hint">ควรจัดคิวทันที</div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-header">
                <h2><i class="fa-solid fa-clipboard-list me-2 text-secondary"></i>ใบงานล่าสุด</h2>
                <a href="<?= e(page_url('work_orders')) ?>" class="btn btn-sm btn-outline-steel">ดูทั้งหมด</a>
            </div>
            <div class="panel-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>เลขที่</th>
                            <th>รถ</th>
                            <th>หัวข้อ</th>
                            <th>สถานะ</th>
                            <th>กำหนด</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$recentWo): ?>
                            <tr><td colspan="5" class="empty-state">ยังไม่มีใบงาน</td></tr>
                        <?php else: foreach ($recentWo as $row): ?>
                            <tr>
                                <td>
                                    <a href="<?= e(page_url('work_order_form', ['id' => $row['id']])) ?>">
                                        <?= e($row['wo_no']) ?>
                                    </a>
                                </td>
                                <td><?= e($row['asset_code']) ?></td>
                                <td>
                                    <div><?= e($row['title']) ?></div>
                                    <small class="text-muted"><?= e(wo_type_label($row['wo_type'])) ?></small>
                                </td>
                                <td><?= wo_status_badge($row['status']) ?></td>
                                <td><?= e(th_date($row['due_date'])) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-header">
                <h2><i class="fa-solid fa-calendar-check me-2 text-secondary"></i>ใกล้ถึงกำหนด PM</h2>
                <a href="<?= e(page_url('vehicles')) ?>" class="btn btn-sm btn-outline-steel">ทะเบียนรถ</a>
            </div>
            <div class="panel-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>PM ถัดไป</th>
                            <th>ชม.</th>
                            <th>สถานะ</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$pmDue): ?>
                            <tr><td colspan="4" class="empty-state">ไม่มีการแจ้งเตือนใน 14 วัน</td></tr>
                        <?php else: foreach ($pmDue as $row): ?>
                            <tr>
                                <td>
                                    <strong><?= e($row['asset_code']) ?></strong><br>
                                    <small class="text-muted"><?= e($row['brand'] . ' ' . $row['model']) ?></small>
                                </td>
                                <td><?= e(th_date($row['next_pm_date'])) ?></td>
                                <td><?= e((string) $row['hour_meter']) ?>/<?= e((string) ($row['next_pm_hours'] ?? '-')) ?></td>
                                <td><?= vehicle_status_badge($row['status']) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/layout/footer.php'; ?>
