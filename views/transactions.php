<?php
/** @var BudgetService $budget */
/** @var list $transactions */
/** @var array|null $month */
/** @var list $months */
/** @var list $loans */
/** @var string $action */
/** @var array|null $edit */
$edit = $edit ?? null;
$isEdit = $action === 'edit' && $edit;
$formType = $isEdit ? $edit['type'] : 'expense';
$formCategory = $isEdit ? $edit['category'] : 'personal';
$formLoanId = $isEdit ? (int) ($edit['loan_id'] ?? 0) : 0;
$formDate = $isEdit ? $edit['txn_date'] : date('Y-m-d');
$formAmount = $isEdit ? (string) $edit['amount'] : '';
$formDesc = $isEdit ? (string) $edit['description'] : '';
$formPayee = $isEdit ? (string) ($edit['payee'] ?? '') : '';
$formPayMethod = $isEdit ? (string) ($edit['payment_method'] ?? '') : '';
$formRef = $isEdit ? (string) ($edit['reference_no'] ?? '') : '';
$formNotes = $isEdit ? (string) ($edit['notes'] ?? '') : '';
ob_start();
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="page-title">บัญชีรายรับ-รายจ่าย</h2>
        <p class="page-sub mb-0">บันทึก แก้ไข และเพิ่มรายละเอียดรายการพร้อมแนบหลักฐาน</p>
    </div>
    <?php if ($month && $month['status'] === 'active' && $action !== 'create' && $action !== 'edit'): ?>
        <a href="index.php?page=transactions&action=create" class="btn btn-mali">
            <i class="fa-solid fa-plus me-1"></i> เพิ่มรายการ
        </a>
    <?php endif; ?>
</div>

