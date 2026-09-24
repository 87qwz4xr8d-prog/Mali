<?php

declare(strict_types=1);

$db = Database::conn();
$id = (int) get('id', 0);
$editing = $id > 0;
$user = Auth::user();

$row = [
    'wo_no' => '', 'vehicle_id' => '', 'customer_id' => '', 'pm_plan_id' => '',
    'wo_type' => 'cm', 'priority' => 'medium', 'status' => 'open',
    'title' => '', 'problem_desc' => '', 'solution_desc' => '',
    'requested_by' => $user['full_name'] ?? '', 'assigned_to' => '',
    'request_date' => date('Y-m-d'), 'due_date' => '',
    'hour_meter' => '', 'labor_cost' => '0', 'parts_cost' => '0', 'other_cost' => '0',
];

if ($editing) {
    $stmt = $db->prepare('SELECT * FROM work_orders WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$found) {
        flash('error', 'ไม่พบใบงาน');
        redirect(page_url('work_orders'));
    }
    $row = $found;
} else {
    $row['wo_no'] = next_wo_no($db);
}

$vehicles = $db->query("SELECT id, asset_code, brand, model, hour_meter FROM vehicles WHERE status <> 'retired' ORDER BY asset_code")->fetch_all(MYSQLI_ASSOC);
$customers = $db->query('SELECT id, code, name FROM customers WHERE is_active = 1 ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$techs = $db->query("SELECT id, full_name FROM users WHERE is_active = 1 AND role IN ('technician','manager','admin') ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);
$plans = $db->query('SELECT id, code, name FROM pm_plans WHERE is_active = 1 ORDER BY code')->fetch_all(MYSQLI_ASSOC);

$checklist = [];
if ($editing) {
    $cs = $db->prepare('SELECT * FROM work_order_checklist WHERE work_order_id = ? ORDER BY id');
    $cs->bind_param('i', $id);
    $cs->execute();
    $checklist = $cs->get_result()->fetch_all(MYSQLI_ASSOC);
    $cs->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $data = [
        'wo_no' => trim((string) post('wo_no')),
        'vehicle_id' => (int) post('vehicle_id'),
        'customer_id' => post('customer_id') !== '' ? (int) post('customer_id') : null,
        'pm_plan_id' => post('pm_plan_id') !== '' ? (int) post('pm_plan_id') : null,
        'wo_type' => (string) post('wo_type', 'cm'),
        'priority' => (string) post('priority', 'medium'),
        'status' => (string) post('status', 'open'),
        'title' => trim((string) post('title')),
        'problem_desc' => trim((string) post('problem_desc')),
        'solution_desc' => trim((string) post('solution_desc')),
        'requested_by' => trim((string) post('requested_by')),
        'assigned_to' => post('assigned_to') !== '' ? (int) post('assigned_to') : null,
        'request_date' => (string) post('request_date', date('Y-m-d')),
        'due_date' => post('due_date') ?: null,
        'hour_meter' => post('hour_meter') !== '' ? (float) post('hour_meter') : null,
        'labor_cost' => (float) post('labor_cost', 0),
        'parts_cost' => (float) post('parts_cost', 0),
        'other_cost' => (float) post('other_cost', 0),
    ];

    if ($data['vehicle_id'] <= 0 || $data['title'] === '') {
        flash('error', 'กรุณาเลือกรถและระบุหัวข้อใบงาน');
        redirect(page_url('work_order_form', $editing ? ['id' => $id] : []));
    }

    $completedAt = $data['status'] === 'completed' ? date('Y-m-d H:i:s') : null;

    if ($editing) {
        $sql = "UPDATE work_orders SET vehicle_id=?, customer_id=?, pm_plan_id=?, wo_type=?, priority=?, status=?,
                title=?, problem_desc=?, solution_desc=?, requested_by=?, assigned_to=?, request_date=?, due_date=?,
                hour_meter=?, labor_cost=?, parts_cost=?, other_cost=?, completed_at=IF(?='completed', COALESCE(completed_at, NOW()), NULL)
                WHERE id=?";
        $stmt = $db->prepare($sql);
        $status = $data['status'];
        $stmt->bind_param(
            'iiisssssssissddddsi',
            $data['vehicle_id'], $data['customer_id'], $data['pm_plan_id'], $data['wo_type'], $data['priority'], $data['status'],
            $data['title'], $data['problem_desc'], $data['solution_desc'], $data['requested_by'], $data['assigned_to'],
            $data['request_date'], $data['due_date'], $data['hour_meter'], $data['labor_cost'], $data['parts_cost'],
            $data['other_cost'], $status, $id
        );
        $stmt->execute();
        $stmt->close();

        // อัปเดต checklist results
        if (!empty($_POST['check_id']) && is_array($_POST['check_id'])) {
            foreach ($_POST['check_id'] as $idx => $cid) {
                $cid = (int) $cid;
                $result = (string) ($_POST['check_result'][$idx] ?? 'pending');
                $remark = trim((string) ($_POST['check_remark'][$idx] ?? ''));
                $up = $db->prepare('UPDATE work_order_checklist SET result=?, remark=? WHERE id=? AND work_order_id=?');
                $up->bind_param('ssii', $result, $remark, $cid, $id);
                $up->execute();
                $up->close();
            }
        }

        // อัปเดตชั่วโมงรถเมื่อปิดงาน
        if ($data['status'] === 'completed' && $data['hour_meter'] !== null) {
            $uv = $db->prepare('UPDATE vehicles SET hour_meter = GREATEST(hour_meter, ?) WHERE id = ?');
            $uv->bind_param('di', $data['hour_meter'], $data['vehicle_id']);
            $uv->execute();
            $uv->close();
        }

        flash('success', 'บันทึกใบงานเรียบร้อย');
    } else {
        $createdBy = (int) ($user['id'] ?? 0);
        $sql = "INSERT INTO work_orders
            (wo_no, vehicle_id, customer_id, pm_plan_id, wo_type, priority, status, title, problem_desc, solution_desc,
             requested_by, assigned_to, request_date, due_date, hour_meter, labor_cost, parts_cost, other_cost, created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param(
            'siiisssssssissddddi',
            $data['wo_no'], $data['vehicle_id'], $data['customer_id'], $data['pm_plan_id'], $data['wo_type'],
            $data['priority'], $data['status'], $data['title'], $data['problem_desc'], $data['solution_desc'],
            $data['requested_by'], $data['assigned_to'], $data['request_date'], $data['due_date'],
            $data['hour_meter'], $data['labor_cost'], $data['parts_cost'], $data['other_cost'], $createdBy
        );
        $stmt->execute();
        $newId = (int) $stmt->insert_id;
        $stmt->close();

        // คัดลอก checklist จากแผน PM
        if ($data['pm_plan_id']) {
            $items = $db->prepare('SELECT item_name FROM pm_plan_items WHERE pm_plan_id = ? ORDER BY seq_no');
            $items->bind_param('i', $data['pm_plan_id']);
            $items->execute();
            $itemRows = $items->get_result()->fetch_all(MYSQLI_ASSOC);
            $items->close();
            $ins = $db->prepare('INSERT INTO work_order_checklist (work_order_id, item_name, result) VALUES (?,?,?)');
            $pending = 'pending';
            foreach ($itemRows as $it) {
                $name = $it['item_name'];
                $ins->bind_param('iss', $newId, $name, $pending);
                $ins->execute();
            }
            $ins->close();
        }

        flash('success', 'เปิดใบงาน ' . $data['wo_no'] . ' สำเร็จ');
    }
    redirect(page_url('work_orders'));
}

$pageTitle = $editing ? 'แก้ไขใบงาน' : 'เปิดใบงานใหม่';
$activePage = 'work_orders';
require dirname(__DIR__) . '/includes/layout/header.php';
?>

<div class="page-toolbar">
    <p class="page-lead mb-0">บันทึกรายละเอียดงานซ่อม / บำรุงรักษา</p>
    <a href="<?= e(page_url('work_orders')) ?>" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> กลับ</a>
</div>

<div class="panel">
    <div class="panel-body">
        <form method="post" data-confirm-submit="ยืนยันบันทึกใบงาน?">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">เลขที่ใบงาน</label>
                    <input type="text" name="wo_no" class="form-control" <?= $editing ? 'readonly' : '' ?> required value="<?= e((string)$row['wo_no']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">ประเภทงาน</label>
                    <select name="wo_type" class="form-select">
                        <?php foreach (['pm','cm','inspection','upgrade','other'] as $t): ?>
                            <option value="<?= $t ?>" <?= $row['wo_type']===$t?'selected':'' ?>><?= e(wo_type_label($t)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">ความสำคัญ</label>
                    <select name="priority" class="form-select">
                        <?php foreach (['low'=>'ต่ำ','medium'=>'ปานกลาง','high'=>'สูง','urgent'=>'เร่งด่วน'] as $k=>$lab): ?>
                            <option value="<?= $k ?>" <?= $row['priority']===$k?'selected':'' ?>><?= e($lab) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">สถานะ</label>
                    <select name="status" class="form-select">
                        <?php foreach (['open'=>'เปิดใบงาน','assigned'=>'มอบหมายแล้ว','in_progress'=>'กำลังดำเนินการ','waiting_parts'=>'รออะไหล่','completed'=>'เสร็จสิ้น','cancelled'=>'ยกเลิก'] as $k=>$lab): ?>
                            <option value="<?= $k ?>" <?= $row['status']===$k?'selected':'' ?>><?= e($lab) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">รถกระเช้า *</label>
                    <select name="vehicle_id" class="form-select" required>
                        <option value="">— เลือก —</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?= (int)$v['id'] ?>" <?= (string)$row['vehicle_id']===(string)$v['id']?'selected':'' ?>>
                                <?= e($v['asset_code'].' — '.$v['brand'].' '.$v['model'].' ('.$v['hour_meter'].' ชม.)') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">ลูกค้า / ไซต์</label>
                    <select name="customer_id" class="form-select">
                        <option value="">— ไม่ระบุ —</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= (string)$row['customer_id']===(string)$c['id']?'selected':'' ?>><?= e($c['code'].' — '.$c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">แผน PM (ถ้ามี)</label>
                    <select name="pm_plan_id" class="form-select">
                        <option value="">— ไม่ใช้แผน —</option>
                        <?php foreach ($plans as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= (string)$row['pm_plan_id']===(string)$p['id']?'selected':'' ?>><?= e($p['code'].' — '.$p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">เมื่อเปิดใบงานใหม่ ระบบจะคัดลอกรายการตรวจจากแผนอัตโนมัติ</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">มอบหมายช่าง</label>
                    <select name="assigned_to" class="form-select">
                        <option value="">— ยังไม่มอบหมาย —</option>
                        <?php foreach ($techs as $t): ?>
                            <option value="<?= (int)$t['id'] ?>" <?= (string)$row['assigned_to']===(string)$t['id']?'selected':'' ?>><?= e($t['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">หัวข้อ *</label>
                    <input type="text" name="title" class="form-control" required value="<?= e((string)$row['title']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">อาการ / รายละเอียดปัญหา</label>
                    <textarea name="problem_desc" class="form-control" rows="3"><?= e((string)$row['problem_desc']) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">วิธีแก้ไข / สรุปงาน</label>
                    <textarea name="solution_desc" class="form-control" rows="3"><?= e((string)$row['solution_desc']) ?></textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label">ผู้แจ้ง</label>
                    <input type="text" name="requested_by" class="form-control" value="<?= e((string)$row['requested_by']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">วันที่แจ้ง</label>
                    <input type="date" name="request_date" class="form-control" required value="<?= e((string)$row['request_date']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">กำหนดเสร็จ</label>
                    <input type="date" name="due_date" class="form-control" value="<?= e((string)$row['due_date']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">ชั่วโมงมิเตอร์</label>
                    <input type="number" step="0.1" name="hour_meter" class="form-control" value="<?= e((string)$row['hour_meter']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">ค่าแรง</label>
                    <input type="number" step="0.01" name="labor_cost" class="form-control" value="<?= e((string)$row['labor_cost']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">ค่าอะไหล่</label>
                    <input type="number" step="0.01" name="parts_cost" class="form-control" value="<?= e((string)$row['parts_cost']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">ค่าใช้จ่ายอื่น</label>
                    <input type="number" step="0.01" name="other_cost" class="form-control" value="<?= e((string)$row['other_cost']) ?>">
                </div>
            </div>

            <?php if ($checklist): ?>
                <hr class="my-4">
                <h3 class="h6 fw-bold mb-3"><i class="fa-solid fa-list-check me-1"></i> รายการตรวจ (Checklist)</h3>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>#</th><th>รายการ</th><th>ผล</th><th>หมายเหตุ</th></tr></thead>
                        <tbody>
                        <?php foreach ($checklist as $i => $c): ?>
                            <tr>
                                <td><?= $i + 1 ?>
                                    <input type="hidden" name="check_id[]" value="<?= (int)$c['id'] ?>">
                                </td>
                                <td><?= e($c['item_name']) ?></td>
                                <td style="min-width:140px">
                                    <select name="check_result[]" class="form-select form-select-sm">
                                        <?php foreach (['pending'=>'รอตรวจ','pass'=>'ผ่าน','fail'=>'ไม่ผ่าน','na'=>'N/A'] as $k=>$lab): ?>
                                            <option value="<?= $k ?>" <?= $c['result']===$k?'selected':'' ?>><?= e($lab) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="text" name="check_remark[]" class="form-control form-control-sm" value="<?= e((string)$c['remark']) ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-accent"><i class="fa-solid fa-floppy-disk"></i> บันทึก</button>
                <a href="<?= e(page_url('work_orders')) ?>" class="btn btn-outline-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/layout/footer.php'; ?>
