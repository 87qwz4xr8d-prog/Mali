<section>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">ผู้ใช้</h1>
            <p class="text-secondary mb-0">แต่ละคนอยู่ได้แผนกเดียว และแก้ได้เฉพาะงานของตนเอง</p>
        </div>
        <button type="button" class="btn btn-primary" id="add-user">เพิ่มผู้ใช้</button>
    </div>
    <div class="table-responsive card border-0 shadow-sm">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>ชื่อ</th>
                    <th>ชื่อผู้ใช้</th>
                    <th>แผนก</th>
                    <th>บทบาท</th>
                    <th>สถานะ</th>
                    <th>งาน</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $row): ?>
                <tr>
                    <td><?= e($row['full_name']) ?></td>
                    <td><code><?= e($row['username']) ?></code></td>
                    <td>
                        <span class="dept-dot" style="background: <?= e($row['department_color']) ?>"></span>
                        <?= e($row['department_name']) ?>
                    </td>
                    <td><?= $row['role'] === 'admin' ? 'ผู้ดูแล' : 'พนักงาน' ?></td>
                    <td><?= (int) $row['active'] === 1 ? 'ใช้งาน' : 'ปิดใช้งาน' ?></td>
                    <td><?= e((string) $row['event_count']) ?></td>
                    <td class="text-end text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-primary edit-user" data-id="<?= e((string) $row['id']) ?>">แก้ไข</button>
                        <button type="button" class="btn btn-sm btn-outline-danger delete-user" data-id="<?= e((string) $row['id']) ?>" data-name="<?= e($row['full_name']) ?>">ลบ</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="modal fade" id="user-modal" tabindex="-1" aria-labelledby="user-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="user-form">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="user-modal-title">ผู้ใช้</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="user-id">
                <div class="mb-3">
                    <label class="form-label" for="user-full-name">ชื่อ-นามสกุล</label>
                    <input class="form-control" id="user-full-name" maxlength="120" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="user-username">ชื่อผู้ใช้</label>
                    <input class="form-control" id="user-username" maxlength="60" required autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="user-password">รหัสผ่าน</label>
                    <input class="form-control" id="user-password" type="password" minlength="8" autocomplete="new-password">
                    <div class="form-text" id="password-help">อย่างน้อย 8 ตัวอักษร</div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="user-role">บทบาท</label>
                        <select class="form-select" id="user-role">
                            <option value="employee">พนักงาน</option>
                            <option value="admin">ผู้ดูแล</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="user-active">สถานะ</label>
                        <select class="form-select" id="user-active">
                            <option value="1">ใช้งาน</option>
                            <option value="0">ปิดใช้งาน</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label" for="user-department">แผนก</label>
                    <select class="form-select" id="user-department" required>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?= e((string) $department['id']) ?>"><?= e($department['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ปิด</button>
                <button type="submit" class="btn btn-primary">บันทึก</button>
            </div>
        </form>
    </div>
</div>
<script id="admin-data" type="application/json"><?= \App\Http::jsonScript(['users' => $users, 'departments' => $departments]) ?></script>
