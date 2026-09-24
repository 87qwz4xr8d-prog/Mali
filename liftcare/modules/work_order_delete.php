<?php

declare(strict_types=1);

Auth::requireRole(['admin', 'manager']);
$id = (int) get('id', 0);
$db = Database::conn();

$stmt = $db->prepare('SELECT wo_no FROM work_orders WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    flash('error', 'ไม่พบใบงาน');
    redirect(page_url('work_orders'));
}

$del = $db->prepare('DELETE FROM work_orders WHERE id = ?');
$del->bind_param('i', $id);
$del->execute();
$del->close();
flash('success', 'ลบใบงาน ' . $row['wo_no'] . ' แล้ว');
redirect(page_url('work_orders'));
