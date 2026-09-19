<?php
ob_start();
?>
<div class="login-wrap">
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="brand-mark mx-auto mb-3" style="width:56px;height:56px;font-size:1.4rem;">M</div>
            <h2 class="fw-bold mb-1">Mali</h2>
            <p class="text-muted mb-0">โปรแกรมควบคุมรายจ่ายจากเงินเดือน</p>
        </div>
        <form method="post" action="index.php?page=login">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">ชื่อผู้ใช้</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" class="form-control" required autofocus value="admin">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">รหัสผ่าน</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" required>
                </div>
            </div>
            <button class="btn btn-mali w-100 py-2" type="submit">
                <i class="fa-solid fa-right-to-bracket me-1"></i> เข้าสู่ระบบ
            </button>
            <p class="small text-muted text-center mt-3 mb-0">ค่าเริ่มต้น: admin / admin123</p>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = 'เข้าสู่ระบบ';
$active = 'login';
require base_path('views/layout.php');
