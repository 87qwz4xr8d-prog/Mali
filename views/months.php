<?php
/** @var list $months */
/** @var BudgetService $budget */
ob_start();
?>
<h2 class="page-title">ประวัติเดือนงบประมาณ</h2>
<p class="page-sub">ดูสรุปเดือนที่เปิด/ปิด และเริ่มเปิดเดือนใหม่</p>

<div class="mb-3">
    <a href="index.php?page=wizard" class="btn btn-mali"><i class="fa-solid fa-plus me-1"></i> เปิด/จัดการเดือนปัจจุบัน</a>
</div>

<div class="panel">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <th>เดือน</th>
                <th>สถานะ</th>
                <th class="text-end">ยอดยกมา</th>
                <th class="text-end">เงินเดือน</th>
                <th class="text-end">ยอดใช้ได้</th>
                <th class="text-end">ออม</th>
                <th class="text-end">ยกไป</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$months): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">ยังไม่มีข้อมูลเดือน</td></tr>
            <?php endif; ?>
            <?php foreach ($months as $m): ?>
                <tr>
                    <td><strong><?= e(thai_month($m['budget_ym'])) ?></strong><div class="small text-muted"><?= e($m['budget_ym']) ?></div></td>
                    <td><span class="badge text-bg-<?= status_badge($m['status']) ?>"><?= e(status_label($m['status'])) ?></span></td>
                    <td class="text-end"><?= money($m['opening_balance']) ?></td>
                    <td class="text-end"><?= money($m['salary_amount']) ?></td>
                    <td class="text-end"><?= money($m['available_total']) ?></td>
                    <td class="text-end"><?= money($m['savings_amount']) ?></td>
                    <td class="text-end"><?= money($m['carry_forward']) ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="index.php?page=transactions&month_id=<?= (int) $m['id'] ?>">รายการ</a>
                        <?php if ($m['status'] === 'draft'): ?>
                            <a class="btn btn-sm btn-mali" href="index.php?page=wizard">ทำต่อ</a>
                        <?php elseif ($m['status'] === 'active'): ?>
                            <form method="post" action="index.php?page=months&action=close" id="close-<?= (int)$m['id'] ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="month_id" value="<?= (int) $m['id'] ?>">
                                <button type="button" class="btn btn-sm btn-outline-dark"
                                        data-confirm="ปิดเดือนนี้?" data-form="close-<?= (int)$m['id'] ?>">ปิดเดือน</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = 'ประวัติเดือน';
$active = 'months';
require base_path('views/layout.php');
