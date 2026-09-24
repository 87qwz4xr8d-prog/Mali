<?php

declare(strict_types=1);

Auth::requireRole(['admin', 'manager']);
$db = Database::conn();
$rows = $db->query('SELECT id, username, full_name, email, phone, role, is_active, created_at FROM users ORDER BY id')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'ผู้ใช้งาน';
$activePage = 'users';
require dirname(__DIR__) . '/includes/layout/header.php';
?>

<div class="page-toolbar">
    <p class="page-lead mb-0">จัดการบัญชีเข้าใช้ระบบ</p>
    <?php if (Auth::isAdmin()): ?>
        <a href="<?= e(page_url('user_form')) ?>" class="btn btn-accent btn-sm"><i class="fa-solid fa-plus"></i> เพิ่มผู้ใช้</a>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-striped datatable w-100">
                <thead>
                <tr>
                    <th>ชื่อผู้ใช้</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>บทบาท</th>
                    <th>โทรศัพท์</th>
                    <th>สถานะ</th>
                    <th>สร้างเมื่อ</th>
                    <th data-orderable="false"></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><strong><?= e($r['username']) ?></strong></td>
                        <td><?= e($r['full_name']) ?></td>
                        <td><?= e(role_label($r['role'])) ?></td>
                        <td><?= e($r['phone'] ?: '-') ?></td>
                        <td><?= (int)$r['is_active'] ? '<span class="badge text-bg-success">ใช้งาน</span>' : '<span class="badge text-bg-secondary">ระงับ</span>' ?></td>
                        <td><?= e(th_datetime($r['created_at'])) ?></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-steel" href="<?= e(page_url('user_form', ['id'=>$r['id']])) ?>"><i class="fa-solid fa-pen"></i></a>
                            <?php if (Auth::isAdmin() && (int)$r['id'] !== (int)Auth::user()['id']): ?>
                                <a class="btn btn-sm btn-outline-danger" href="<?= e(page_url('user_delete', ['id'=>$r['id']])) ?>" data-confirm="ยืนยันลบผู้ใช้ <?= e($r['username']) ?>?"><i class="fa-solid fa-trash"></i></a>
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
