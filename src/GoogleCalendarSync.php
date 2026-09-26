<?php

declare(strict_types=1);

namespace App;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Exception as GoogleServiceException;
use Throwable;

final class GoogleCalendarSync
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config)
    {
    }

    public function availability(): string
    {
        $calendarId = trim((string) ($this->config['calendar_id'] ?? ''));
        $path = (string) ($this->config['credentials_path'] ?? '');
        if ($calendarId === '' || $path === '' || !is_file($path) || !is_readable($path)) {
            return 'not_configured';
        }
        if (!class_exists(\Google\Client::class) || !class_exists(Calendar::class)) {
            return 'library_missing';
        }

        return 'ready';
    }

    public function bannerMessage(): string
    {
        if ($this->availability() === 'library_missing') {
            return 'พบไฟล์ตั้งค่า Google Calendar แล้ว แต่ยังไม่ได้ติดตั้งไลบรารี กรุณารัน composer install จึงยังไม่ซิงก์งานไปปฏิทินบริษัท';
        }

        return 'ยังไม่ได้ตั้งค่า Google Calendar ระบบใช้งานในเครื่องได้ตามปกติ แต่ยังไม่ซิงก์งานไปปฏิทินบริษัท';
    }

    public function skipMessage(string $action): string
    {
        $done = match ($action) {
            'delete' => 'ลบงานในระบบแล้ว',
            'update' => 'แก้ไขงานในระบบแล้ว',
            default => 'บันทึกงานในระบบแล้ว',
        };
        if ($this->availability() === 'library_missing') {
            return $done . ' แต่ยังติดตั้งไลบรารี Google API ไม่ครบ กรุณารัน composer install จึงยังไม่ซิงก์ไป Google Calendar';
        }

        return $done . ' แต่ยังไม่ได้ตั้งค่า Google Calendar จึงยังไม่ซิงก์ไปปฏิทินบริษัท';
    }

    /** @param array<string, mixed> $event */
    public function insert(array $event): string
    {
        $created = $this->service()->events->insert($this->calendarId(), $this->payload($event));
        $id = (string) $created->getId();
        if ($id === '') {
            throw new \RuntimeException('Google Calendar ไม่ส่งรหัสกิจกรรมกลับมา');
        }

        return $id;
    }

    /** @param array<string, mixed> $event */
    public function update(string $googleEventId, array $event): string
    {
        try {
            $updated = $this->service()->events->update($this->calendarId(), $googleEventId, $this->payload($event));
            $id = (string) $updated->getId();

            return $id !== '' ? $id : $googleEventId;
        } catch (GoogleServiceException $e) {
            if ($e->getCode() === 404) {
                return $this->insert($event);
            }
            throw $e;
        }
    }

    public function delete(string $googleEventId): void
    {
        try {
            $this->service()->events->delete($this->calendarId(), $googleEventId);
        } catch (GoogleServiceException $e) {
            if ($e->getCode() === 404) {
                return;
            }
            throw $e;
        }
    }

    public function safeError(Throwable $error): string
    {
        $message = trim($error->getMessage());
        $lower = strtolower($message);
        if (
            $message === ''
            || str_contains($lower, 'private_key')
            || str_contains($lower, 'private key')
            || str_contains($lower, 'default credentials')
            || str_contains($lower, 'invalid json')
            || str_contains($lower, 'openssl')
            || str_contains($message, 'BEGIN ')
        ) {
            return 'ไฟล์คีย์ service account ใช้ไม่ได้ ตรวจไฟล์ JSON และรหัสปฏิทินใน config.php';
        }
        if (function_exists('mb_substr')) {
            $message = mb_substr($message, 0, 180);
        } else {
            $message = substr($message, 0, 180);
        }

        return $message;
    }

    private function calendarId(): string
    {
        return trim((string) ($this->config['calendar_id'] ?? ''));
    }

    private function service(): Calendar
    {
        $client = new \Google\Client();
        $client->setApplicationName('Company Central Calendar');
        $client->setAuthConfig((string) $this->config['credentials_path']);
        $client->setScopes([Calendar::CALENDAR_EVENTS]);

        return new Calendar($client);
    }

    /** @param array<string, mixed> $event */
    private function payload(array $event): Event
    {
        $tz = new DateTimeZone((string) ($this->config['timezone'] ?? 'Asia/Bangkok'));
        $start = new DateTimeImmutable((string) $event['start_at'], $tz);
        $end = new DateTimeImmutable((string) $event['end_at'], $tz);
        $lines = [];
        $description = trim((string) ($event['description'] ?? ''));
        if ($description !== '') {
            $lines[] = $description;
            $lines[] = '';
        }
        $lines[] = 'แผนก: ' . (string) ($event['department_name'] ?? '');
        $lines[] = 'ผู้บันทึก: ' . (string) ($event['owner_name'] ?? '');

        return new Event([
            'summary' => (string) $event['title'],
            'description' => implode("\n", $lines),
            'start' => [
                'dateTime' => $start->format(DateTimeInterface::RFC3339),
                'timeZone' => $tz->getName(),
            ],
            'end' => [
                'dateTime' => $end->format(DateTimeInterface::RFC3339),
                'timeZone' => $tz->getName(),
            ],
        ]);
    }
}
