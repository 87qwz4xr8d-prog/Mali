<?php

declare(strict_types=1);

Auth::requireRole(['admin', 'manager']);
$id = (int) get('id', 0);
if ($id <= 0) {
    redirect(page_url('vehicles'));
}

$db = Database::conn();
$stmt = $db->prepare('SELECT asset_code FROM vehicles WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    flash('error', 'ไม่พบข้อมูล');
    redirect(page_url('vehicles'));
}

try {
    $del = $db->prepare('DELETE FROM vehicles WHERE id = ?');
    $del->bind_param('i', $id);
    $del->execute();
    $del->close();
    flash('success', 'ลบรถ ' . $row['asset_code'] . ' แล้ว');
} catch (Throwable $e) {
    flash('error', 'ลบไม่ได้ มีใบงานอ้างอิงอยู่ — เปลี่ยนสถานะเป็นปลดระวางแทน');
}
redirect(page_url('vehicles'));
