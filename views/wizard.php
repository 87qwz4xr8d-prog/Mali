<?php
/** @var BudgetService $budget */
/** @var array $month */
/** @var int $step */
/** @var list $loans */
/** @var list $allocations */
/** @var array $stats */
ob_start();

$steps = [
    1 => 'ยืนยันยอดเปิดเดือน',
    2 => 'จัดสรรงบสินเชื่อ',
    3 => 'คุณแม่ + ส่วนตัว',
    4 => 'สรุปกรอบเดือน',
    5 => 'เริ่มใช้จ่าย',
];

$allocMap = [];
foreach ($allocations as $a) {
    if ($a['category'] === 'loan' && $a['loan_id']) {
        $allocMap[(int) $a['loan_id']] = (float) $a['planned_amount'];
    }
}
$motherAlloc = $budget->motherBudget();
$personalAlloc = $budget->personalBudget();
foreach ($allocations as $a) {
    if ($a['category'] === 'mother') {
        $motherAlloc = (float) $a['planned_amount'];
    }
    if ($a['category'] === 'personal') {
        $personalAlloc = (float) $a['planned_amount'];
    }
}
$remaining = $budget->remainingAfterPlan($month);
$projSave = $budget->projectedSavings($remaining);
$projCarry = max(0, $remaining - $projSave);
?>
<h2 class="page-title">เปิดเดือนแบบทีละขั้นตอน</h2>
<p class="page-sub">เดือน<?= e(thai_month($month['budget_ym'])) ?> · สถานะ <?= e(status_label($month['status'])) ?></p>

<div class="wizard-steps">
    <?php foreach ($steps as $n => $label): ?>
        <div class="wizard-step <?= $step === $n ? 'active' : ($step > $n ? 'done' : '') ?>">
            <div class="fw-bold">ขั้นที่ <?= $n ?></div>
            <div><?= e($label) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($month['status'] === 'active' || $step >= 5): ?>
    <div class="panel">
        <div class="alert alert-success mb-3">
            <i class="fa-solid fa-circle-check me-1"></i>
            เดือนนี้เปิดใช้งานแล้ว สามารถบันทึกรายรับ-รายจ่ายได้ทันที
        </div>
        <a href="index.php?page=dashboard" class="btn btn-mali">ไปที่แดชบอร์ด</a>
        <a href="index.php?page=transactions&action=create" class="btn btn-outline-success">บันทึกรายการ</a>
    </div>
<?php elseif ($step === 1): ?>
    <div class="panel">
        <h5 class="mb-3">ขั้นที่ 1: ยืนยันยอดเปิดเดือน</h5>
        <p class="text-muted">เงินเดือนเข้าวันสิ้นเดือน → ใช้ในเดือนถัดไป · ยอดเปิด = ยอดยกมา + เงินเดือน</p>
        <form method="post" action="index.php?page=wizard&action=step1">
            <?= csrf_field() ?>
            <input type="hidden" name="month_id" value="<?= (int) $month['id'] ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">ยอดยกมา (บาท)</label>
                    <input type="number" step="0.01" min="0" name="opening_balance" class="form-control"
                           value="<?= e((string) $month['opening_balance']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">เงินเดือนเข้า (บาท)</label>
                    <input type="number" step="0.01" min="0" name="salary_amount" class="form-control"
                           value="<?= e((string) $month['salary_amount']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">ยอดใช้ได้รวม</label>
                    <input type="text" class="form-control" readonly
                           value="<?= money((float) $month['opening_balance'] + (float) $month['salary_amount']) ?>">
                </div>
            </div>
            <div class="mt-4 d-flex justify-content-end">
                <button class="btn btn-mali" type="submit">ถัดไป <i class="fa-solid fa-arrow-right ms-1"></i></button>
            </div>
        </form>
    </div>
