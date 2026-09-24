<?php

declare(strict_types=1);

Auth::requireRole(['admin', 'manager']);
$db = Database::conn();
$id = (int) get('id', 0);
$editing = $id > 0;
$isAdmin = Auth::isAdmin();

$row = [
    'username' => '', 'full_name' => '', 'email' => '', 'phone' => '',
    'role' => 'technician', 'is_active' => 1,
];

if ($editing) {
    $stmt = $db->prepare('SELECT id, username, full_name, email, phone, role, is_active FROM users WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$found) {
        flash('error', 'ไม่พบผู้ใช้');
        redirect(page_url('users'));
    }
    if (!$isAdmin && (int) $found['id'] !== (int) Auth::user()['id'] && $found['role'] === 'admin') {
        flash('error', 'ไม่มีสิทธิ์แก้ไขผู้ดูแลระบบ');
        redirect(page_url('users'));
    }
    $row = $found;
} elseif (!$isAdmin) {
    flash('error', 'เฉพาะผู้ดูแลระบบเท่านั้นที่เพิ่มผู้ใช้ได้');
    redirect(page_url('users'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $data = [
        'username' => trim((string) post('username')),
        'full_name' => trim((string) post('full_name')),
        'email' => trim((string) post('email')),
        'phone' => trim((string) post('phone')),
        'role' => (string) post('role', 'technician'),
        'is_active' => (int) post('is_active', 1),
        'password' => (string) post('password', ''),
    ];

    if (!$isAdmin) {
        $data['role'] = $row['role'];
        $data['is_active'] = (int) $row['is_active'];
        $data['username'] = $row['username'];
    }

    if ($data['username'] === '' || $data['full_name'] === '') {
        flash('error', 'กรุณากรอกชื่อผู้ใช้และชื่อ-นามสกุล');
        redirect(page_url('user_form', $editing ? ['id' => $id] : []));
    }

    if ($editing) {
        if ($data['password'] !== '') {
            $hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $db->prepare('UPDATE users SET username=?, full_name=?, email=?, phone=?, role=?, is_active=?, password_hash=? WHERE id=?');
            $stmt->bind_param('sssssisi', $data['username'], $data['full_name'], $data['email'], $data['phone'], $data['role'], $data['is_active'], $hash, $id);
        } else {
            $stmt = $db->prepare('UPDATE users SET username=?, full_name=?, email=?, phone=?, role=?, is_active=? WHERE id=?');
            $stmt->bind_param('sssssii', $data['username'], $data['full_name'], $data['email'], $data['phone'], $data['role'], $data['is_active'], $id);
        }
        $stmt->execute();
        $stmt->close();
        flash('success', 'บันทึกผู้ใช้เรียบร้อย');
    } else {
        if ($data['password'] === '') {
            flash('error', 'กรุณากำหนดรหัสผ่าน');
            redirect(page_url('user_form'));
        }
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO users (username, password_hash, full_name, email, phone, role, is_active) VALUES (?,?,?,?,?,?,?)');
        $stmt->bind_param('ssssssi', $data['username'], $hash, $data['full_name'], $data['email'], $data['phone'], $data['role'], $data['is_active']);
        $stmt->execute();
        $stmt->close();
        flash('success', 'เพิ่มผู้ใช้เรียบร้อย');
    }
    redirect(page_url('users'));
}

$pageTitle = $editing ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้';
$activePage = 'users';
require dirname(__DIR__) . '/includes/layout/header.php';
?>

<div class="page-toolbar">
    <p class="page-lead mb-0">บัญชีเข้าสู่ระบบ</p>
    <a href="<?= e(page_url('users')) ?>" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> กลับ</a>
</div>

<div class="panel">
    <div class="panel-body">
        <form method="post" data-confirm-submit="ยืนยันบันทึกผู้ใช้?">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">ชื่อผู้ใช้ *</label>
                    <input name="username" class="form-control" required <?= $editing && !$isAdmin ? 'readonly' : '' ?> value="<?= e((string)$row['username']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">ชื่อ-นามสกุล *</label>
                    <input name="full_name" class="form-control" required value="<?= e((string)$row['full_name']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">รหัสผ่าน <?= $editing ? '(ว่างไว้หากไม่เปลี่ยน)' : '*' ?></label>
                    <input type="password" name="password" class="form-control" <?= $editing ? '' : 'required' ?> autocomplete="new-password">
                </div>
                <div class="col-md-4"><label class="form-label">อีเมล</label><input type="email" name="email" class="form-control" value="<?= e((string)$row['email']) ?>"></div>
                <div class="col-md-4"><label class="form-label">โทรศัพท์</label><input name="phone" class="form-control" value="<?= e((string)$row['phone']) ?>"></div>
                <div class="col-md-2">
                    <label class="form-label">บทบาท</label>
                    <select name="role" class="form-select" <?= $isAdmin ? '' : 'disabled' ?>>
                        <?php foreach (['admin','manager','technician','viewer'] as $r): ?>
                            <option value="<?= $r ?>" <?= $row['role']===$r?'selected':'' ?>><?= e(role_label($r)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$isAdmin): ?><input type="hidden" name="role" value="<?= e($row['role']) ?>"><?php endif; ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label">สถานะ</label>
                    <select name="is_active" class="form-select" <?= $isAdmin ? '' : 'disabled' ?>>
                        <option value="1" <?= (int)$row['is_active']===1?'selected':'' ?>>ใช้งาน</option>
                        <option value="0" <?= (int)$row['is_active']===0?'selected':'' ?>>ระงับ</option>
                    </select>
                    <?php if (!$isAdmin): ?><input type="hidden" name="is_active" value="<?= (int)$row['is_active'] ?>"><?php endif; ?>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-accent" type="submit"><i class="fa-solid fa-floppy-disk"></i> บันทึก</button>
                <a href="<?= e(page_url('users')) ?>" class="btn btn-outline-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/layout/footer.php'; ?>
