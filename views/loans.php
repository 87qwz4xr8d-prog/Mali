<?php
/** @var list $loans */
/** @var array|null $loan */
/** @var list $payments */
/** @var string $action */
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="page-title">สินเชื่อส่วนบุคคล</h2>
        <p class="page-sub mb-0">จัดการ 5 วงเงินและติดตามยอดคงเหลือ</p>
    </div>
</div>

<?php if ($action === 'edit' && $loan): ?>
    <div class="panel">
        <h5 class="mb-3">แก้ไข: <?= e($loan['name']) ?></h5>
        <form method="post" action="index.php?page=loans&action=save">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $loan['id'] ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">ชื่อวงเงิน</label>
                    <input type="text" name="name" class="form-control" required value="<?= e($loan['name']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">ยอดกู้</label>
                    <input type="number" step="0.01" name="principal" class="form-control" required value="<?= e((string) $loan['principal']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">ยอดคงเหลือ</label>
                    <input type="number" step="0.01" name="balance" class="form-control" required value="<?= e((string) $loan['balance']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">ดอกเบี้ย % / ปี</label>
                    <input type="number" step="0.01" name="interest_rate" class="form-control" required value="<?= e((string) $loan['interest_rate']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">ระยะเวลา (เดือน)</label>
                    <input type="number" name="term_months" class="form-control" required value="<?= e((string) $loan['term_months']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">คาดจ่าย/เดือน</label>
                    <input type="number" step="0.01" name="expected_payment" class="form-control" required value="<?= e((string) $loan['expected_payment']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">สถานะ</label>
                    <select name="is_active" class="form-select">
                        <option value="1" <?= (int)$loan['is_active'] === 1 ? 'selected' : '' ?>>ใช้งาน</option>
                        <option value="0" <?= (int)$loan['is_active'] === 0 ? 'selected' : '' ?>>ปิด</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">หมายเหตุ</label>
                    <textarea name="notes" class="form-control" rows="2"><?= e($loan['notes'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-mali" type="submit">บันทึก</button>
                <a href="index.php?page=loans" class="btn btn-outline-secondary">ยกเลิก</a>
            </div>
        </form>

        <hr class="my-4">
        <h6>ประวัติการชำระ</h6>
        <?php if (!$payments): ?>
            <p class="text-muted mb-0">ยังไม่มีรายการชำระ</p>
        <?php else: ?>
            <table class="table table-sm">
                <thead><tr><th>วันที่</th><th>รายละเอียด</th><th class="text-end">จำนวน</th></tr></thead>
                <tbody>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?= e($p['txn_date']) ?></td>
                        <td><?= e($p['description']) ?></td>
                        <td class="text-end"><?= money($p['amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="panel">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <th>วงเงิน</th>
                <th class="text-end">ยอดกู้</th>
                <th class="text-end">ดอกเบี้ย</th>
                <th class="text-end">ระยะ</th>
                <th class="text-end">คาดจ่าย/เดือน</th>
                <th class="text-end">คงเหลือ</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php
            $sumPay = 0;
            $sumBal = 0;
            foreach ($loans as $l):
                $sumPay += (float) $l['expected_payment'];
                $sumBal += (float) $l['balance'];
                $pct = (float) $l['principal'] > 0
                    ? min(100, round((1 - ((float)$l['balance'] / (float)$l['principal'])) * 100))
                    : 0;
            ?>
                <tr>
                    <td>
                        <strong><?= e($l['name']) ?></strong>
                        <?php if (!(int)$l['is_active']): ?>
                            <span class="badge text-bg-secondary">ปิด</span>
                        <?php endif; ?>
                        <div class="progress mt-2" style="width:160px">
                            <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
                        </div>
                        <small class="text-muted">ชำระไปแล้ว <?= $pct ?>%</small>
                    </td>
                    <td class="text-end"><?= money($l['principal']) ?></td>
                    <td class="text-end"><?= money($l['interest_rate']) ?>%</td>
                    <td class="text-end"><?= (int) $l['term_months'] ?> ด.</td>
                    <td class="text-end"><?= money($l['expected_payment']) ?></td>
                    <td class="text-end fw-bold"><?= money($l['balance']) ?></td>
                    <td class="text-end">
                        <a href="index.php?page=loans&action=edit&id=<?= (int) $l['id'] ?>" class="btn btn-sm btn-outline-success">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
            <tr class="fw-bold">
                <td>รวม</td>
                <td></td><td></td><td></td>
                <td class="text-end"><?= money($sumPay) ?></td>
                <td class="text-end"><?= money($sumBal) ?></td>
                <td></td>
            </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = 'สินเชื่อ';
$active = 'loans';
require base_path('views/layout.php');
