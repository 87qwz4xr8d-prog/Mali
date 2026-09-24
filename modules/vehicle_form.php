<?php

declare(strict_types=1);

$db = Database::conn();
$id = (int) get('id', 0);
$editing = $id > 0;
$row = [
    'asset_code' => '', 'plate_no' => '', 'brand' => '', 'model' => '', 'serial_no' => '',
    'lift_type' => 'boom', 'power_type' => 'electric', 'max_height_m' => '', 'capacity_kg' => '',
    'year_made' => '', 'hour_meter' => '0', 'status' => 'ready', 'customer_id' => '',
    'location' => '', 'purchase_date' => '', 'warranty_expire' => '', 'next_pm_date' => '',
    'next_pm_hours' => '', 'notes' => '',
];

if ($editing) {
    $stmt = $db->prepare('SELECT * FROM vehicles WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$found) {
        flash('error', 'ไม่พบข้อมูลรถ');
        redirect(page_url('vehicles'));
    }
    $row = $found;
}

$customers = $db->query('SELECT id, code, name FROM customers WHERE is_active = 1 ORDER BY name')->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    if (!Auth::canManage() && !$editing) {
        flash('error', 'ไม่มีสิทธิ์เพิ่มข้อมูล');
        redirect(page_url('vehicles'));
    }

    $data = [
        'asset_code' => trim((string) post('asset_code')),
        'plate_no' => trim((string) post('plate_no')),
        'brand' => trim((string) post('brand')),
        'model' => trim((string) post('model')),
        'serial_no' => trim((string) post('serial_no')),
        'lift_type' => (string) post('lift_type', 'boom'),
        'power_type' => (string) post('power_type', 'electric'),
        'max_height_m' => post('max_height_m') !== '' ? (float) post('max_height_m') : null,
        'capacity_kg' => post('capacity_kg') !== '' ? (float) post('capacity_kg') : null,
        'year_made' => post('year_made') !== '' ? (int) post('year_made') : null,
        'hour_meter' => (float) post('hour_meter', 0),
        'status' => (string) post('status', 'ready'),
        'customer_id' => post('customer_id') !== '' ? (int) post('customer_id') : null,
        'location' => trim((string) post('location')),
        'purchase_date' => post('purchase_date') ?: null,
        'warranty_expire' => post('warranty_expire') ?: null,
        'next_pm_date' => post('next_pm_date') ?: null,
        'next_pm_hours' => post('next_pm_hours') !== '' ? (float) post('next_pm_hours') : null,
        'notes' => trim((string) post('notes')),
    ];

    if ($data['asset_code'] === '' || $data['brand'] === '' || $data['model'] === '') {
        flash('error', 'กรุณากรอกรหัสรถ ยี่ห้อ และรุ่น');
        redirect(page_url('vehicle_form', $editing ? ['id' => $id] : []));
    }

    if ($editing) {
        $sql = "UPDATE vehicles SET asset_code=?, plate_no=?, brand=?, model=?, serial_no=?, lift_type=?, power_type=?,
                max_height_m=?, capacity_kg=?, year_made=?, hour_meter=?, status=?, customer_id=?, location=?,
                purchase_date=?, warranty_expire=?, next_pm_date=?, next_pm_hours=?, notes=? WHERE id=?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param(
            'sssssssddidsissssdsi',
            $data['asset_code'], $data['plate_no'], $data['brand'], $data['model'], $data['serial_no'],
            $data['lift_type'], $data['power_type'], $data['max_height_m'], $data['capacity_kg'],
            $data['year_made'], $data['hour_meter'], $data['status'], $data['customer_id'], $data['location'],
            $data['purchase_date'], $data['warranty_expire'], $data['next_pm_date'], $data['next_pm_hours'],
            $data['notes'], $id
        );
        $stmt->execute();
        $stmt->close();
        flash('success', 'บันทึกข้อมูลรถเรียบร้อย');
    } else {
        $sql = "INSERT INTO vehicles
            (asset_code, plate_no, brand, model, serial_no, lift_type, power_type, max_height_m, capacity_kg,
             year_made, hour_meter, status, customer_id, location, purchase_date, warranty_expire, next_pm_date, next_pm_hours, notes)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param(
            'sssssssddidsissssds',
            $data['asset_code'], $data['plate_no'], $data['brand'], $data['model'], $data['serial_no'],
            $data['lift_type'], $data['power_type'], $data['max_height_m'], $data['capacity_kg'],
            $data['year_made'], $data['hour_meter'], $data['status'], $data['customer_id'], $data['location'],
            $data['purchase_date'], $data['warranty_expire'], $data['next_pm_date'], $data['next_pm_hours'],
            $data['notes']
        );
        $stmt->execute();
        $stmt->close();
        flash('success', 'เพิ่มรถกระเช้าเรียบร้อย');
    }
    redirect(page_url('vehicles'));
}

