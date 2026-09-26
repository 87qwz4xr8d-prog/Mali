<section class="login-wrap">
    <div class="login-card">
        <p class="login-kicker">บริษัท</p>
        <h1><?= e($appName ?? 'ปฏิทินกลางบริษัท') ?></h1>
        <p class="login-lead">พนักงานแต่ละแผนกบันทึกงานของตนเองในปฏิทินกลาง</p>
        <form id="login-form" class="mt-4" autocomplete="on">
            <div class="mb-3">
                <label class="form-label" for="username">ชื่อผู้ใช้</label>
                <input class="form-control" id="username" name="username" autocomplete="username" required maxlength="60">
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">รหัสผ่าน</label>
                <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">เข้าสู่ระบบ</button>
        </form>
    </div>
</section>
