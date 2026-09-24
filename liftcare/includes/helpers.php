<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $res = Database::conn()->query('SELECT setting_key, setting_value FROM settings');
            while ($row = $res->fetch_assoc()) {
                $cache[$row['setting_key']] = (string) $row['setting_value'];
            }
        } catch (Throwable $e) {
            return $default;
        }
    }
    return $cache[$key] ?? $default;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf(is_string($token) ? $token : null)) {
        flash('error', 'โทเค็นไม่ถูกต้อง กรุณาลองใหม่');
        redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
    }
}

function post(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $default;
}

function get(string $key, mixed $default = null): mixed
{
    return $_GET[$key] ?? $default;
}

function money(float|int|string|null $n): string
{
    return number_format((float) $n, 2);
}

function th_date(?string $date): string
{
    if (!$date || $date === '0000-00-00') {
        return '-';
    }
    $ts = strtotime($date);
    return $ts ? date('d/m/Y', $ts) : '-';
}

function th_datetime(?string $datetime): string
{
    if (!$datetime) {
        return '-';
    }
    $ts = strtotime($datetime);
    return $ts ? date('d/m/Y H:i', $ts) : '-';
}

function vehicle_status_badge(string $status): string
{
    $map = [
        'ready'       => ['พร้อมใช้', 'success'],
        'in_use'      => ['กำลังใช้งาน', 'primary'],
        'maintenance' => ['บำรุงรักษา', 'warning'],
        'repair'      => ['ซ่อม', 'danger'],
        'retired'     => ['ปลดระวาง', 'secondary'],
    ];
    [$label, $color] = $map[$status] ?? [$status, 'secondary'];
    return '<span class="badge text-bg-' . $color . '">' . e($label) . '</span>';
}

function wo_status_badge(string $status): string
{
    $map = [
        'open'          => ['เปิดใบงาน', 'secondary'],
        'assigned'      => ['มอบหมายแล้ว', 'info'],
        'in_progress'   => ['กำลังดำเนินการ', 'primary'],
        'waiting_parts' => ['รออะไหล่', 'warning'],
        'completed'     => ['เสร็จสิ้น', 'success'],
        'cancelled'     => ['ยกเลิก', 'dark'],
    ];
    [$label, $color] = $map[$status] ?? [$status, 'secondary'];
    return '<span class="badge text-bg-' . $color . '">' . e($label) . '</span>';
}

function wo_type_label(string $type): string
{
    return match ($type) {
        'pm' => 'PM (ป้องกัน)',
        'cm' => 'CM (แก้ไข)',
        'inspection' => 'ตรวจสภาพ',
        'upgrade' => 'ปรับปรุง',
        default => 'อื่น ๆ',
    };
}

function priority_badge(string $priority): string
{
    $map = [
        'low'    => ['ต่ำ', 'secondary'],
        'medium' => ['ปานกลาง', 'info'],
        'high'   => ['สูง', 'warning'],
        'urgent' => ['เร่งด่วน', 'danger'],
    ];
    [$label, $color] = $map[$priority] ?? [$priority, 'secondary'];
    return '<span class="badge text-bg-' . $color . '">' . e($label) . '</span>';
}

function lift_type_label(string $type): string
{
    return match ($type) {
        'boom' => 'บูมลิฟต์',
        'scissor' => 'กรรไกร',
        'vertical' => 'แนวตั้ง',
        'trailer' => 'เทรลเลอร์',
        'all' => 'ทุกรุ่น',
        default => 'อื่น ๆ',
    };
}

function role_label(string $role): string
{
    return match ($role) {
        'admin' => 'ผู้ดูแลระบบ',
        'manager' => 'หัวหน้าช่าง',
        'technician' => 'ช่างเทคนิค',
        'viewer' => 'ผู้ชม',
        default => $role,
    };
}

function next_wo_no(mysqli $db): string
{
    $prefix = setting('wo_prefix', 'WO');
    $year = date('Y');
    $like = $prefix . '-' . $year . '-%';
    $stmt = $db->prepare("SELECT wo_no FROM work_orders WHERE wo_no LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $seq = 1;
    if ($row && preg_match('/(\d+)$/', $row['wo_no'], $m)) {
        $seq = (int) $m[1] + 1;
    }
    return sprintf('%s-%s-%04d', $prefix, $year, $seq);
}

function page_url(string $page, array $params = []): string
{
    $params = array_merge(['page' => $page], $params);
    return 'index.php?' . http_build_query($params);
}

function is_active_page(string $page, string $current): string
{
    return $page === $current ? 'active' : '';
}

/**
 * bind_param ที่รองรับค่า null (mysqli ต้องการตัวแปรอ้างอิง)
 *
 * @param list<mixed> $params
 */
function stmt_bind(mysqli_stmt $stmt, string $types, array $params): void
{
    $refs = [];
    foreach ($params as $i => $value) {
        $refs[$i] = $params[$i];
    }
    $stmt->bind_param($types, ...$refs);
}
