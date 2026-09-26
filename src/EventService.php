<?php

declare(strict_types=1);

namespace App;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;

final class EventService
{
    public function __construct(
        private readonly Database $db,
        private readonly GoogleCalendarSync $google,
    ) {
    }

    /**
     * @param array<string, mixed> $actor
     * @return list<array<string, mixed>>
     */
    public function list(string $from, string $to, ?int $departmentId, array $actor): array
    {
        $params = [$to, $from];
        $departmentSql = '';
        if ($departmentId !== null) {
            $departmentSql = ' AND e.department_id = ?';
            $params[] = $departmentId;
        }

        $rows = $this->db->all(
            'SELECT e.id, e.title, e.description, e.start_at, e.end_at, e.department_id, e.user_id,
                    d.name AS department_name, d.color AS department_color, u.full_name AS owner_name
             FROM events e
             INNER JOIN departments d ON d.id = e.department_id
             INNER JOIN users u ON u.id = e.user_id
             WHERE e.start_at < ? AND e.end_at > ?' . $departmentSql . '
             ORDER BY e.start_at, e.id',
            $params
        );

        return array_map(fn (array $row): array => $this->present($row, $actor), $rows);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    public function create(array $input, array $actor): array
    {
        $fields = $this->validate($input);
        $id = $this->db->insert(
            'INSERT INTO events (title, description, start_at, end_at, department_id, user_id)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $fields['title'],
                $fields['description'],
                $fields['start_at'],
                $fields['end_at'],
                (int) $actor['department_id'],
                (int) $actor['id'],
            ]
        );

        return $this->afterWrite($id, $actor, 'create');
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    public function update(int $id, array $input, array $actor): array
    {
        $existing = $this->findRow($id);
        if ($existing === null) {
            return $this->fail('ไม่พบงานนี้', 404);
        }
        if ((int) $existing['user_id'] !== (int) $actor['id']) {
            return $this->fail('แก้ไขได้เฉพาะงานของตนเอง', 403);
        }

        $fields = $this->validate($input);
        $this->db->execute(
            'UPDATE events
             SET title = ?, description = ?, start_at = ?, end_at = ?, department_id = ?
             WHERE id = ? AND user_id = ?',
            [
                $fields['title'],
                $fields['description'],
                $fields['start_at'],
                $fields['end_at'],
                (int) $actor['department_id'],
                $id,
                (int) $actor['id'],
            ]
        );

        return $this->afterWrite($id, $actor, 'update');
    }

    /**
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    public function delete(int $id, array $actor): array
    {
        $existing = $this->findRow($id);
        if ($existing === null) {
            return $this->fail('ไม่พบงานนี้', 404);
        }
        if ((int) $existing['user_id'] !== (int) $actor['id']) {
            return $this->fail('ลบได้เฉพาะงานของตนเอง', 403);
        }

        $googleId = trim((string) ($existing['google_event_id'] ?? ''));
        $this->db->execute('DELETE FROM events WHERE id = ? AND user_id = ?', [$id, (int) $actor['id']]);
        $result = $this->syncResult('delete', $googleId !== '' ? $googleId : null, null);
        unset($result['google_event_id']);

        return $result;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{title: string, description: ?string, start_at: string, end_at: string}
     */
    private function validate(array $input): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $start = $this->parseDate((string) ($input['start_at'] ?? ''));
        $end = $this->parseDate((string) ($input['end_at'] ?? ''));

        if ($title === '') {
            $this->halt('กรุณากรอกหัวข้องาน');
        }
        if (mb_strlen($title) > 200) {
            $this->halt('หัวข้องานยาวเกิน 200 ตัวอักษร');
        }
        if (mb_strlen($description) > 4000) {
            $this->halt('รายละเอียดยาวเกิน 4,000 ตัวอักษร');
        }
        if ($start === null || $end === null) {
            $this->halt('กรุณาระบุวันและเวลาเริ่มกับเวลาสิ้นสุด');
        }
        if ($end <= $start) {
            $this->halt('เวลาสิ้นสุดต้องอยู่หลังเวลาเริ่ม');
        }
        if ($start->diff($end)->days > 366) {
            $this->halt('ช่วงเวลางานยาวเกิน 366 วัน');
        }

        return [
            'title' => $title,
            'description' => $description === '' ? null : $description,
            'start_at' => $start->format('Y-m-d H:i:s'),
            'end_at' => $end->format('Y-m-d H:i:s'),
        ];
    }

