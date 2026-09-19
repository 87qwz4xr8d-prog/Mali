<?php
/** @var array $txn */
/** @var list $attachments */
/** @var array|null $txnMonth */
$canEdit = $txnMonth && $txnMonth['status'] !== 'closed';
ob_start();
?>
<div class="mb-3 d-flex flex-wrap gap-2">
    <a href="index.php?page=transactions" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> กลับ
    </a>
    <?php if ($canEdit): ?>
        <a href="index.php?page=transactions&action=edit&id=<?= (int) $txn['id'] ?>" class="btn btn-sm btn-mali">
            <i class="fa-solid fa-pen me-1"></i> แก้ไขรายการ
        </a>
    <?php endif; ?>
</div>
<div class="panel">
    <h2 class="page-title">รายละเอียดรายการ #<?= (int) $txn['id'] ?></h2>
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="text-muted">วันที่</div><strong><?= e($txn['txn_date']) ?></strong></div>
        <div class="col-md-3"><div class="text-muted">ประเภท</div><strong><?= $txn['type'] === 'income' ? 'รายรับ' : 'รายจ่าย' ?></strong></div>
        <div class="col-md-3"><div class="text-muted">หมวด</div><strong><?= e(category_label($txn['category'])) ?></strong></div>
        <div class="col-md-3"><div class="text-muted">จำนวน</div><strong><?= money($txn['amount']) ?> บาท</strong></div>
        <div class="col-md-6"><div class="text-muted">หัวข้อ / รายละเอียด</div><strong><?= e($txn['description']) ?></strong></div>
        <div class="col-md-3"><div class="text-muted">ผู้รับ / ร้านค้า</div><strong><?= e($txn['payee'] ?: '—') ?></strong></div>
        <div class="col-md-3"><div class="text-muted">ช่องทางชำระ</div><strong><?= e(payment_method_label($txn['payment_method'] ?? null)) ?></strong></div>
        <div class="col-md-6"><div class="text-muted">เลขอ้างอิง / สลิป</div><strong><?= e($txn['reference_no'] ?: '—') ?></strong></div>
        <?php if ($txn['loan_name']): ?>
            <div class="col-md-6"><div class="text-muted">วงเงินสินเชื่อ</div><strong><?= e($txn['loan_name']) ?></strong></div>
        <?php endif; ?>
        <?php if ($txn['notes']): ?>
            <div class="col-12"><div class="text-muted">หมายเหตุ</div><div class="mt-1"><?= nl2br(e($txn['notes'])) ?></div></div>
        <?php endif; ?>
    </div>

    <h5 class="mb-3"><i class="fa-solid fa-paperclip me-2"></i>หลักฐานแนบ</h5>
    <?php if (!$attachments): ?>
        <p class="text-muted">ไม่มีไฟล์แนบ</p>
    <?php else: ?>
        <ul class="list-group mb-3">
            <?php foreach ($attachments as $att): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-file me-2"></i><?= e($att['original_name']) ?>
                        <small class="text-muted">(<?= number_format((int)$att['file_size'] / 1024, 1) ?> KB)</small>
                    </span>
                    <a class="btn btn-sm btn-outline-success" href="index.php?page=download&id=<?= (int) $att['id'] ?>">
                        <i class="fa-solid fa-download"></i> ดาวน์โหลด
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($canEdit): ?>
        <form method="post" action="index.php?page=transactions&action=attach" enctype="multipart/form-data" class="row g-2">
            <?= csrf_field() ?>
            <input type="hidden" name="transaction_id" value="<?= (int) $txn['id'] ?>">
            <div class="col-md-6">
                <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-success" type="submit">
                    <?= $attachments ? 'แนบเพิ่ม' : 'อัปโหลด' ?>
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
$title = 'รายละเอียดรายการ';
$active = 'transactions';
require base_path('views/layout.php');