$pageTitle = $editing ? 'แก้ไขรถกระเช้า' : 'เพิ่มรถกระเช้า';
$activePage = 'vehicles';
require dirname(__DIR__) . '/includes/layout/header.php';
?>

<div class="page-toolbar">
    <p class="page-lead mb-0"><?= $editing ? 'แก้ไขข้อมูลทรัพย์สิน' : 'ลงทะเบียนรถกระเช้าไฟฟ้าใหม่' ?></p>
    <a href="<?= e(page_url('vehicles')) ?>" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> กลับ</a>
</div>

<div class="panel">
    <div class="panel-body">
        <form method="post" data-confirm-submit="ยืนยันบันทึกข้อมูลรถ?">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">รหัสทรัพย์สิน *</label>
                    <input type="text" name="asset_code" class="form-control" required value="<?= e((string) $row['asset_code']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">เลขทะเบียน/ป้าย</label>
                    <input type="text" name="plate_no" class="form-control" value="<?= e((string) $row['plate_no']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">ยี่ห้อ *</label>
                    <input type="text" name="brand" class="form-control" required value="<?= e((string) $row['brand']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">รุ่น *</label>
                    <input type="text" name="model" class="form-control" required value="<?= e((string) $row['model']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Serial No.</label>
                    <input type="text" name="serial_no" class="form-control" value="<?= e((string) $row['serial_no']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">ประเภท</label>
                    <select name="lift_type" class="form-select">
                        <?php foreach (['boom','scissor','vertical','trailer','other'] as $t): ?>
                            <option value="<?= $t ?>" <?= $row['lift_type'] === $t ? 'selected' : '' ?>><?= e(lift_type_label($t)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">ระบบขับเคลื่อน</label>
                    <select name="power_type" class="form-select">
                        <?php foreach (['electric'=>'ไฟฟ้า','hybrid'=>'ไฮบริด','diesel'=>'ดีเซล','other'=>'อื่น ๆ'] as $k=>$lab): ?>
                            <option value="<?= $k ?>" <?= $row['power_type'] === $k ? 'selected' : '' ?>><?= e($lab) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">สถานะ</label>
                    <select name="status" class="form-select">
                        <?php foreach (['ready'=>'พร้อมใช้','in_use'=>'กำลังใช้งาน','maintenance'=>'บำรุงรักษา','repair'=>'ซ่อม','retired'=>'ปลดระวาง'] as $k=>$lab): ?>
                            <option value="<?= $k ?>" <?= $row['status'] === $k ? 'selected' : '' ?>><?= e($lab) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">ความสูงสูงสุด (ม.)</label>
                    <input type="number" step="0.01" name="max_height_m" class="form-control" value="<?= e((string) $row['max_height_m']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">พิกัดน้ำหนัก (กก.)</label>
                    <input type="number" step="0.01" name="capacity_kg" class="form-control" value="<?= e((string) $row['capacity_kg']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">ปีผลิต</label>
                    <input type="number" name="year_made" class="form-control" value="<?= e((string) $row['year_made']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">ชั่วโมงใช้งาน</label>
                    <input type="number" step="0.1" name="hour_meter" class="form-control" value="<?= e((string) $row['hour_meter']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">ลูกค้า / ไซต์งาน</label>
                    <select name="customer_id" class="form-select">
                        <option value="">— ไม่ระบุ —</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= (string) $row['customer_id'] === (string) $c['id'] ? 'selected' : '' ?>>
                                <?= e($c['code'] . ' — ' . $c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">ตำแหน่งปัจจุบัน</label>
                    <input type="text" name="location" class="form-control" value="<?= e((string) $row['location']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">วันที่ซื้อ</label>
                    <input type="date" name="purchase_date" class="form-control" value="<?= e((string) $row['purchase_date']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">หมดประกัน</label>
                    <input type="date" name="warranty_expire" class="form-control" value="<?= e((string) $row['warranty_expire']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">วัน PM ถัดไป</label>
                    <input type="date" name="next_pm_date" class="form-control" value="<?= e((string) $row['next_pm_date']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">ชม. PM ถัดไป</label>
                    <input type="number" step="0.1" name="next_pm_hours" class="form-control" value="<?= e((string) $row['next_pm_hours']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">หมายเหตุ</label>
                    <textarea name="notes" class="form-control" rows="3"><?= e((string) $row['notes']) ?></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-accent"><i class="fa-solid fa-floppy-disk"></i> บันทึก</button>
                <a href="<?= e(page_url('vehicles')) ?>" class="btn btn-outline-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/layout/footer.php'; ?>
