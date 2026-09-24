<?php

declare(strict_types=1);

final class Auth
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function attempt(string $username, string $password): bool
    {
        $db = Database::conn();
        $stmt = $db->prepare(
            'SELECT id, username, password_hash, full_name, email, role, is_active
             FROM users WHERE username = ? LIMIT 1'
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user || !(int) $user['is_active']) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        return true;
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('warning', 'กรุณาเข้าสู่ระบบก่อนใช้งาน');
            redirect('login.php');
        }
    }

    public static function requireRole(array $roles): void
    {
        self::requireLogin();
        $user = self::user();
        if (!in_array($user['role'] ?? '', $roles, true)) {
            flash('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
            redirect('index.php');
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function canManage(): bool
    {
        $role = self::user()['role'] ?? '';
        return in_array($role, ['admin', 'manager'], true);
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? '') === 'admin';
    }
}
