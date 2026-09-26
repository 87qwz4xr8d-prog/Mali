<?php

declare(strict_types=1);

namespace App;

use mysqli_sql_exception;

final class AdminService
{
    public function __construct(private readonly Database $db)
    {
    }

    /** @return list<array<string, mixed>> */
    public function departments(): array
    {
        $rows = $this->db->all(
            'SELECT d.id, d.name, d.color,
                    (SELECT COUNT(*) FROM users u WHERE u.department_id = d.id) AS user_count,
                    (SELECT COUNT(*) FROM events e WHERE e.department_id = d.id) AS event_count
             FROM departments d
             ORDER BY d.name'
        );

        return array_map(function (array $row): array {
            $color = (string) $row['color'];
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                $color = '#6c757d';
            }

            return [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'color' => $color,
                'user_count' => (int) $row['user_count'],
                'event_count' => (int) $row['event_count'],
            ];
        }, $rows);
    }

    /** @return list<array<string, mixed>> */
    public function users(): array
    {
        $rows = $this->db->all(
            'SELECT u.id, u.username, u.full_name, u.role, u.department_id, u.active,
                    d.name AS department_name, d.color AS department_color,
                    (SELECT COUNT(*) FROM events e WHERE e.user_id = u.id) AS event_count
             FROM users u
             INNER JOIN departments d ON d.id = u.department_id
             ORDER BY d.name, u.full_name'
        );

        return array_map(static function (array $row): array {
            $color = (string) $row['department_color'];
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                $color = '#6c757d';
            }

            return [
                'id' => (int) $row['id'],
                'username' => (string) $row['username'],
                'full_name' => (string) $row['full_name'],
                'role' => (string) $row['role'],
                'department_id' => (int) $row['department_id'],
                'department_name' => (string) $row['department_name'],
                'department_color' => $color,
                'active' => (int) $row['active'],
                'event_count' => (int) $row['event_count'],
            ];
        }, $rows);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    public function saveDepartment(array $input, array $actor): array
    {
        unset($actor);
        $id = (int) ($input['id'] ?? 0);
        $name = trim((string) ($input['name'] ?? ''));
        $color = strtolower(trim((string) ($input['color'] ?? '')));

        if ($name === '' || mb_strlen($name) > 120) {
            return $this->fail('กรุณากรอกชื่อแผนกไม่เกิน 120 ตัวอักษร');
        }
        if (!preg_match('/^#[0-9a-f]{6}$/', $color)) {
            return $this->fail('กรุณาเลือกสีแผนก');
        }

        $duplicate = $this->db->one(
            'SELECT id FROM departments WHERE name = ? AND id <> ? LIMIT 1',
            [$name, $id]
        );
        if ($duplicate !== null) {
            return $this->fail('มีแผนกชื่อนี้อยู่แล้ว');
        }

        if ($id > 0) {
            $exists = $this->db->one('SELECT id FROM departments WHERE id = ?', [$id]);
            if ($exists === null) {
                return $this->fail('ไม่พบแผนกนี้', 404);
            }
            $this->db->execute('UPDATE departments SET name = ?, color = ? WHERE id = ?', [$name, $color, $id]);

            return $this->ok('แก้ไขแผนกแล้ว');
        }

        $this->db->insert('INSERT INTO departments (name, color) VALUES (?, ?)', [$name, $color]);

        return $this->ok('เพิ่มแผนกแล้ว');
    }

    /**
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    public function deleteDepartment(int $id, array $actor): array
    {
        unset($actor);
        $department = $this->db->one('SELECT id, name FROM departments WHERE id = ?', [$id]);
        if ($department === null) {
            return $this->fail('ไม่พบแผนกนี้', 404);
        }
        $users = (int) ($this->db->one('SELECT COUNT(*) AS total FROM users WHERE department_id = ?', [$id])['total'] ?? 0);
        $events = (int) ($this->db->one('SELECT COUNT(*) AS total FROM events WHERE department_id = ?', [$id])['total'] ?? 0);
        if ($users > 0 || $events > 0) {
            return $this->fail('ลบแผนกไม่ได้ เพราะยังมีผู้ใช้หรืองานผูกอยู่');
        }
        $this->db->execute('DELETE FROM departments WHERE id = ?', [$id]);

        return $this->ok('ลบแผนกแล้ว');
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    public function saveUser(array $input, array $actor): array
    {
        $id = (int) ($input['id'] ?? 0);
        $username = trim((string) ($input['username'] ?? ''));
        $fullName = trim((string) ($input['full_name'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        $role = (string) ($input['role'] ?? '');
        $departmentId = (int) ($input['department_id'] ?? 0);
        $active = (int) ($input['active'] ?? 0) === 1 ? 1 : 0;

        if (!preg_match('/^[A-Za-z0-9._-]{3,60}$/', $username)) {
            return $this->fail('ชื่อผู้ใช้ใช้ได้เฉพาะตัวอักษรภาษาอังกฤษ ตัวเลข จุด ขีด และขีดล่าง ความยาว 3–60 ตัว');
        }
        if ($fullName === '' || mb_strlen($fullName) > 120) {
            return $this->fail('กรุณากรอกชื่อ-นามสกุลไม่เกิน 120 ตัวอักษร');
        }
        if (!in_array($role, ['admin', 'employee'], true)) {
            return $this->fail('กรุณาเลือกบทบาท');
        }
        $department = $this->db->one('SELECT id FROM departments WHERE id = ?', [$departmentId]);
        if ($department === null) {
            return $this->fail('กรุณาเลือกแผนก');
        }

        if ($id === 0 && mb_strlen($password) < 8) {
            return $this->fail('รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร');
        }
        if ($id > 0 && $password !== '' && mb_strlen($password) < 8) {
            return $this->fail('รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร');
        }

        $duplicate = $this->db->one(
            'SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1',
            [$username, $id]
        );
        if ($duplicate !== null) {
            return $this->fail('ชื่อผู้ใช้นี้ถูกใช้แล้ว');
        }

        if ($id > 0) {
            $existing = $this->db->one('SELECT id, role, active FROM users WHERE id = ?', [$id]);
            if ($existing === null) {
                return $this->fail('ไม่พบผู้ใช้นี้', 404);
            }
            if ($this->removesLastAdmin($existing, $role, $active)) {
                return $this->fail('ต้องมีผู้ดูแลที่ใช้งานอยู่อย่างน้อย 1 คน');
            }
            if ((int) $actor['id'] === $id && $active === 0) {
                return $this->fail('ปิดใช้งานบัญชีของตนเองไม่ได้');
            }

            if ($password === '') {
                $this->db->execute(
                    'UPDATE users SET username = ?, full_name = ?, role = ?, department_id = ?, active = ? WHERE id = ?',
                    [$username, $fullName, $role, $departmentId, $active, $id]
                );
            } else {
                $this->db->execute(
                    'UPDATE users SET username = ?, full_name = ?, password_hash = ?, role = ?, department_id = ?, active = ? WHERE id = ?',
                    [$username, $fullName, password_hash($password, PASSWORD_DEFAULT), $role, $departmentId, $active, $id]
                );
            }

            return $this->ok('แก้ไขผู้ใช้แล้ว');
        }

        try {
            $this->db->insert(
                'INSERT INTO users (username, password_hash, full_name, role, department_id, active)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$username, password_hash($password, PASSWORD_DEFAULT), $fullName, $role, $departmentId, $active]
            );
        } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() === 1062) {
                return $this->fail('ชื่อผู้ใช้นี้ถูกใช้แล้ว');
            }
            throw $e;
        }

        return $this->ok('เพิ่มผู้ใช้แล้ว');
    }

    /**
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    public function deleteUser(int $id, array $actor): array
    {
        if ((int) $actor['id'] === $id) {
            return $this->fail('ลบบัญชีของตนเองไม่ได้');
        }
        $existing = $this->db->one('SELECT id, role, active FROM users WHERE id = ?', [$id]);
        if ($existing === null) {
            return $this->fail('ไม่พบผู้ใช้นี้', 404);
        }
        if ($this->removesLastAdmin($existing, 'employee', 0)) {
            return $this->fail('ต้องมีผู้ดูแลที่ใช้งานอยู่อย่างน้อย 1 คน');
        }
        $events = (int) ($this->db->one('SELECT COUNT(*) AS total FROM events WHERE user_id = ?', [$id])['total'] ?? 0);
        if ($events > 0) {
            return $this->fail('ลบผู้ใช้ไม่ได้ เพราะยังมีงานของคนนี้อยู่ ให้ปิดใช้งานแทน');
        }
        $this->db->execute('DELETE FROM users WHERE id = ?', [$id]);

        return $this->ok('ลบผู้ใช้แล้ว');
    }

    /** @param array<string, mixed> $existing */
    private function removesLastAdmin(array $existing, string $newRole, int $newActive): bool
    {
        $wasActiveAdmin = (string) $existing['role'] === 'admin' && (int) $existing['active'] === 1;
        $staysActiveAdmin = $newRole === 'admin' && $newActive === 1;
        if (!$wasActiveAdmin || $staysActiveAdmin) {
            return false;
        }
        $count = (int) ($this->db->one(
            "SELECT COUNT(*) AS total FROM users WHERE role = 'admin' AND active = 1"
        )['total'] ?? 0);

        return $count <= 1;
    }

    /** @return array<string, mixed> */
    private function ok(string $message): array
    {
        return [
            'ok' => true,
            'status' => 200,
            'title' => 'สำเร็จ',
            'message' => $message,
        ];
    }

    /** @return array<string, mixed> */
    private function fail(string $message, int $status = 422): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'title' => 'ไม่สำเร็จ',
            'message' => $message,
        ];
    }
}
