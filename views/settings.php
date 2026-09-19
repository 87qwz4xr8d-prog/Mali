<?php
/** @var array $settings */
ob_start();
?>
<h2 class="page-title">ตั้งค่า</h2>
<p class="page-sub">เงินเดือน กรอบงบคงที่ และอัตราออม</p>

<div class="panel">
    <form method="post" action="index.php?page=settings&action=save">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">ชื่อแอป</label>
                <input type="text" name="app_name" class="form-control" value="<?= e($settings['app_name'] ?? 'Mali') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">เงินเดือน (บาท)</label>
                <input type="number" step="0.01" name="salary_amount" class="form-control" required
                       value="<?= e($settings['salary_amount'] ?? '31364') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">อัตราออม (%)</label>
                <input type="number" step="0.01" name="savings_rate" class="form-control" required
                       value="<?= e($settings['savings_rate'] ?? '5') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">งบส่วนตัว/เดือน</label>
                <input type="number" step="0.01" name="personal_budget" class="form-control" required
                       value="<?= e($settings['personal_budget'] ?? '5000') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">ให้คุณแม่/เดือน</label>
                <input type="number" step="0.01" name="mother_budget" class="form-control" required
                       value="<?= e($settings['mother_budget'] ?? '10000') ?>">
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-mali" type="submit"><i class="fa-solid fa-save me-1"></i> บันทึกการตั้งค่า</button>
        </div>
    </form>
</div>

<div class="panel">
    <h5>สรุปกรอบงบเริ่มต้น</h5>
    <ul class="mb-0">
        <li>สินเชื่อรวมคาดจ่าย: 2,500 + 1,500 + 1,500 + 5,000 + 5,000 = <strong>15,500</strong> บาท</li>
        <li>ส่วนตัว 5,000 + คุณแม่ 10,000 = <strong>15,000</strong> บาท</li>
        <li>รวมผูกพัน <strong>30,500</strong> จากเงินเดือน <strong>31,364</strong> → คงเหลือก่อนออมประมาณ <strong>864</strong> บาท</li>
    </ul>
</div>
<?php
$content = ob_get_clean();
$title = 'ตั้งค่า';
$active = 'settings';
require base_path('views/layout.php');
