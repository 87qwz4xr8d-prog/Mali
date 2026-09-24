<?php

declare(strict_types=1);

$db = Database::conn();

$byStatus = $db->query(
    "SELECT status, COUNT(*) AS cnt FROM work_orders GROUP BY status ORDER BY cnt DESC"
)->fetch_all(MYSQLI_ASSOC);

$byType = $db->query(
    "SELECT wo_type, COUNT(*) AS cnt,
            SUM(labor_cost + parts_cost + other_cost) AS total_cost
     FROM work_orders
     GROUP BY wo_type"
)->fetch_all(MYSQLI_ASSOC);

$costMonth = $db->query(
    "SELECT DATE_FORMAT(COALESCE(completed_at, created_at), '%Y-%m') AS ym,
            COUNT(*) AS cnt,
            SUM(labor_cost + parts_cost + other_cost) AS total_cost
     FROM work_orders
     WHERE status = 'completed'
     GROUP BY ym
     ORDER BY ym DESC
     LIMIT 6"
)->fetch_all(MYSQLI_ASSOC);

$fleet = $db->query(
    "SELECT status, COUNT(*) AS cnt FROM vehicles GROUP BY status"
)->fetch_all(MYSQLI_ASSOC);

$lowParts = $db->query(
    "SELECT sku, name, qty_on_hand, reorder_level, unit
     FROM spare_parts
     WHERE qty_on_hand <= reorder_level AND is_active = 1
     ORDER BY qty_on_hand ASC"
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'รายงาน';
$activePage = 'reports';
require dirname(__DIR__) . '/includes/layout/header.php';
?>

<div class="page-toolbar">
    <p class="page-lead mb-0">สรุปสถานะฟลีท ใบงาน และอะไหล่</p>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header"><h2>ใบงานตามสถานะ</h2></div>
            <div class="panel-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>สถานะ</th><th class="text-end">จำนวน</th></tr></thead>
                    <tbody>
                    <?php foreach ($byStatus as $r): ?>
                        <tr>
                            <td><?= wo_status_badge($r['status']) ?></td>
                            <td class="text-end fw-bold"><?= (int)$r['cnt'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$byStatus): ?><tr><td colspan="2" class="empty-state">ไม่มีข้อมูล</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header"><h2>ใบงานตามประเภท + ค่าใช้จ่าย</h2></div>
            <div class="panel-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>ประเภท</th><th class="text-end">จำนวน</th><th class="text-end">รวมค่าใช้จ่าย</th></tr></thead>
                    <tbody>
                    <?php foreach ($byType as $r): ?>
                        <tr>
                            <td><?= e(wo_type_label($r['wo_type'])) ?></td>
                            <td class="text-end"><?= (int)$r['cnt'] ?></td>
                            <td class="text-end"><?= e(money($r['total_cost'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header"><h2>สถานะรถกระเช้า</h2></div>
            <div class="panel-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>สถานะ</th><th class="text-end">จำนวน</th></tr></thead>
                    <tbody>
                    <?php foreach ($fleet as $r): ?>
                        <tr>
                            <td><?= vehicle_status_badge($r['status']) ?></td>
                            <td class="text-end fw-bold"><?= (int)$r['cnt'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header"><h2>งานเสร็จสิ้นรายเดือน</h2></div>
            <div class="panel-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>เดือน</th><th class="text-end">ใบงาน</th><th class="text-end">ค่าใช้จ่าย</th></tr></thead>
                    <tbody>
                    <?php foreach ($costMonth as $r): ?>
                        <tr>
                            <td><?= e($r['ym']) ?></td>
                            <td class="text-end"><?= (int)$r['cnt'] ?></td>
                            <td class="text-end"><?= e(money($r['total_cost'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$costMonth): ?><tr><td colspan="3" class="empty-state">ยังไม่มีงานที่ปิด</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="panel">
            <div class="panel-header"><h2>อะไหล่ถึงจุดสั่งซื้อ</h2></div>
            <div class="panel-body p-0">
                <table class="table table-striped datatable w-100 mb-0">
                    <thead><tr><th>SKU</th><th>ชื่อ</th><th>คงเหลือ</th><th>จุดสั่งซื้อ</th></tr></thead>
                    <tbody>
                    <?php foreach ($lowParts as $r): ?>
                        <tr>
                            <td><?= e($r['sku']) ?></td>
                            <td><?= e($r['name']) ?></td>
                            <td class="text-danger fw-bold"><?= e(number_format((float)$r['qty_on_hand'], 2)) ?> <?= e($r['unit']) ?></td>
                            <td><?= e(number_format((float)$r['reorder_level'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (!$lowParts): ?><div class="empty-state">ไม่มีอะไหล่ต่ำกว่าจุดสั่งซื้อ</div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/layout/footer.php'; ?>
