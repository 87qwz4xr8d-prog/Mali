<?php

declare(strict_types=1);

$db = Database::conn();
$id = (int) get('id', 0);
$editing = $id > 0;

$row = [
    'code' => '', 'name' => '', 'lift_type' => 'boom', 'interval_days' => '30',
    'interval_hours' => '100', 'description' => '', 'is_active' => 1,
];
$items = [['item_name' => '', 'check_type' => 'visual', 'standard_value' => '']];

if ($editing) {
    $stmt = $db->prepare('SELECT * FROM pm_plans WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$found) {
        flash('error', 'ไม่พบแผน PM');
        redirect(page_url('pm_plans'));
    }
    $row = $found;
    $is = $db->prepare('SELECT * FROM pm_plan_items WHERE pm_plan_id = ? ORDER BY seq_no, id');
    $is->bind_param('i', $id);
    $is->execute();
    $items = $is->get_result()->fetch_all(MYSQLI_ASSOC) ?: $items;
    $is->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    Auth::requireRole(['admin', 'manager']);

    $data = [
        'code' => trim((string) post('code')),
        'name' => trim((string) post('name')),
        'lift_type' => (string) post('lift_type', 'boom'),
        'interval_days' => post('interval_days') !== '' ? (int) post('interval_days') : null,
        'interval_hours' => post('interval_hours') !== '' ? (float) post('interval_hours') : null,
        'description' => trim((string) post('description')),
        'is_active' => (int) post('is_active', 1),
    ];

    $itemNames = $_POST['item_name'] ?? [];
    $itemTypes = $_POST['check_type'] ?? [];
    $itemStds = $_POST['standard_value'] ?? [];

    if ($data['code'] === '' || $data['name'] === '') {
        flash('error', 'กรุณากรอกรหัสและชื่อแผน');
        redirect(page_url('pm_plan_form', $editing ? ['id' => $id] : []));
    }

    if ($editing) {
        $stmt = $db->prepare('UPDATE pm_plans SET code=?, name=?, lift_type=?, interval_days=?, interval_hours=?, description=?, is_active=? WHERE id=?');
        $stmt->bind_param('sssidsii', $data['code'], $data['name'], $data['lift_type'], $data['interval_days'], $data['interval_hours'], $data['description'], $data['is_active'], $id);
        $stmt->execute();
        $stmt->close();
        $db->query('DELETE FROM pm_plan_items WHERE pm_plan_id = ' . (int) $id);
        $planId = $id;
    } else {
        $stmt = $db->prepare('INSERT INTO pm_plans (code, name, lift_type, interval_days, interval_hours, description, is_active) VALUES (?,?,?,?,?,?,?)');
        $stmt->bind_param('sssidsi', $data['code'], $data['name'], $data['lift_type'], $data['interval_days'], $data['interval_hours'], $data['description'], $data['is_active']);
        $stmt->execute();
        $planId = (int) $stmt->insert_id;
        $stmt->close();
    }

    $ins = $db->prepare('INSERT INTO pm_plan_items (pm_plan_id, seq_no, item_name, check_type, standard_value) VALUES (?,?,?,?,?)');
    $seq = 1;
    foreach ($itemNames as $idx => $name) {
        $name = trim((string) $name);
        if ($name === '') {
            continue;
        }
        $ctype = (string) ($itemTypes[$idx] ?? 'visual');
        $std = trim((string) ($itemStds[$idx] ?? ''));
        $ins->bind_param('iisss', $planId, $seq, $name, $ctype, $std);
        $ins->execute();
        $seq++;
    }
    $ins->close();

    flash('success', 'บันทึกแผน PM เรียบร้อย');
    redirect(page_url('pm_plans'));
}

$pageTitle = $editing ? 'แก้ไขแผน PM' : 'เพิ่มแผน PM';
$activePage = 'pm_plans';
require dirname(__DIR__) . '/includes/layout/header.php';
?>

<div class="page-toolbar">
    <p class="page-lead mb-0">กำหนด checklist และรอบการบำรุง</p>
    <a href="<?= e(page_url('pm_plans')) ?>" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> กลับ</a>