<?php if ($action === 'create' || $action === 'edit'): ?>
    <div class="panel">
        <h5 class="mb-3"><?= $isEdit ? 'แก้ไขรายการ #' . (int) $edit['id'] : 'เพิ่มรายการใหม่' ?></h5>
        <?php if (!$month || !in_array($month['status'], ['active', 'draft'], true) || ($action === 'create' && $month['status'] !== 'active')): ?>
            <div class="alert alert-warning">ต้องมีเดือนที่กำลังใช้งานก่อน จึงจะบันทึกรายการได้</div>
        <?php else: ?>
            <form method="post" action="index.php?page=transactions&action=save" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="month_id" value="<?= (int) $month['id'] ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
                <?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">ประเภท</label>
                        <select name="type" class="form-select" required>
                            <option value="expense" <?= $formType === 'expense' ? 'selected' : '' ?>>รายจ่าย</option>
                            <option value="income" <?= $formType === 'income' ? 'selected' : '' ?>>รายรับ</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">หมวด</label>
                        <select name="category" id="txn-category" class="form-select" required>
                            <?php foreach (['personal', 'mother', 'loan', 'other', 'salary'] as $cat): ?>
                                <option value="<?= $cat ?>" <?= $formCategory === $cat ? 'selected' : '' ?>>
                                    <?= e(category_label($cat)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">วันที่</label>
                        <input type="date" name="txn_date" class="form-control" required value="<?= e($formDate) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">จำนวนเงิน</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required
                               value="<?= e($formAmount) ?>">
                    </div>
                    <div class="col-md-6" id="loan-select-wrap" style="display:none">
                        <label class="form-label">วงเงินสินเชื่อ</label>
                        <select name="loan_id" class="form-select">
                            <option value="">— เลือก —</option>
                            <?php foreach ($loans as $loan): ?>
                                <option value="<?= (int) $loan['id'] ?>" <?= $formLoanId === (int) $loan['id'] ? 'selected' : '' ?>>
                                    <?= e($loan['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">หัวข้อ / รายละเอียดสั้น</label>
                        <input type="text" name="description" class="form-control" maxlength="255" required
                               value="<?= e($formDesc) ?>" placeholder="เช่น ค่าอาหารกลางวัน, ค่าเดินทาง">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ผู้รับ / ร้านค้า</label>
                        <input type="text" name="payee" class="form-control" maxlength="120"
                               value="<?= e($formPayee) ?>" placeholder="ชื่อร้าน หรือ ผู้รับเงิน">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ช่องทางชำระ</label>
                        <select name="payment_method" class="form-select">
                            <option value="">— ไม่ระบุ —</option>
                            <?php foreach (payment_methods() as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $formPayMethod === $key ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">เลขอ้างอิง / สลิป</label>
                        <input type="text" name="reference_no" class="form-control" maxlength="100"
                               value="<?= e($formRef) ?>" placeholder="เลขที่โอน หรือ เลขใบเสร็จ">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">หมายเหตุเพิ่มเติม</label>
                        <textarea name="notes" class="form-control" rows="3"
                                  placeholder="รายละเอียดเพิ่มเติมของรายการ"><?= e($formNotes) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?= $isEdit ? 'แนบหลักฐานเพิ่ม' : 'แนบหลักฐาน' ?> (JPG/PNG/PDF)</label>
                        <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf">
                        <div class="form-text">สูงสุด 5 MB<?= $isEdit ? ' · ไฟล์เดิมยังอยู่' : '' ?></div>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="force" value="1" id="force">
                            <label class="form-check-label" for="force">บังคับบันทึกแม้เกินกรอบงบ (ใช้เมื่อจำเป็น)</label>
                        </div>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-mali" type="submit">
                        <i class="fa-solid fa-save me-1"></i> <?= $isEdit ? 'บันทึกการแก้ไข' : 'บันทึก' ?>
                    </button>
                    <?php if ($isEdit): ?>
                        <a href="index.php?page=transactions&action=view&id=<?= (int) $edit['id'] ?>" class="btn btn-outline-secondary">ยกเลิก</a>
                    <?php else: ?>
                        <a href="index.php?page=transactions" class="btn btn-outline-secondary">ยกเลิก</a>
                    <?php endif; ?>
                </div>
            </form>
            <script>
              const cat = document.getElementById('txn-category');
              const wrap = document.getElementById('loan-select-wrap');
              const toggle = () => { wrap.style.display = cat.value === 'loan' ? '' : 'none'; };
              cat.addEventListener('change', toggle);
              toggle();
            </script>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($action !== 'create' && $action !== 'edit'): ?>
<div class="panel">
    <form class="row g-2 mb-3" method="get" action="index.php">
        <input type="hidden" name="page" value="transactions">
        <div class="col-md-4">
            <select name="month_id" class="form-select" onchange="this.form.submit()">
                <option value="">ทุกเดือน</option>
                <?php foreach ($months as $m): ?>
                    <option value="<?= (int) $m['id'] ?>" <?= isset($_GET['month_id']) && (int)$_GET['month_id'] === (int)$m['id'] ? 'selected' : (($month && !isset($_GET['month_id']) && (int)$month['id'] === (int)$m['id']) ? 'selected' : '') ?>>
                        <?= e(thai_month($m['budget_ym'])) ?> (<?= e(status_label($m['status'])) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="type" class="form-select" onchange="this.form.submit()">
                <option value="">ทุกประเภท</option>
                <option value="income" <?= ($_GET['type'] ?? '') === 'income' ? 'selected' : '' ?>>รายรับ</option>
                <option value="expense" <?= ($_GET['type'] ?? '') === 'expense' ? 'selected' : '' ?>>รายจ่าย</option>
            </select>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
            <tr>
                <th>วันที่</th>
                <th>ประเภท</th>
                <th>หมวด</th>
                <th>รายละเอียด</th>
                <th class="text-end">จำนวน</th>
                <th>หลักฐาน</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$transactions): ?>
                <tr><td colspan="7" class="text-muted text-center py-4">ไม่พบรายการ</td></tr>
            <?php endif; ?>
            <?php foreach ($transactions as $txn): ?>
                <tr>
                    <td><?= e($txn['txn_date']) ?></td>
                    <td>
                        <span class="badge text-bg-<?= $txn['type'] === 'income' ? 'success' : 'danger' ?>">
                            <?= $txn['type'] === 'income' ? 'รับ' : 'จ่าย' ?>
                        </span>
                    </td>
                    <td>
                        <?= e(category_label($txn['category'])) ?>
                        <?php if ($txn['loan_name']): ?>
                            <div class="small text-muted"><?= e($txn['loan_name']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div><?= e($txn['description']) ?></div>
                        <?php if (!empty($txn['payee'])): ?>
                            <div class="small text-muted"><i class="fa-solid fa-store me-1"></i><?= e($txn['payee']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-end fw-semibold <?= $txn['type'] === 'income' ? 'text-success' : '' ?>">
                        <?= money($txn['amount']) ?>
                    </td>
                    <td>
                        <?php if ((int) $txn['attachment_count'] > 0): ?>
                            <a href="index.php?page=transactions&action=view&id=<?= (int) $txn['id'] ?>" class="badge text-bg-secondary">
                                <i class="fa-solid fa-paperclip"></i> <?= (int) $txn['attachment_count'] ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="index.php?page=transactions&action=view&id=<?= (int) $txn['id'] ?>" class="btn btn-sm btn-outline-secondary" title="ดู">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <a href="index.php?page=transactions&action=edit&id=<?= (int) $txn['id'] ?>" class="btn btn-sm btn-outline-success" title="แก้ไข">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        <a href="index.php?page=transactions&action=delete&id=<?= (int) $txn['id'] ?>&csrf_token=<?= e(csrf_token()) ?>"
                           class="btn btn-sm btn-outline-danger" title="ลบ"
                           data-confirm="ลบรายการนี้?">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
$title = $isEdit ? 'แก้ไขรายการ' : 'รายรับ-รายจ่าย';
$active = 'transactions';
require base_path('views/layout.php');