    private function parseDate(string $value): ?DateTimeImmutable
    {
        $value = trim($value);
        $zone = new DateTimeZone('Asia/Bangkok');
        foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s', 'Y-m-d H:i'] as $format) {
            $parsed = DateTimeImmutable::createFromFormat($format, $value, $zone);
            $errors = DateTimeImmutable::getLastErrors();
            if ($parsed instanceof DateTimeImmutable && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                $formatted = $parsed->format($format);
                if ($formatted === $value) {
                    return $parsed;
                }
            }
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    private function findRow(int $id): ?array
    {
        return $this->db->one(
            'SELECT e.id, e.title, e.description, e.start_at, e.end_at, e.department_id, e.user_id, e.google_event_id,
                    d.name AS department_name, d.color AS department_color, u.full_name AS owner_name
             FROM events e
             INNER JOIN departments d ON d.id = e.department_id
             INNER JOIN users u ON u.id = e.user_id
             WHERE e.id = ?
             LIMIT 1',
            [$id]
        );
    }

    /**
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    private function afterWrite(int $id, array $actor, string $action): array
    {
        $row = $this->findRow($id);
        if ($row === null) {
            return $this->fail('บันทึกงานไม่สำเร็จ', 500);
        }

        $googleId = trim((string) ($row['google_event_id'] ?? ''));
        $sync = $this->syncResult($action, $googleId !== '' ? $googleId : null, $row);
        if (($sync['google_event_id'] ?? null) !== null && (string) $sync['google_event_id'] !== $googleId) {
            $this->db->execute('UPDATE events SET google_event_id = ? WHERE id = ?', [(string) $sync['google_event_id'], $id]);
        }
        unset($sync['google_event_id']);
        $fresh = $this->findRow($id);
        $sync['event'] = $fresh === null ? null : $this->present($fresh, $actor);

        return $sync;
    }

    /**
     * @param array<string, mixed>|null $event
     * @return array<string, mixed>
     */
    private function syncResult(string $action, ?string $googleId, ?array $event): array
    {
        $availability = $this->google->availability();
        if ($availability !== 'ready') {
            return [
                'ok' => true,
                'status' => 200,
                'google' => 'not_configured',
                'title' => $action === 'delete' ? 'ลบในระบบแล้ว' : 'บันทึกในระบบแล้ว',
                'message' => $this->google->skipMessage($action),
                'google_event_id' => null,
            ];
        }

        try {
            $newId = $googleId;
            if ($action === 'delete') {
                if ($googleId !== null && $googleId !== '') {
                    $this->google->delete($googleId);
                }
                $newId = null;
            } elseif ($event !== null) {
                $newId = ($googleId === null || $googleId === '')
                    ? $this->google->insert($event)
                    : $this->google->update($googleId, $event);
            }

            $title = match ($action) {
                'delete' => 'ลบแล้ว',
                'update' => 'แก้ไขแล้ว',
                default => 'บันทึกแล้ว',
            };
            $message = match ($action) {
                'delete' => 'ลบงานและซิงก์ออกจาก Google Calendar แล้ว',
                'update' => 'แก้ไขงานและซิงก์ไป Google Calendar แล้ว',
                default => 'บันทึกงานและซิงก์ไป Google Calendar แล้ว',
            };

            return [
                'ok' => true,
                'status' => 200,
                'google' => 'synced',
                'title' => $title,
                'message' => $message,
                'google_event_id' => $newId,
            ];
        } catch (Throwable $error) {
            $done = match ($action) {
                'delete' => 'ลบงานในระบบแล้ว',
                'update' => 'แก้ไขงานในระบบแล้ว',
                default => 'บันทึกงานในระบบแล้ว',
            };

            return [
                'ok' => true,
                'status' => 200,
                'google' => 'failed',
                'title' => 'บันทึกในระบบแล้ว',
                'message' => $done . ' แต่ซิงก์ Google Calendar ไม่สำเร็จ: ' . $this->google->safeError($error),
                'google_event_id' => $googleId,
            ];
        }
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $actor
     * @return array<string, mixed>
     */
    private function present(array $row, array $actor): array
    {
        $color = (string) $row['department_color'];
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = '#6c757d';
        }

        return [
            'id' => (int) $row['id'],
            'title' => (string) $row['title'],
            'description' => (string) ($row['description'] ?? ''),
            'start_at' => (string) $row['start_at'],
            'end_at' => (string) $row['end_at'],
            'department_id' => (int) $row['department_id'],
            'department_name' => (string) $row['department_name'],
            'department_color' => $color,
            'user_id' => (int) $row['user_id'],
            'owner_name' => (string) $row['owner_name'],
            'can_edit' => (int) $row['user_id'] === (int) $actor['id'],
        ];
    }

    /** @return array<string, mixed> */
    private function fail(string $message, int $status): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'title' => 'ไม่สำเร็จ',
            'message' => $message,
        ];
    }

    private function halt(string $message): never
    {
        Http::json([
            'ok' => false,
            'title' => 'ไม่สำเร็จ',
            'message' => $message,
        ], 422);
    }
}
