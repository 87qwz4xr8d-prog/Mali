<?php

declare(strict_types=1);

$db = Database::conn();
$rows = $db->query('SELECT * FROM customers ORDER BY code')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'ลูกค้า / ไซต์งาน';
$activePage = 'customers';
require dirname(__DIR__) . '/includes/layout/header.php';
?>

<div class="page-toolbar">
    <p class="page-lead mb-0">ข้อมูลลูกค้าเช่า/ไซต์ที่วางรถกระเช้า</p>
    <?php if (Auth::canManage()): ?>
        <a href="<?= e(page_url('customer_form')) ?>" class="btn btn-accent btn-sm"><i class="fa-solid fa-plus"></i> เพิ่มลูกค้า</a>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-striped datatable w-100">
                <thead>
                <tr>
                    <th>รหัส</th>
                    <th>ชื่อ</th>
                    <th>ผู้ติดต่อ</th>
                    <th>โทรศัพท์</th>
                    <th>อีเมล</th>
                    <th>สถานะ</th>
                    <th data-orderable="false"></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><strong><?= e($r['code']) ?></strong></td>
                        <td><?= e($r['name']) ?></td>
                        <td><?= e($r['contact_name'] ?: '-') ?></td>
                        <td><?= e($r['phone'] ?: '-') ?></td>
                        <td><?= e($r['email'] ?: '-') ?></td>
                        <td><?= (int)$r['is_active'] ? '<span class="badge text-bg-success">ใช้งาน</span>' : '<span class="badge text-bg-secondary">ปิด</span>' ?></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-steel" href="<?= e(page_url('customer_form', ['id'=>$r['id']])) ?>"><i class="fa-solid fa-pen"></i></a>
                            <?php if (Auth::canManage()): ?>
                                <a class="btn btn-sm btn-outline-danger" href="<?= e(page_url('customer_delete', ['id'=>$r['id']])) ?>" data-confirm="ยืนยันลบ <?= e($r['code']) ?>?"><i class="fa-solid fa-trash"></i></a>
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
