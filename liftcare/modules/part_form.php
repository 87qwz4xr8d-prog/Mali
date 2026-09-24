<?php

declare(strict_types=1);

$db = Database::conn();
$id = (int) get('id', 0);
$editing = $id > 0;
$row = [
    'sku' => '', 'name' => '', 'category' => '', 'unit' => 'ชิ้น',
    'qty_on_hand' => '0', 'reorder_level' => '0', 'unit_cost' => '0',
    'location' => '', 'notes' => '', 'is_active' => 1,
];

if ($editing) {
    $stmt = $db->prepare('SELECT * FROM spare_parts WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$found) {
        flash('error', 'ไม่พบอะไหล่');
        redirect(page_url('parts'));
    }
    $row = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    Auth::requireRole(['admin', 'manager']);

    $data = [
        'sku' => trim((string) post('sku')),
        'name' => trim((string) post('name')),
        'category' => trim((string) post('category')),
        'unit' => trim((string) post('unit', 'ชิ้น')),
        'qty_on_hand' => (float) post('qty_on_hand', 0),
        'reorder_level' => (float) post('reorder_level', 0),
        'unit_cost' => (float) post('unit_cost', 0),
        'location' => trim((string) post('location')),
        'notes' => trim((string) post('notes')),
        'is_active' => (int) post('is_active', 1),
    ];

    if ($data['sku'] === '' || $data['name'] === '') {
        flash('error', 'กรุณากรอก SKU และชื่อ');
        redirect(page_url('part_form', $editing ? ['id' => $id] : []));
    }

    if ($editing) {
        $stmt = $db->prepare('UPDATE spare_parts SET sku=?, name=?, category=?, unit=?, qty_on_hand=?, reorder_level=?, unit_cost=?, location=?, notes=?, is_active=? WHERE id=?');
        $stmt->bind_param('ssssdddssii', $data['sku'], $data['name'], $data['category'], $data['unit'], $data['qty_on_hand'], $data['reorder_level'], $data['unit_cost'], $data['location'], $data['notes'], $data['is_active'], $id);
    } else {
        $stmt = $db->prepare('INSERT INTO spare_parts (sku, name, category, unit, qty_on_hand, reorder_level, unit_cost, location, notes, is_active) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $stmt->bind_param('ssssdddssi', $data['sku'], $data['name'], $data['category'], $data['unit'], $data['qty_on_hand'], $data['reorder_level'], $data['unit_cost'], $data['location'], $data['notes'], $data['is_active']);
    }
    $stmt->execute();
    $stmt->close();
    flash('success', 'บันทึกอะไหล่เรียบร้อย');
    redirect(page_url('parts'));
}

$pageTitle = $editing ? 'แก้ไขอะไหล่' : 'เพิ่มอะไหล่';
$activePage = 'parts';
require dirname(__DIR__) . '/includes/layout/header.php';
?>

<div class="page-toolbar">
    <p class="page-lead mb-0">ข้อมูลคลังอะไหล่</p>
    <a href="<?= e(page_url('parts')) ?>" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> กลับ</a>
</div>

<div class="panel">
    <div class="panel-body">
        <form method="post" data-confirm-submit="ยืนยันบันทึกอะไหล่?">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">SKU *</label><input name="sku" class="form-control" required value="<?= e((string)$row['sku']) ?>"></div>
                <div class="col-md-5"><label class="form-label">ชื่อ *</label><input name="name" class="form-control" required value="<?= e((string)$row['name']) ?>"></div>
                <div class="col-md-4"><label class="form-label">หมวด</label><input name="category" class="form-control" value="<?= e((string)$row['category']) ?>"></div>
                <div class="col-md-2"><label class="form-label">หน่วย</label><input name="unit" class="form-control" value="<?= e((string)$row['unit']) ?>"></div>
                <div class="col-md-2"><label class="form-label">คงเหลือ</label><input type="number" step="0.01" name="qty_on_hand" class="form-control" value="<?= e((string)$row['qty_on_hand']) ?>"></div>
                <div class="col-md-2"><label class="form-label">จุดสั่งซื้อ</label><input type="number" step="0.01" name="reorder_level" class="form-control" value="<?= e((string)$row['reorder_level']) ?>"></div>
                <div class="col-md-3"><label class="form-label">ต้นทุน/หน่วย</label><input type="number" step="0.01" name="unit_cost" class="form-control" value="<?= e((string)$row['unit_cost']) ?>"></div>
                <div class="col-md-3"><label class="form-label">ที่เก็บ</label><input name="location" class="form-control" value="<?= e((string)$row['location']) ?>"></div>
                <div class="col-md-3">
                    <label class="form-label">สถานะ</label>
                    <select name="is_active" class="form-select">
                        <option value="1" <?= (int)$row['is_active']===1?'selected':'' ?>>ใช้งาน</option>
                        <option value="0" <?= (int)$row['is_active']===0?'selected':'' ?>>ปิด</option>
                    </select>
                </div>
                <div class="col-12"><label class="form-label">หมายเหตุ</label><textarea name="notes" class="form-control" rows="2"><?= e((string)$row['notes']) ?></textarea></div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-accent" type="submit"><i class="fa-solid fa-floppy-disk"></i> บันทึก</button>
                <a href="<?= e(page_url('parts')) ?>" class="btn btn-outline-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/layout/footer.php'; ?>