</div>

<div class="panel">
    <div class="panel-body">
        <form method="post" id="pmForm" data-confirm-submit="ยืนยันบันทึกแผน PM?">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">รหัสแผน *</label>
                    <input type="text" name="code" class="form-control" required value="<?= e((string)$row['code']) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">ชื่อแผน *</label>
                    <input type="text" name="name" class="form-control" required value="<?= e((string)$row['name']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">ประเภทเครื่อง</label>
                    <select name="lift_type" class="form-select">
                        <?php foreach (['all','boom','scissor','vertical','trailer','other'] as $t): ?>
                            <option value="<?= $t ?>" <?= $row['lift_type']===$t?'selected':'' ?>><?= e(lift_type_label($t)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">รอบ (วัน)</label>
                    <input type="number" name="interval_days" class="form-control" value="<?= e((string)$row['interval_days']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">รอบ (ชั่วโมง)</label>
                    <input type="number" step="0.1" name="interval_hours" class="form-control" value="<?= e((string)$row['interval_hours']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">สถานะ</label>
                    <select name="is_active" class="form-select">
                        <option value="1" <?= (int)$row['is_active']===1?'selected':'' ?>>ใช้งาน</option>
                        <option value="0" <?= (int)$row['is_active']===0?'selected':'' ?>>ปิด</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">รายละเอียด</label>
                    <textarea name="description" class="form-control" rows="2"><?= e((string)$row['description']) ?></textarea>
                </div>
            </div>

            <hr class="my-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h3 class="h6 fw-bold mb-0">รายการตรวจ</h3>
                <button type="button" class="btn btn-sm btn-outline-steel" id="addItemBtn"><i class="fa-solid fa-plus"></i> เพิ่มแถว</button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm" id="itemsTable">
                    <thead><tr><th>รายการ</th><th style="width:160px">ประเภท</th><th>ค่ามาตรฐาน</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><input type="text" name="item_name[]" class="form-control form-control-sm" value="<?= e((string)($it['item_name'] ?? '')) ?>"></td>
                            <td>
                                <select name="check_type[]" class="form-select form-select-sm">
                                    <?php foreach (['visual'=>'สายตา','measure'=>'วัดค่า','test'=>'ทดสอบ','replace'=>'เปลี่ยน','lubricate'=>'หล่อลื่น','other'=>'อื่น'] as $k=>$lab): ?>
                                        <option value="<?= $k ?>" <?= ($it['check_type'] ?? '')===$k?'selected':'' ?>><?= e($lab) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="standard_value[]" class="form-control form-control-sm" value="<?= e((string)($it['standard_value'] ?? '')) ?>"></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fa-solid fa-xmark"></i></button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-accent"><i class="fa-solid fa-floppy-disk"></i> บันทึก</button>
                <a href="<?= e(page_url('pm_plans')) ?>" class="btn btn-outline-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('addItemBtn')?.addEventListener('click', () => {
  const tbody = document.querySelector('#itemsTable tbody');
  const tr = document.createElement('tr');
  tr.innerHTML = `<td><input type="text" name="item_name[]" class="form-control form-control-sm"></td>
    <td><select name="check_type[]" class="form-select form-select-sm">
      <option value="visual">สายตา</option><option value="measure">วัดค่า</option>
      <option value="test">ทดสอบ</option><option value="replace">เปลี่ยน</option>
      <option value="lubricate">หล่อลื่น</option><option value="other">อื่น</option>
    </select></td>
    <td><input type="text" name="standard_value[]" class="form-control form-control-sm"></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fa-solid fa-xmark"></i></button></td>`;
  tbody.appendChild(tr);
});
document.querySelector('#itemsTable')?.addEventListener('click', (e) => {
  const btn = e.target.closest('.remove-row');
  if (!btn) return;
  const rows = document.querySelectorAll('#itemsTable tbody tr');
  if (rows.length > 1) btn.closest('tr').remove();
});
</script>

<?php require dirname(__DIR__) . '/includes/layout/footer.php'; ?>
