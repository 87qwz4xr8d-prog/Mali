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

function money(float|string|null $amount): string
{
    return number_format((float) $amount, 2, '.', ',');
}

function thai_month(string $yearMonth): string
{
    $months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
    ];
    [$y, $m] = array_map('intval', explode('-', $yearMonth));
    return ($months[$m] ?? $yearMonth) . ' ' . ($y + 543);
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

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), (string) $token)) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }
}

function base_path(string $path = ''): string
{
    $root = dirname(__DIR__);
    return $path === '' ? $root : $root . '/' . ltrim($path, '/');
}

function upload_path(string $file = ''): string
{
    $dir = base_path('public/uploads/receipts');
    return $file === '' ? $dir : $dir . '/' . ltrim($file, '/');
}

function status_badge(string $status): string
{
    return match ($status) {
        'draft' => 'secondary',
        'active' => 'success',
        'closed' => 'dark',
        default => 'light',
    };
}

function status_label(string $status): string
{
    return match ($status) {
        'draft' => 'ร่าง',
        'active' => 'กำลังใช้',
        'closed' => 'ปิดเดือนแล้ว',
        default => $status,
    };
}

function category_label(string $category): string
{
    return match ($category) {
        'salary' => 'เงินเดือน',
        'loan' => 'สินเชื่อ',
        'mother' => 'ให้คุณแม่',
        'personal' => 'ส่วนตัว',
        'other' => 'อื่นๆ',
        'savings' => 'ออม',
        'carry' => 'ยกยอด',
        default => $category,
    };
}

function payment_method_label(?string $method): string
{
    return match ((string) $method) {
        'cash' => 'เงินสด',
        'transfer' => 'โอนเงิน',
        'promptpay' => 'พร้อมเพย์',
        'card' => 'บัตรเครดิต/เดบิต',
        'other' => 'อื่นๆ',
        default => $method ?: '—',
    };
}

/** @return array<string, string> */
function payment_methods(): array
{
    return [
        'cash' => 'เงินสด',
        'transfer' => 'โอนเงิน',
        'promptpay' => 'พร้อมเพย์',
        'card' => 'บัตรเครดิต/เดบิต',
        'other' => 'อื่นๆ',
    ];
}
