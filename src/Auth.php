<?php

declare(strict_types=1);

namespace App;

final class Auth
{
    public function __construct(private readonly Database $db)
    {
    }

    /** @return array<string, mixed>|null */
    public function user(): ?array
    {
        $id = $_SESSION['user_id'] ?? null;
        if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
            return null;
        }

        $user = $this->findById((int) $id);
        if ($user === null || (int) $user['active'] !== 1) {
            unset($_SESSION['user_id']);

            return null;
        }

        return $user;
    }

    /** @return array<string, mixed>|null */
    public function attempt(string $username, string $password): ?array
    {
        $row = $this->db->one(
            'SELECT u.id, u.username, u.password_hash, u.full_name, u.role, u.department_id, u.active,
                    d.name AS department_name, d.color AS department_color
             FROM users u
             INNER JOIN departments d ON d.id = u.department_id
             WHERE u.username = ?
             LIMIT 1',
            [$username]
        );

        if ($row === null || !password_verify($password, (string) $row['password_hash'])) {
            return null;
        }
        if ((int) $row['active'] !== 1) {
            return ['inactive' => true];
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $row['id'];
        Csrf::rotate();
        unset($row['password_hash']);

        return $row;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $row = $this->db->one(
            'SELECT u.id, u.username, u.full_name, u.role, u.department_id, u.active,
                    d.name AS department_name, d.color AS department_color
             FROM users u
             INNER JOIN departments d ON d.id = u.department_id
             WHERE u.id = ?
             LIMIT 1',
            [$id]
        );
        if ($row === null) {
            return null;
        }

        return $this->castUser($row);
    }

    /** @param array<string, mixed> $row
     *  @return array<string, mixed>
     */
    private function castUser(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['department_id'] = (int) $row['department_id'];
        $row['active'] = (int) $row['active'];
        $row['is_admin'] = $row['role'] === 'admin';

        return $row;
    }
}
