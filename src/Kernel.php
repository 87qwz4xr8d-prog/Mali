<?php

declare(strict_types=1);

namespace App;

use mysqli_sql_exception;
use Throwable;

final class Kernel
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config)
    {
    }

    public function handle(): void
    {
        try {
            $this->dispatch();
        } catch (mysqli_sql_exception $error) {
            error_log('Calendar DB error: ' . $error->getMessage());
            $this->failUnexpected(str_starts_with(Http::path(), '/api') || Http::method() === 'POST');
        } catch (Throwable $error) {
            error_log('Calendar error: ' . $error->getMessage());
            $this->failUnexpected(str_starts_with(Http::path(), '/api') || Http::method() === 'POST');
        }
    }

    private function dispatch(): void
    {
        try {
            $db = new Database($this->config['db']);
        } catch (Throwable $error) {
            error_log('Calendar connection: ' . $error->getMessage());
            $this->setup('เชื่อมต่อฐานข้อมูลไม่สำเร็จ ตรวจค่าใน config/config.php แล้วนำเข้า sql/company_calendar.sql ผ่าน phpMyAdmin');
        }

        $tableResult = $db->mysqli()->query('SHOW TABLES');
        $tables = [];
        if ($tableResult !== false) {
            while ($tableRow = $tableResult->fetch_row()) {
                $tables[] = (string) ($tableRow[0] ?? '');
            }
            $tableResult->free();
        }
        foreach (['departments', 'users', 'events'] as $requiredTable) {
            if (!in_array($requiredTable, $tables, true)) {
                $this->setup('ยังไม่พบตารางในฐานข้อมูล ให้นำเข้าไฟล์ sql/company_calendar.sql ผ่าน phpMyAdmin');
            }
        }

        $auth = new Auth($db);
        $google = new GoogleCalendarSync($this->config['google'] ?? []);
        $events = new EventService($db, $google);
        $admin = new AdminService($db);
        $path = Http::path();
        $method = Http::method();
        $user = $auth->user();

        if ($path === '/login' && $method === 'GET') {
            if ($user !== null) {
                Http::redirect('/calendar');
            }
            $this->page('login', [
                'title' => 'เข้าสู่ระบบ',
                'showNav' => false,
                'appName' => (string) ($this->config['app']['name'] ?? 'ปฏิทินกลางบริษัท'),
            ]);
        }

        if ($path === '/login' && $method === 'POST') {
            $this->requireCsrf();
            $input = Http::input();
            $username = trim((string) ($input['username'] ?? ''));
            $password = (string) ($input['password'] ?? '');
            if ($username === '' || $password === '') {
                Http::json(['ok' => false, 'title' => 'เข้าสู่ระบบไม่สำเร็จ', 'message' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน'], 422);
            }
            $result = $auth->attempt($username, $password);
            if ($result === null) {
                Http::json(['ok' => false, 'title' => 'เข้าสู่ระบบไม่สำเร็จ', 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'], 401);
            }
            if (!empty($result['inactive'])) {
                Http::json(['ok' => false, 'title' => 'เข้าสู่ระบบไม่สำเร็จ', 'message' => 'บัญชีนี้ถูกปิดใช้งาน'], 403);
            }
            Http::json(['ok' => true, 'title' => 'สำเร็จ', 'message' => 'เข้าสู่ระบบแล้ว', 'redirect' => '/calendar']);
        }

        if ($path === '/logout' && $method === 'POST') {
            if (!Csrf::check(Csrf::fromRequest())) {
                Http::redirect($user !== null ? '/calendar' : '/login');
            }
            $auth->logout();
            Http::redirect('/login');
        }

        if ($user === null) {
            if ($method === 'GET' && !str_starts_with($path, '/api')) {
                Http::redirect('/login');
            }
            Http::json(['ok' => false, 'title' => 'กรุณาเข้าสู่ระบบ', 'message' => 'เซสชันหมดอายุ'], 401);
        }

        if (($path === '/' || $path === '/calendar') && $method === 'GET') {
            $ready = $google->availability() === 'ready';
            $this->page('calendar', [
                'title' => 'ปฏิทิน',
                'showNav' => true,
                'activeNav' => 'calendar',
                'user' => $user,
                'appName' => (string) ($this->config['app']['name'] ?? 'ปฏิทินกลางบริษัท'),
                'departments' => $admin->departments(),
                'pageScript' => '/assets/js/calendar.js',
                'bootSwal' => $ready ? null : [
                    'icon' => 'info',
                    'title' => 'ยังไม่ซิงก์ Google Calendar',
                    'text' => $google->bannerMessage(),
                    'onceKey' => 'google-sync-notice',
                ],
            ]);
        }

        if ($path === '/api/events' && $method === 'GET') {
            [$from, $to] = $this->range($_GET['from'] ?? '', $_GET['to'] ?? '');
            $department = $this->optionalDepartment($_GET['department_id'] ?? null, $admin);
            Http::json([
                'ok' => true,
                'events' => $events->list($from, $to, $department, $user),
            ]);
        }

        if ($path === '/api/events' && $method === 'POST') {
            $this->requireCsrf();
            $result = $events->create(Http::input(), $user);
            $this->emit($result);
        }

        if ($path === '/api/events/update' && $method === 'POST') {
            $this->requireCsrf();
            $input = Http::input();
            $id = (int) ($input['id'] ?? 0);
            if ($id < 1) {
                Http::json(['ok' => false, 'title' => 'ไม่สำเร็จ', 'message' => 'ไม่พบงานนี้'], 422);
            }
            $this->emit($events->update($id, $input, $user));
        }

        if ($path === '/api/events/delete' && $method === 'POST') {
            $this->requireCsrf();
            $id = (int) (Http::input()['id'] ?? 0);
            if ($id < 1) {
                Http::json(['ok' => false, 'title' => 'ไม่สำเร็จ', 'message' => 'ไม่พบงานนี้'], 422);
            }
            $this->emit($events->delete($id, $user));
        }

        if (str_starts_with($path, '/admin')) {
            if (($user['role'] ?? '') !== 'admin') {
                if ($method === 'GET') {
                    $this->page('not_found', [
                        'title' => 'ไม่มีสิทธิ์',
                        'showNav' => true,
                        'activeNav' => '',
                        'user' => $user,
                        'appName' => (string) ($this->config['app']['name'] ?? 'ปฏิทินกลางบริษัท'),
                        'heading' => 'ไม่มีสิทธิ์',
                        'message' => 'เฉพาะผู้ดูแลจัดการผู้ใช้และแผนกได้',
                    ], 403);
                }
                Http::json(['ok' => false, 'title' => 'ไม่มีสิทธิ์', 'message' => 'เฉพาะผู้ดูแลจัดการผู้ใช้และแผนกได้'], 403);
            }
        }

        if ($path === '/admin/users' && $method === 'GET') {
            $this->page('admin_users', [
                'title' => 'ผู้ใช้',
                'showNav' => true,
                'activeNav' => 'users',
                'user' => $user,
                'appName' => (string) ($this->config['app']['name'] ?? 'ปฏิทินกลางบริษัท'),
                'departments' => $admin->departments(),
                'users' => $admin->users(),
                'pageScript' => '/assets/js/admin.js',
            ]);
        }

        if ($path === '/admin/users' && $method === 'POST') {
            $this->requireCsrf();
            $this->emit($admin->saveUser(Http::input(), $user));
        }

        if ($path === '/admin/users/delete' && $method === 'POST') {
            $this->requireCsrf();
            $id = (int) (Http::input()['id'] ?? 0);
            $this->emit($admin->deleteUser($id, $user));
        }

        if ($path === '/admin/departments' && $method === 'GET') {
            $this->page('admin_departments', [
                'title' => 'แผนก',
                'showNav' => true,
                'activeNav' => 'departments',
                'user' => $user,
                'appName' => (string) ($this->config['app']['name'] ?? 'ปฏิทินกลางบริษัท'),
                'departments' => $admin->departments(),
                'pageScript' => '/assets/js/admin.js',
            ]);
        }

        if ($path === '/admin/departments' && $method === 'POST') {
            $this->requireCsrf();
            $this->emit($admin->saveDepartment(Http::input(), $user));
        }

        if ($path === '/admin/departments/delete' && $method === 'POST') {
            $this->requireCsrf();
            $id = (int) (Http::input()['id'] ?? 0);
            $this->emit($admin->deleteDepartment($id, $user));
        }

        if ($method === 'GET') {
            $this->page('not_found', [
                'title' => 'ไม่พบหน้า',
                'showNav' => true,
                'activeNav' => '',
                'user' => $user,
                'appName' => (string) ($this->config['app']['name'] ?? 'ปฏิทินกลางบริษัท'),
                'heading' => 'ไม่พบหน้านี้',
                'message' => 'กลับไปที่ปฏิทินแล้วลองใหม่',
            ], 404);
        }

        Http::json(['ok' => false, 'title' => 'ไม่สำเร็จ', 'message' => 'ไม่พบรายการที่เรียก'], 404);
    }

    /** @param array<string, mixed> $data */
    private function page(string $view, array $data, int $status = 200): never
    {
        $data['csrf'] = Csrf::token();
        Http::view($view, $data, $status);
    }

    private function setup(string $message): never
    {
        Http::view('setup_error', [
            'title' => 'ตั้งค่าฐานข้อมูล',
            'showNav' => false,
            'appName' => (string) ($this->config['app']['name'] ?? 'ปฏิทินกลางบริษัท'),
            'csrf' => Csrf::token(),
            'message' => $message,
            'bootSwal' => [
                'icon' => 'error',
                'title' => 'ยังใช้งานไม่ได้',
                'text' => $message,
            ],
        ], 500);
    }

    private function requireCsrf(): void
    {
        if (!Csrf::check(Csrf::fromRequest())) {
            Http::json([
                'ok' => false,
                'title' => 'ไม่สำเร็จ',
                'message' => 'เซสชันไม่ถูกต้อง กรุณาโหลดหน้าใหม่',
            ], 419);
        }
    }

    /** @param array<string, mixed> $result */
    private function emit(array $result): never
    {
        $status = (int) ($result['status'] ?? ($result['ok'] ? 200 : 422));
        unset($result['status']);
        Http::json($result, $status);
    }

    /** @return array{0: string, 1: string} */
    private function range(mixed $from, mixed $to): array
    {
        $start = $this->day((string) $from);
        $end = $this->day((string) $to);
        if ($start === null || $end === null || $end <= $start || $start->diff($end)->days > 120) {
            Http::json(['ok' => false, 'title' => 'ไม่สำเร็จ', 'message' => 'ช่วงวันที่ไม่ถูกต้อง'], 422);
        }

        return [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];
    }

    private function day(string $value): ?\DateTimeImmutable
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $value, new \DateTimeZone('Asia/Bangkok'));
        $errors = \DateTimeImmutable::getLastErrors();
        if (!$parsed || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }
        if ($parsed->format('Y-m-d') !== $value) {
            return null;
        }

        return $parsed->setTime(0, 0, 0);
    }

    private function optionalDepartment(mixed $value, AdminService $admin): ?int
    {
        if ($value === null || $value === '' || $value === '0') {
            return null;
        }
        $id = (int) $value;
        foreach ($admin->departments() as $department) {
            if ($department['id'] === $id) {
                return $id;
            }
        }
        Http::json(['ok' => false, 'title' => 'ไม่สำเร็จ', 'message' => 'ไม่พบแผนกที่เลือก'], 422);
    }

    private function failUnexpected(bool $json): never
    {
        if ($json) {
            Http::json(['ok' => false, 'title' => 'ไม่สำเร็จ', 'message' => 'เกิดข้อผิดพลาดภายในระบบ'], 500);
        }
        $this->setup('เกิดข้อผิดพลาดภายในระบบ ตรวจบันทึกของ PHP และการเชื่อมต่อฐานข้อมูล');
    }
}
