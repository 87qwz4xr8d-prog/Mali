<?php

declare(strict_types=1);

$db = Database::conn();
$status = (string) get('status', '');

$sql = "SELECT v.*, c.name AS customer_name
        FROM vehicles v
        LEFT JOIN customers c ON c.id = v.customer_id
        WHERE 1=1";
$types = '';
$params = [];

if ($status !== '' && in_array($status, ['ready','in_use','maintenance','repair','retired'], true)) {
    $sql .= ' AND v.status = ?';
    $types .= 's';
    $params[] = $status;
}
$sql .= ' ORDER BY v.asset_code ASC';

$stmt = $db->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = 'ทะเบียนรถกระเช้า';
$activePage = 'vehicles';
require dirname(__DIR__) . '/includes/layout/header.php';
?>

<div class="page-toolbar">
    <div>
        <p class="page-lead mb-0">จัดการข้อมูลรถกระเช้าไฟฟ้า / บูมลิฟต์ / กรรไกร</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= e(page_url('vehicles')) ?>" class="btn btn-sm <?= $status === '' ? 'btn-steel' : 'btn-outline-secondary' ?>">ทั้งหมด</a>
        <?php foreach (['ready'=>'พร้อมใช้','in_use'=>'ใช้งาน','maintenance'=>'บำรุง','repair'=>'ซ่อม'] as $k=>$lab): ?>
            <a href="<?= e(page_url('vehicles', ['status' => $k])) ?>"
               class="btn btn-sm <?= $status === $k ? 'btn-steel' : 'btn-outline-secondary' ?>"><?= e($lab) ?></a>
        <?php endforeach; ?>
        <?php if (Auth::canManage()): ?>
            <a href="<?= e(page_url('vehicle_form')) ?>" class="btn btn-accent btn-sm">
                <i class="fa-solid fa-plus"></i> เพิ่มรถ
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="panel">
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-striped datatable w-100">
                <thead>
                <tr>
                    <th>รหัส</th>
                    <th>รุ่น</th>
                    <th>ประเภท</th>
                    <th>ชม.ใช้งาน</th>
                    <th>PM ถัดไป</th>
                    <th>ลูกค้า/ไซต์</th>
                    <th>สถานะ</th>
                    <th data-orderable="false">จัดการ</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <strong><?= e($r['asset_code']) ?></strong>
                            <?php if ($r['plate_no']): ?><br><small class="text-muted"><?= e($r['plate_no']) ?></small><?php endif; ?>
                        </td>
                        <td><?= e($r['brand'] . ' ' . $r['model']) ?></td>
                        <td><?= e(lift_type_label($r['lift_type'])) ?> · ไฟฟ้า</td>
                        <td><?= e(number_format((float) $r['hour_meter'], 1)) ?></td>
                        <td><?= e(th_date($r['next_pm_date'])) ?></td>
                        <td><?= e($r['customer_name'] ?: ($r['location'] ?: '-')) ?></td>
                        <td><?= vehicle_status_badge($r['status']) ?></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-steel" href="<?= e(page_url('vehicle_form', ['id' => $r['id']])) ?>">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <?php if (Auth::canManage()): ?>
                                <a class="btn btn-sm btn-outline-danger"
                                   href="<?= e(page_url('vehicle_delete', ['id' => $r['id']])) ?>"
                                   data-confirm="ยืนยันลบรถ <?= e($r['asset_code']) ?>?">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/layout/footer.php'; ?>
