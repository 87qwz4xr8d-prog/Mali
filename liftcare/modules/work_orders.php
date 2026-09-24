<?php

declare(strict_types=1);

$db = Database::conn();
$status = (string) get('status', '');

$sql = "SELECT w.*, v.asset_code, v.brand, v.model, u.full_name AS tech_name
        FROM work_orders w
        JOIN vehicles v ON v.id = w.vehicle_id
        LEFT JOIN users u ON u.id = w.assigned_to
        WHERE 1=1";
$types = '';
$params = [];
if ($status !== '' && in_array($status, ['open','assigned','in_progress','waiting_parts','completed','cancelled'], true)) {
    $sql .= ' AND w.status = ?';
    $types .= 's';
    $params[] = $status;
}
$sql .= " ORDER BY FIELD(w.priority,'urgent','high','medium','low'), w.id DESC";

$stmt = $db->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = 'ใบงานซ่อมบำรุง';
$activePage = 'work_orders';
require dirname(__DIR__) . '/includes/layout/header.php';
?>

<div class="page-toolbar">
    <p class="page-lead mb-0">Job Request / Work Order — PM · CM · ตรวจสภาพ</p>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= e(page_url('work_orders')) ?>" class="btn btn-sm <?= $status===''?'btn-steel':'btn-outline-secondary' ?>">ทั้งหมด</a>
        <?php foreach (['open'=>'เปิด','in_progress'=>'กำลังทำ','waiting_parts'=>'รออะไหล่','completed'=>'เสร็จ'] as $k=>$lab): ?>
            <a href="<?= e(page_url('work_orders', ['status'=>$k])) ?>" class="btn btn-sm <?= $status===$k?'btn-steel':'btn-outline-secondary' ?>"><?= e($lab) ?></a>
        <?php endforeach; ?>
        <a href="<?= e(page_url('work_order_form')) ?>" class="btn btn-accent btn-sm"><i class="fa-solid fa-plus"></i> เปิดใบงาน</a>
    </div>
</div>

<div class="panel">
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-striped datatable w-100">
                <thead>
                <tr>
                    <th>เลขที่</th>
                    <th>ประเภท</th>
                    <th>รถ</th>
                    <th>หัวข้อ</th>
                    <th>ความสำคัญ</th>
                    <th>ช่าง</th>
                    <th>สถานะ</th>
                    <th>กำหนด</th>
                    <th data-orderable="false"></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><strong><?= e($r['wo_no']) ?></strong></td>
                        <td><?= e(wo_type_label($r['wo_type'])) ?></td>
                        <td><?= e($r['asset_code']) ?><br><small class="text-muted"><?= e($r['brand'].' '.$r['model']) ?></small></td>
                        <td><?= e($r['title']) ?></td>
                        <td><?= priority_badge($r['priority']) ?></td>
                        <td><?= e($r['tech_name'] ?: '-') ?></td>
                        <td><?= wo_status_badge($r['status']) ?></td>
                        <td><?= e(th_date($r['due_date'])) ?></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-steel" href="<?= e(page_url('work_order_form', ['id'=>$r['id']])) ?>"><i class="fa-solid fa-pen"></i></a>
                            <?php if (Auth::canManage()): ?>
                                <a class="btn btn-sm btn-outline-danger" href="<?= e(page_url('work_order_delete', ['id'=>$r['id']])) ?>" data-confirm="ยืนยันลบใบงาน <?= e($r['wo_no']) ?>?"><i class="fa-solid fa-trash"></i></a>
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