<?php elseif ($step === 2): ?>
    <div class="panel">
        <h5 class="mb-3">ขั้นที่ 2: จัดสรรงบสินเชื่อ</h5>
        <p class="text-muted">ยอดใช้ได้ <?= money((float) $month['available_total']) ?> บาท — ใส่จำนวนที่จะจ่ายเดือนนี้ (ค่าเริ่มต้นตามคาดการณ์)</p>
        <form method="post" action="index.php?page=wizard&action=step2">
            <?= csrf_field() ?>
            <input type="hidden" name="month_id" value="<?= (int) $month['id'] ?>">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>วงเงิน</th>
                        <th class="text-end">ยอดกู้</th>
                        <th class="text-end">คงเหลือ</th>
                        <th class="text-end">ดอกเบี้ย/ปี</th>
                        <th class="text-end">ระยะ (เดือน)</th>
                        <th style="width:160px" class="text-end">จ่ายเดือนนี้</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($loans as $loan): ?>
                        <?php $pay = $allocMap[(int) $loan['id']] ?? (float) $loan['expected_payment']; ?>
                        <tr>
                            <td><?= e($loan['name']) ?></td>
                            <td class="text-end"><?= money($loan['principal']) ?></td>
                            <td class="text-end"><?= money($loan['balance']) ?></td>
                            <td class="text-end"><?= money($loan['interest_rate']) ?>%</td>
                            <td class="text-end"><?= (int) $loan['term_months'] ?></td>
                            <td>
                                <input type="number" step="0.01" min="0" class="form-control text-end"
                                       name="loan_pay[<?= (int) $loan['id'] ?>]" value="<?= e((string) $pay) ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-between">
                <a href="index.php?page=wizard&step=1" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> ย้อนกลับ</a>
                <button class="btn btn-mali" type="submit">ถัดไป <i class="fa-solid fa-arrow-right ms-1"></i></button>
            </div>
        </form>
    </div>
<?php elseif ($step === 3): ?>
    <div class="panel">
        <h5 class="mb-3">ขั้นที่ 3: ให้คุณแม่ + งบส่วนตัว</h5>
        <form method="post" action="index.php?page=wizard&action=step3">
            <?= csrf_field() ?>
            <input type="hidden" name="month_id" value="<?= (int) $month['id'] ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">ให้คุณแม่ (บาท)</label>
                    <input type="number" step="0.01" min="0" name="mother_budget" class="form-control"
                           value="<?= e((string) $motherAlloc) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">งบค่าใช้จ่ายส่วนตัว (บาท)</label>
                    <input type="number" step="0.01" min="0" name="personal_budget" class="form-control"
                           value="<?= e((string) $personalAlloc) ?>" required>
                </div>
            </div>
            <div class="mt-4 d-flex justify-content-between">
                <a href="index.php?page=wizard&step=2" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> ย้อนกลับ</a>
                <button class="btn btn-mali" type="submit">ถัดไป <i class="fa-solid fa-arrow-right ms-1"></i></button>
            </div>
        </form>
    </div>
<?php elseif ($step === 4): ?>
    <div class="panel">
        <h5 class="mb-3">ขั้นที่ 4: สรุปกรอบเดือน</h5>
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="label">ยอดใช้ได้</div>
                    <div class="value"><?= money((float) $month['available_total']) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="label">จัดสรรแล้ว</div>
                    <div class="value"><?= money($budget->plannedTotal((int) $month['id'])) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="label">คงเหลือหลังจัดสรร</div>
                    <div class="value <?= $remaining < 0 ? 'text-danger' : 'text-success' ?>"><?= money($remaining) ?></div>
                </div>
            </div>
        </div>

        <div class="table-responsive mb-3">
            <table class="table">
                <thead>
                <tr><th>ซองงบ</th><th class="text-end">จำนวน</th></tr>
                </thead>
                <tbody>
                <?php foreach ($allocations as $a): ?>
                    <tr>
                        <td><?= e($a['label']) ?> <span class="badge badge-soft"><?= e(category_label($a['category'])) ?></span></td>
                        <td class="text-end"><?= money($a['planned_amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="alert <?= $remaining < 0 ? 'alert-danger' : 'alert-info' ?>">
            คาดการณ์ออม <?= e((string) $budget->savingsRate()) ?>% = <strong><?= money($projSave) ?></strong> บาท
            · ยอดยกไปเดือนถัดไปประมาณ <strong><?= money($projCarry) ?></strong> บาท
            <?php if ($remaining < 0): ?>
                <div class="mt-1">คำเตือน: จัดสรรเกินยอดใช้ได้ กรุณาย้อนกลับไปปรับก่อนเปิดใช้งาน</div>
            <?php endif; ?>
        </div>

        <form method="post" action="index.php?page=wizard&action=activate" id="activate-form">
            <?= csrf_field() ?>
            <input type="hidden" name="month_id" value="<?= (int) $month['id'] ?>">
            <div class="d-flex justify-content-between">
                <a href="index.php?page=wizard&step=3" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> ย้อนกลับ</a>
                <button type="button" class="btn btn-mali"
                        <?= $remaining < 0 ? 'disabled' : '' ?>
                        data-confirm="ยืนยันเปิดใช้งานเดือนนี้? ระบบจะบันทึกรายรับและตัดจ่ายตามซองที่จัดสรร"
                        data-form="activate-form">
                    เปิดใช้งานเดือนนี้ <i class="fa-solid fa-check ms-1"></i>
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
$title = 'เปิดเดือน';
$active = 'wizard';
require base_path('views/layout.php');
