<?php

declare(strict_types=1);

Auth::requireRole(['admin', 'manager']);
$id = (int) get('id', 0);
$db = Database::conn();

$stmt = $db->prepare('SELECT code FROM pm_plans WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    flash('error', 'ไม่พบแผน');
    redirect(page_url('pm_plans'));
}

try {
    $del = $db->prepare('DELETE FROM pm_plans WHERE id = ?');
    $del->bind_param('i', $id);
    $del->execute();
    $del->close();
    flash('success', 'ลบแผน ' . $row['code'] . ' แล้ว');
} catch (Throwable $e) {
    flash('error', 'ลบไม่ได้ มีใบงานอ้างอิงแผนนี้อยู่');
}
redirect(page_url('pm_plans'));
