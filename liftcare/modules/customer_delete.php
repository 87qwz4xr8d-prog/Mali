<?php

declare(strict_types=1);

Auth::requireRole(['admin', 'manager']);
$id = (int) get('id', 0);
$db = Database::conn();

$stmt = $db->prepare('SELECT code FROM customers WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) {
    flash('error', 'ไม่พบข้อมูล');
    redirect(page_url('customers'));
}

try {
    $del = $db->prepare('DELETE FROM customers WHERE id = ?');
    $del->bind_param('i', $id);
    $del->execute();
    $del->close();
    flash('success', 'ลบลูกค้า ' . $row['code'] . ' แล้ว');
} catch (Throwable $e) {
    flash('error', 'ลบไม่ได้ มีรถหรือใบงานอ้างอิง');
}
redirect(page_url('customers'));
