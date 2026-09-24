<?php

declare(strict_types=1);

Auth::requireRole(['admin']);
$id = (int) get('id', 0);

if ($id === (int) Auth::user()['id']) {
    flash('error', 'ไม่สามารถลบบัญชีของตัวเองได้');
    redirect(page_url('users'));
}

$db = Database::conn();
$stmt = $db->prepare('SELECT username FROM users WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    flash('error', 'ไม่พบผู้ใช้');
    redirect(page_url('users'));
}

try {
    $del = $db->prepare('DELETE FROM users WHERE id = ?');
    $del->bind_param('i', $id);
    $del->execute();
    $del->close();
    flash('success', 'ลบผู้ใช้ ' . $row['username'] . ' แล้ว');
} catch (Throwable $e) {
    flash('error', 'ลบไม่ได้ มีการอ้างอิงในใบงาน');
}
redirect(page_url('users'));
