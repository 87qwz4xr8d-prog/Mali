<section>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">แผนก</h1>
            <p class="text-secondary mb-0">สีของแผนกใช้แยกงานบนปฏิทิน</p>
        </div>
        <button type="button" class="btn btn-primary" id="add-department">เพิ่มแผนก</button>
    </div>
    <div class="table-responsive card border-0 shadow-sm">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>แผนก</th>
                    <th>ผู้ใช้</th>
                    <th>งาน</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($departments as $department): ?>
                <tr>
                    <td>
                        <span class="dept-dot" style="background: <?= e($department['color']) ?>"></span>
                        <?= e($department['name']) ?>
                    </td>
                    <td><?= e((string) $department['user_count']) ?></td>
                    <td><?= e((string) $department['event_count']) ?></td>
                    <td class="text-end text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-primary edit-department" data-id="<?= e((string) $department['id']) ?>">แก้ไข</button>
                        <button type="button" class="btn btn-sm btn-outline-danger delete-department" data-id="<?= e((string) $department['id']) ?>" data-name="<?= e($department['name']) ?>">ลบ</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="modal fade" id="department-modal" tabindex="-1" aria-labelledby="department-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="department-form">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="department-modal-title">แผนก</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="department-id">
                <div class="mb-3">
                    <label class="form-label" for="department-name">ชื่อแผนก</label>
                    <input class="form-control" id="department-name" maxlength="120" required>
                </div>
                <div>
                    <label class="form-label" for="department-color">สี</label>
                    <input class="form-control form-control-color" id="department-color" type="color" value="#0d6efd">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ปิด</button>
                <button type="submit" class="btn btn-primary">บันทึก</button>
            </div>
        </form>
    </div>
</div>
<script id="admin-data" type="application/json"><?= \App\Http::jsonScript(['departments' => $departments]) ?></script>
