<?php
/** @var list $months */
/** @var array|null $month */
/** @var array|null $report */
/** @var BudgetService $budget */
ob_start();
$selectedId = $month ? (int) $month['id'] : 0;
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h2 class="page-title">รายงาน</h2>
        <p class="page-sub mb-0">สรุปรายรับ-รายจ่าย · ส่งออก PDF / Excel / PowerPoint · พิมพ์ได้</p>
    </div>
</div>

<div class="panel">
    <form method="get" action="index.php" class="row g-3 align-items-end">
        <input type="hidden" name="page" value="reports">
        <div class="col-md-5">
            <label class="form-label">เลือกเดือน</label>
            <select name="month_id" class="form-select" required>
                <?php if (!$months): ?>
                    <option value="">— ยังไม่มีเดือน —</option>
                <?php endif; ?>
                <?php foreach ($months as $m): ?>
                    <option value="<?= (int) $m['id'] ?>" <?= $selectedId === (int) $m['id'] ? 'selected' : '' ?>>
                        <?= e(thai_month($m['budget_ym'])) ?> (<?= e(status_label($m['status'])) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-mali w-100" type="submit">
                <i class="fa-solid fa-eye me-1"></i> ดูรายงาน
            </button>
        </div>
    </form>
</div>

<?php if ($report): ?>
    <div class="panel">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5 class="mb-1">เดือน<?= e($report['month_label']) ?></h5>
                <div class="text-muted small">สร้างตัวอย่าง · <?= e($report['generated_at']) ?></div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-danger"
                   href="index.php?page=reports&action=pdf&month_id=<?= $selectedId ?>">
                    <i class="fa-solid fa-file-pdf me-1"></i> PDF
                </a>
                <a class="btn btn-outline-success"
                   href="index.php?page=reports&action=excel&month_id=<?= $selectedId ?>">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </a>
                <a class="btn btn-outline-warning"
                   href="index.php?page=reports&action=pptx&month_id=<?= $selectedId ?>">
                    <i class="fa-solid fa-file-powerpoint me-1"></i> PowerPoint
                </a>
                <a class="btn btn-outline-secondary" target="_blank"
                   href="index.php?page=reports&action=print&month_id=<?= $selectedId ?>">
                    <i class="fa-solid fa-print me-1"></i> พิมพ์
                </a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="stat-card"><div class="label">ยอดใช้ได้</div><div class="value"><?= money($report['stats']['available']) ?></div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="label">รายรับรวม</div><div class="value text-success"><?= money($report['stats']['income']) ?></div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="label">รายจ่ายรวม</div><div class="value"><?= money($report['stats']['spent']) ?></div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="label">คงเหลือ</div><div class="value"><?= money($report['stats']['balance']) ?></div></div></div>
        </div>

        <div class="row g-3">
            <div class="col-lg-5">
                <h6 class="mb-2">สัดส่วนรายจ่าย</h6>
                <?php if (!$report['expense_breakdown']): ?>
                    <p class="text-muted">ไม่มีข้อมูล</p>
                <?php else: ?>
                    <table class="table table-sm">
                        <thead><tr><th>หมวด</th><th class="text-end">จำนวน</th></tr></thead>
                        <tbody>
                        <?php foreach ($report['expense_breakdown'] as $row): ?>
                            <tr>
                                <td><?= e($row['label']) ?></td>
                                <td class="text-end"><?= money($row['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="col-lg-7">
                <h6 class="mb-2">รายการล่าสุดในรายงาน</h6>
                <div class="table-responsive" style="max-height:320px;overflow:auto">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>วันที่</th><th>ประเภท</th><th>รายละเอียด</th><th class="text-end">จำนวน</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($report['transactions'], 0, 15) as $txn): ?>
                            <tr>
                                <td><?= e($txn['txn_date']) ?></td>
                                <td><?= $txn['type'] === 'income' ? 'รับ' : 'จ่าย' ?></td>
                                <td><?= e($txn['description']) ?></td>
                                <td class="text-end"><?= money($txn['amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($months): ?>
    <div class="alert alert-info">เลือกเดือนแล้วกดดูรายงาน เพื่อส่งออกไฟล์หรือพิมพ์</div>
<?php else: ?>
    <div class="alert alert-warning">ยังไม่มีเดือนงบประมาณ — ไปที่ <a href="index.php?page=wizard">เปิดเดือน</a> ก่อน</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
$title = 'รายงาน';
$active = 'reports';
require base_path('views/layout.php');
