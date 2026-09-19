<?php

declare(strict_types=1);

final class Auth
{
    public static function attempt(string $username, string $password): bool
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'display_name' => $user['display_name'],
        ];

        return true;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('warning', 'กรุณาเข้าสู่ระบบก่อนใช้งาน');
            redirect('index.php?page=login');
        }
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
    }
}
