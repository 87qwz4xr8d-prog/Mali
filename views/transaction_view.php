<?php
/** @var array $txn */
/** @var list $attachments */
ob_start();
?>
<div class="mb-3">
    <a href="index.php?page=transactions" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> กลับ
    </a>
</div>
<div class="panel">
    <h2 class="page-title">รายละเอียดรายการ #<?= (int) $txn['id'] ?></h2>
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="text-muted">วันที่</div><strong><?= e($txn['txn_date']) ?></strong></div>
        <div class="col-md-3"><div class="text-muted">ประเภท</div><strong><?= $txn['type'] === 'income' ? 'รายรับ' : 'รายจ่าย' ?></strong></div>
        <div class="col-md-3"><div class="text-muted">หมวด</div><strong><?= e(category_label($txn['category'])) ?></strong></div>
        <div class="col-md-3"><div class="text-muted">จำนวน</div><strong><?= money($txn['amount']) ?> บาท</strong></div>
        <div class="col-12"><div class="text-muted">รายละเอียด</div><strong><?= e($txn['description']) ?></strong></div>
        <?php if ($txn['notes']): ?>
            <div class="col-12"><div class="text-muted">หมายเหตุ</div><?= e($txn['notes']) ?></div>
        <?php endif; ?>
    </div>

    <h5 class="mb-3"><i class="fa-solid fa-paperclip me-2"></i>หลักฐานแนบ</h5>
    <?php if (!$attachments): ?>
        <p class="text-muted">ไม่มีไฟล์แนบ</p>
        <form method="post" action="index.php?page=transactions&action=attach" enctype="multipart/form-data" class="row g-2">
            <?= csrf_field() ?>
            <input type="hidden" name="transaction_id" value="<?= (int) $txn['id'] ?>">
            <div class="col-md-6">
                <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
            </div>
            <div class="col-md-3">
                <button class="btn btn-mali" type="submit">อัปโหลด</button>
            </div>
        </form>
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
        <form method="post" action="index.php?page=transactions&action=attach" enctype="multipart/form-data" class="row g-2">
            <?= csrf_field() ?>
            <input type="hidden" name="transaction_id" value="<?= (int) $txn['id'] ?>">
            <div class="col-md-6">
                <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-success" type="submit">แนบเพิ่ม</button>
            </div>
        </form>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
$title = 'รายละเอียดรายการ';
$active = 'transactions';
require base_path('views/layout.php');
