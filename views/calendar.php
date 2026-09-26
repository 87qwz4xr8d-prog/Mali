<?php
$googleReady = ($bootSwal ?? null) === null;
?>
<section id="calendar-app">
    <div class="cal-toolbar">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <h1 class="cal-title" id="cal-title">ปฏิทิน</h1>
            <?php if (!$googleReady): ?>
                <span class="badge rounded-pill text-bg-warning">ยังไม่ซิงก์ Google</span>
            <?php endif; ?>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="btn-group" role="group" aria-label="มุมมอง">
                <button type="button" class="btn btn-outline-secondary" id="view-month">เดือน</button>
                <button type="button" class="btn btn-outline-secondary" id="view-week">สัปดาห์</button>
            </div>
            <button type="button" class="btn btn-outline-secondary" id="cal-today">วันนี้</button>
            <div class="btn-group" role="group" aria-label="เลื่อนช่วงเวลา">
                <button type="button" class="btn btn-outline-secondary" id="cal-prev" aria-label="ก่อนหน้า">‹</button>
                <button type="button" class="btn btn-outline-secondary" id="cal-next" aria-label="ถัดไป">›</button>
            </div>
            <label class="visually-hidden" for="department-filter">กรองแผนก</label>
            <select class="form-select cal-filter" id="department-filter">
                <option value="">ทุกแผนก</option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?= e((string) $department['id']) ?>"><?= e($department['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="btn btn-primary" id="add-event">เพิ่มงาน</button>
        </div>
    </div>
    <div class="dept-legend" id="dept-legend"></div>
    <p class="cal-count" id="cal-count"></p>
    <div id="cal-grid" class="cal-grid" aria-live="polite"></div>
</section>

<div class="modal fade" id="event-modal" tabindex="-1" aria-labelledby="event-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="event-form">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="event-modal-title">งาน</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="event-id">
                <div class="mb-3">
                    <label class="form-label" for="event-title">หัวข้องาน</label>
                    <input class="form-control" id="event-title" maxlength="200" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="event-description">รายละเอียด</label>
                    <textarea class="form-control" id="event-description" rows="3" maxlength="4000"></textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="event-start">เริ่ม</label>
                        <input class="form-control" id="event-start" type="datetime-local" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="event-end">สิ้นสุด</label>
                        <input class="form-control" id="event-end" type="datetime-local" required>
                    </div>
                </div>
                <div class="row g-3 mt-0">
                    <div class="col-md-6">
                        <label class="form-label" for="event-department">แผนก</label>
                        <input class="form-control" id="event-department" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="event-owner">ผู้บันทึก</label>
                        <input class="form-control" id="event-owner" readonly>
                    </div>
                </div>
                <p class="form-text mt-3 mb-0" id="event-lock-note" hidden>ดูได้อย่างเดียว แก้ไขหรือลบได้เฉพาะเจ้าของงาน</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger me-auto" id="event-delete" hidden>ลบ</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ปิด</button>
                <button type="submit" class="btn btn-primary" id="event-save">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script id="me-data" type="application/json"><?= \App\Http::jsonScript([
    'id' => (int) $user['id'],
    'full_name' => (string) $user['full_name'],
    'department_id' => (int) $user['department_id'],
    'department_name' => (string) $user['department_name'],
    'department_color' => (string) $user['department_color'],
]) ?></script>
<script id="dept-data" type="application/json"><?= \App\Http::jsonScript($departments) ?></script>
