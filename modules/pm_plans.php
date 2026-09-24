<?php

declare(strict_types=1);

$db = Database::conn();
$rows = $db->query(
    "SELECT p.*,
            (SELECT COUNT(*) FROM pm_plan_items i WHERE i.pm_plan_id = p.id) AS item_count
     FROM pm_plans p
     ORDER BY p.code"
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'แผนบำรุงรักษา PM';
$activePage = 'pm_plans';
require dirname(__DIR__) . '/includes/layout/header.php';
?>

<div class="page-toolbar">
    <p class="page-lead mb-0">กำหนดรอบบำรุงรักษาเชิงป้องกัน ตามวัน/ชั่วโมงใช้งาน</p>
    <?php if (Auth::canManage()): ?>
        <a href="<?= e(page_url('pm_plan_form')) ?>" class="btn btn-accent btn-sm"><i class="fa-solid fa-plus"></i> เพิ่มแผน</a>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-striped datatable w-100">
                <thead>
                <tr>
                    <th>รหัส</th>
                    <th>ชื่อแผน</th>
                    <th>ประเภทเครื่อง</th>
                    <th>ทุก (วัน)</th>
                    <th>ทุก (ชม.)</th>
                    <th>รายการตรวจ</th>
                    <th>สถานะ</th>
                    <th data-orderable="false"></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><strong><?= e($r['code']) ?></strong></td>
                        <td><?= e($r['name']) ?></td>
                        <td><?= e(lift_type_label($r['lift_type'])) ?></td>
                        <td><?= e((string)($r['interval_days'] ?? '-')) ?></td>
                        <td><?= e((string)($r['interval_hours'] ?? '-')) ?></td>
                        <td><?= (int)$r['item_count'] ?> รายการ</td>
                        <td><?= (int)$r['is_active'] ? '<span class="badge text-bg-success">ใช้งาน</span>' : '<span class="badge text-bg-secondary">ปิด</span>' ?></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-steel" href="<?= e(page_url('pm_plan_form', ['id'=>$r['id']])) ?>"><i class="fa-solid fa-pen"></i></a>
                            <?php if (Auth::canManage()): ?>
                                <a class="btn btn-sm btn-outline-danger" href="<?= e(page_url('pm_plan_delete', ['id'=>$r['id']])) ?>" data-confirm="ยืนยันลบแผน <?= e($r['code']) ?>?"><i class="fa-solid fa-trash"></i></a>
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
