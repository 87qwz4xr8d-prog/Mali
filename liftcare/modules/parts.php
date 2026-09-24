<?php

declare(strict_types=1);

$db = Database::conn();
$rows = $db->query('SELECT * FROM spare_parts ORDER BY sku')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'อะไหล่';
$activePage = 'parts';
require dirname(__DIR__) . '/includes/layout/header.php';
?>

<div class="page-toolbar">
    <p class="page-lead mb-0">คลังอะไหล่รถกระเช้า — ติดตามจุดสั่งซื้อ</p>
    <?php if (Auth::canManage()): ?>
        <a href="<?= e(page_url('part_form')) ?>" class="btn btn-accent btn-sm"><i class="fa-solid fa-plus"></i> เพิ่มอะไหล่</a>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-striped datatable w-100">
                <thead>
                <tr>
                    <th>SKU</th>
                    <th>ชื่อ</th>
                    <th>หมวด</th>
                    <th>คงเหลือ</th>
                    <th>จุดสั่งซื้อ</th>
                    <th>ต้นทุน/หน่วย</th>
                    <th>ที่เก็บ</th>
                    <th data-orderable="false"></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <?php $low = (float)$r['qty_on_hand'] <= (float)$r['reorder_level']; ?>
                    <tr>
                        <td><strong><?= e($r['sku']) ?></strong></td>
                        <td><?= e($r['name']) ?></td>
                        <td><?= e($r['category'] ?: '-') ?></td>
                        <td>
                            <?= e(number_format((float)$r['qty_on_hand'], 2)) ?> <?= e($r['unit']) ?>
                            <?php if ($low): ?><span class="badge text-bg-danger ms-1">ต่ำ</span><?php endif; ?>
                        </td>
                        <td><?= e(number_format((float)$r['reorder_level'], 2)) ?></td>
                        <td><?= e(money($r['unit_cost'])) ?></td>
                        <td><?= e($r['location'] ?: '-') ?></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-steel" href="<?= e(page_url('part_form', ['id'=>$r['id']])) ?>"><i class="fa-solid fa-pen"></i></a>
                            <?php if (Auth::canManage()): ?>
                                <a class="btn btn-sm btn-outline-danger" href="<?= e(page_url('part_delete', ['id'=>$r['id']])) ?>" data-confirm="ยืนยันลบ <?= e($r['sku']) ?>?"><i class="fa-solid fa-trash"></i></a>
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
