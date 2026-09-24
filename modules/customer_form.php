<?php

declare(strict_types=1);

$db = Database::conn();
$id = (int) get('id', 0);
$editing = $id > 0;
$row = [
    'code' => '', 'name' => '', 'contact_name' => '', 'phone' => '',
    'email' => '', 'address' => '', 'notes' => '', 'is_active' => 1,
];

if ($editing) {
    $stmt = $db->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$found) {
        flash('error', 'ไม่พบลูกค้า');
        redirect(page_url('customers'));
    }
    $row = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    Auth::requireRole(['admin', 'manager']);

    $data = [
        'code' => trim((string) post('code')),
        'name' => trim((string) post('name')),
        'contact_name' => trim((string) post('contact_name')),
        'phone' => trim((string) post('phone')),
        'email' => trim((string) post('email')),
        'address' => trim((string) post('address')),
        'notes' => trim((string) post('notes')),
        'is_active' => (int) post('is_active', 1),
    ];

    if ($data['code'] === '' || $data['name'] === '') {
        flash('error', 'กรุณากรอกรหัสและชื่อ');
        redirect(page_url('customer_form', $editing ? ['id' => $id] : []));
    }

    if ($editing) {
        $stmt = $db->prepare('UPDATE customers SET code=?, name=?, contact_name=?, phone=?, email=?, address=?, notes=?, is_active=? WHERE id=?');
        $stmt->bind_param('sssssssii', $data['code'], $data['name'], $data['contact_name'], $data['phone'], $data['email'], $data['address'], $data['notes'], $data['is_active'], $id);
    } else {
        $stmt = $db->prepare('INSERT INTO customers (code, name, contact_name, phone, email, address, notes, is_active) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->bind_param('sssssssi', $data['code'], $data['name'], $data['contact_name'], $data['phone'], $data['email'], $data['address'], $data['notes'], $data['is_active']);
    }
    $stmt->execute();
    $stmt->close();
    flash('success', 'บันทึกลูกค้าเรียบร้อย');
    redirect(page_url('customers'));
}

$pageTitle = $editing ? 'แก้ไขลูกค้า' : 'เพิ่มลูกค้า';
$activePage = 'customers';
require dirname(__DIR__) . '/includes/layout/header.php';
?>

<div class="page-toolbar">
    <p class="page-lead mb-0">ข้อมูลลูกค้า / ไซต์งาน</p>
    <a href="<?= e(page_url('customers')) ?>" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> กลับ</a>
</div>

<div class="panel">
    <div class="panel-body">
        <form method="post" data-confirm-submit="ยืนยันบันทึก?">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">รหัส *</label><input name="code" class="form-control" required value="<?= e((string)$row['code']) ?>"></div>
                <div class="col-md-5"><label class="form-label">ชื่อ *</label><input name="name" class="form-control" required value="<?= e((string)$row['name']) ?>"></div>
                <div class="col-md-4"><label class="form-label">ผู้ติดต่อ</label><input name="contact_name" class="form-control" value="<?= e((string)$row['contact_name']) ?>"></div>
                <div class="col-md-4"><label class="form-label">โทรศัพท์</label><input name="phone" class="form-control" value="<?= e((string)$row['phone']) ?>"></div>
                <div class="col-md-4"><label class="form-label">อีเมล</label><input type="email" name="email" class="form-control" value="<?= e((string)$row['email']) ?>"></div>
                <div class="col-md-4">
                    <label class="form-label">สถานะ</label>
                    <select name="is_active" class="form-select">
                        <option value="1" <?= (int)$row['is_active']===1?'selected':'' ?>>ใช้งาน</option>
                        <option value="0" <?= (int)$row['is_active']===0?'selected':'' ?>>ปิด</option>
                    </select>
                </div>
                <div class="col-12"><label class="form-label">ที่อยู่</label><textarea name="address" class="form-control" rows="2"><?= e((string)$row['address']) ?></textarea></div>
                <div class="col-12"><label class="form-label">หมายเหตุ</label><textarea name="notes" class="form-control" rows="2"><?= e((string)$row['notes']) ?></textarea></div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-accent" type="submit"><i class="fa-solid fa-floppy-disk"></i> บันทึก</button>
                <a href="<?= e(page_url('customers')) ?>" class="btn btn-outline-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/layout/footer.php'; ?>
