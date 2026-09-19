<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

$page = $_GET['page'] ?? 'dashboard';
$budget = new BudgetService();

try {
    switch ($page) {
        case 'login':
            if (Auth::check()) {
                redirect('index.php?page=dashboard');
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $username = trim((string) ($_POST['username'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');
                if (Auth::attempt($username, $password)) {
                    flash('success', 'เข้าสู่ระบบสำเร็จ');
                    redirect('index.php?page=dashboard');
                }
                flash('danger', 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
                redirect('index.php?page=login');
            }
            require base_path('views/login.php');
            break;

        case 'logout':
            Auth::logout();
            flash('success', 'ออกจากระบบแล้ว');
            redirect('index.php?page=login');
            break;

        case 'dashboard':
            Auth::requireLogin();
            $month = $budget->currentMonth();
            $stats = $budget->dashboardStats($month);
            $loans = $budget->activeLoans();
            $recent = $month ? array_slice($budget->transactions((int) $month['id']), 0, 8) : [];
            $expenseBreakdown = $month ? $budget->expenseBreakdownByCategory((int) $month['id']) : [];
            $loanBreakdown = $month ? $budget->loanExpenseBreakdown((int) $month['id']) : [];
            require base_path('views/dashboard.php');
            break;

        case 'wizard':
            Auth::requireLogin();
            $action = $_GET['action'] ?? '';
            $month = $budget->currentMonth();
            if (!$month) {
                $month = $budget->createDraftMonth();
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $monthId = (int) ($_POST['month_id'] ?? 0);
                $month = $budget->getMonth($monthId) ?: $month;

                if ($action === 'step1') {
                    $opening = (float) ($_POST['opening_balance'] ?? 0);
                    $salary = (float) ($_POST['salary_amount'] ?? 0);
                    $budget->confirmOpening($monthId, $opening, $salary);
                    flash('success', 'บันทึกยอดเปิดเดือนแล้ว');
                    redirect('index.php?page=wizard&step=2');
                }

                if ($action === 'step2') {
                    $payments = [];
                    foreach (($_POST['loan_pay'] ?? []) as $loanId => $amount) {
                        $payments[(int) $loanId] = (float) $amount;
                    }
                    $budget->saveLoanAllocations($monthId, $payments);
                    $planned = $budget->plannedTotal($monthId);
                    $available = (float) $budget->getMonth($monthId)['available_total'];
                    if ($planned > $available) {
                        flash('warning', 'งวดสินเชื่อรวมเกินยอดใช้ได้ — ตรวจสอบในขั้นถัดไป');
                    } else {
                        flash('success', 'บันทึกงบสินเชื่อแล้ว');
                    }
                    redirect('index.php?page=wizard&step=3');
                }

                if ($action === 'step3') {
                    $mother = (float) ($_POST['mother_budget'] ?? 0);
                    $personal = (float) ($_POST['personal_budget'] ?? 0);
                    $budget->saveFixedAllocations($monthId, $mother, $personal);
                    flash('success', 'บันทึกงบคุณแม่และส่วนตัวแล้ว');
                    redirect('index.php?page=wizard&step=4');
                }

                if ($action === 'activate') {
                    $month = $budget->getMonth($monthId);
                    $remaining = $budget->remainingAfterPlan($month);
                    if ($remaining < 0) {
                        flash('danger', 'ไม่สามารถเปิดเดือนได้ เพราะจัดสรรเกินยอดใช้ได้');
                        redirect('index.php?page=wizard&step=4');
                    }
                    $budget->activateMonth($monthId);
                    flash('success', 'เปิดใช้งานเดือนแล้ว พร้อมบันทึกรายวัน');
                    redirect('index.php?page=wizard&step=5');
                }
            }

            if ($month['status'] === 'active') {
                $step = 5;
            } else {
                $step = (int) ($_GET['step'] ?? $month['wizard_step'] ?? 1);
                $step = max(1, min(5, $step));
                if ($step > (int) $month['wizard_step'] && $month['status'] === 'draft') {
                    $step = (int) $month['wizard_step'];
                }
            }

            $loans = $budget->activeLoans();
            $allocations = $budget->allocations((int) $month['id']);
            $stats = $budget->dashboardStats($month);
            require base_path('views/wizard.php');
            break;

        case 'transactions':
            Auth::requireLogin();
            $action = $_GET['action'] ?? 'list';
            $month = $budget->currentMonth();
            $months = $budget->allMonths();
            $loans = $budget->activeLoans();
            $edit = null;

            if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $type = $_POST['type'] ?? 'expense';
                $payload = [
                    'month_id' => (int) ($_POST['month_id'] ?? 0),
                    'category' => $_POST['category'] ?? 'personal',
                    'loan_id' => ($_POST['loan_id'] ?? '') !== '' ? (int) $_POST['loan_id'] : null,
                    'txn_date' => $_POST['txn_date'] ?? date('Y-m-d'),
                    'amount' => (float) ($_POST['amount'] ?? 0),
                    'description' => trim((string) ($_POST['description'] ?? '')),
                    'payee' => trim((string) ($_POST['payee'] ?? '')) ?: null,
                    'payment_method' => trim((string) ($_POST['payment_method'] ?? '')) ?: null,
                    'reference_no' => trim((string) ($_POST['reference_no'] ?? '')) ?: null,
                    'notes' => trim((string) ($_POST['notes'] ?? '')) ?: null,
                    'force' => !empty($_POST['force']),
                ];
                $file = $_FILES['attachment'] ?? null;
                $editId = (int) ($_POST['id'] ?? 0);

                if ($editId > 0) {
                    $payload['type'] = $type;
                    $budget->updateTransaction($editId, $payload, $file);
                    flash('success', 'แก้ไขรายการสำเร็จ');
                    redirect('index.php?page=transactions&action=view&id=' . $editId);
                }

                if ($type === 'income') {
                    $budget->addIncome($payload, $file);
                } else {
                    $budget->addExpense($payload, $file);
                }
                flash('success', 'บันทึกรายการสำเร็จ');
                redirect('index.php?page=transactions');
            }

            if ($action === 'attach' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $txnId = (int) ($_POST['transaction_id'] ?? 0);
                $file = $_FILES['attachment'] ?? null;
                if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    throw new RuntimeException('กรุณาเลือกไฟล์');
                }
                $budget->storeAttachment($txnId, $file);
                flash('success', 'แนบหลักฐานแล้ว');
                redirect('index.php?page=transactions&action=view&id=' . $txnId);
            }

            if ($action === 'delete') {
                $token = $_GET['csrf_token'] ?? '';
                if (!hash_equals(csrf_token(), (string) $token)) {
                    http_response_code(403);
                    exit('Invalid CSRF token');
                }
                $budget->deleteTransaction((int) ($_GET['id'] ?? 0));
                flash('success', 'ลบรายการแล้ว');
                redirect('index.php?page=transactions');
            }

            if ($action === 'edit') {
                $edit = $budget->getTransaction((int) ($_GET['id'] ?? 0));
                if (!$edit) {
                    flash('danger', 'ไม่พบรายการ');
                    redirect('index.php?page=transactions');
                }
                $editMonth = $budget->getMonth((int) $edit['month_id']);
                if (!$editMonth || $editMonth['status'] === 'closed') {
                    flash('warning', 'ไม่สามารถแก้ไขรายการในเดือนที่ปิดแล้ว');
                    redirect('index.php?page=transactions&action=view&id=' . (int) $edit['id']);
                }
                $month = $editMonth;
                require base_path('views/transactions.php');
                break;
            }

            if ($action === 'view') {
                $txn = $budget->getTransaction((int) ($_GET['id'] ?? 0));
                if (!$txn) {
                    flash('danger', 'ไม่พบรายการ');
                    redirect('index.php?page=transactions');
                }
                $attachments = $budget->attachmentsFor((int) $txn['id']);
                $txnMonth = $budget->getMonth((int) $txn['month_id']);
                require base_path('views/transaction_view.php');
                break;
            }

            $filterMonthId = isset($_GET['month_id']) && $_GET['month_id'] !== ''
                ? (int) $_GET['month_id']
                : ($month ? (int) $month['id'] : null);
            $filterType = $_GET['type'] ?? null;
            if ($filterType === '') {
                $filterType = null;
            }
            $transactions = $budget->transactions($filterMonthId, $filterType);
            require base_path('views/transactions.php');
            break;

        case 'download':
            Auth::requireLogin();
            $id = (int) ($_GET['id'] ?? 0);
            $stmt = Database::connection()->prepare('SELECT * FROM attachments WHERE id = ?');
            $stmt->execute([$id]);
            $att = $stmt->fetch();
            if (!$att) {
                http_response_code(404);
                exit('File not found');
            }
            $path = upload_path($att['stored_name']);
            if (!is_file($path)) {
                http_response_code(404);
                exit('File missing');
            }
            header('Content-Type: ' . $att['mime_type']);
            header('Content-Disposition: inline; filename="' . rawurlencode($att['original_name']) . '"');
            header('Content-Length: ' . filesize($path));
            readfile($path);
            exit;

        case 'loans':
            Auth::requireLogin();
            $action = $_GET['action'] ?? 'list';
            $loan = null;
            $payments = [];

            if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $id = (int) ($_POST['id'] ?? 0);
                $budget->updateLoan($id, [
                    'name' => trim((string) $_POST['name']),
                    'principal' => (float) $_POST['principal'],
                    'interest_rate' => (float) $_POST['interest_rate'],
                    'term_months' => (int) $_POST['term_months'],
                    'expected_payment' => (float) $_POST['expected_payment'],
                    'balance' => (float) $_POST['balance'],
                    'is_active' => (int) ($_POST['is_active'] ?? 1),
                    'notes' => trim((string) ($_POST['notes'] ?? '')),
                ]);
                flash('success', 'บันทึกสินเชื่อแล้ว');
                redirect('index.php?page=loans');
            }

            if ($action === 'edit') {
                $loan = $budget->getLoan((int) ($_GET['id'] ?? 0));
                if (!$loan) {
                    flash('danger', 'ไม่พบวงเงิน');
                    redirect('index.php?page=loans');
                }
                $payments = $budget->loanPayments((int) $loan['id']);
            }

            $loans = Database::connection()->query('SELECT * FROM loans ORDER BY sort_order, id')->fetchAll();
            require base_path('views/loans.php');
            break;

        case 'months':
            Auth::requireLogin();
            $action = $_GET['action'] ?? 'list';
            if ($action === 'close' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $closed = $budget->closeMonth((int) ($_POST['month_id'] ?? 0));
                flash(
                    'success',
                    'ปิดเดือน ' . thai_month($closed['budget_ym']) . ' แล้ว · ออม '
                    . money((float) $closed['savings_amount']) . ' · ยกไป '
                    . money((float) $closed['carry_forward']) . ' บาท'
                );
                redirect('index.php?page=months');
            }
            $months = $budget->allMonths();
            require base_path('views/months.php');
            break;

        case 'settings':
            Auth::requireLogin();
            if (($_GET['action'] ?? '') === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                foreach (['app_name', 'salary_amount', 'savings_rate', 'personal_budget', 'mother_budget'] as $key) {
                    if (isset($_POST[$key])) {
                        $budget->setSetting($key, trim((string) $_POST[$key]));
                    }
                }
                flash('success', 'บันทึกการตั้งค่าแล้ว');
                redirect('index.php?page=settings');
            }
            $settings = $budget->allSettings();
            require base_path('views/settings.php');
            break;

        default:
            Auth::requireLogin();
            redirect('index.php?page=dashboard');
    }
} catch (Throwable $e) {
    if (Auth::check()) {
        flash('danger', $e->getMessage());
        $fallback = match ($page) {
            'wizard' => 'wizard',
            'transactions' => 'transactions',
            'loans' => 'loans',
            'settings' => 'settings',
            'months' => 'months',
            default => 'dashboard',
        };
        redirect('index.php?page=' . $fallback);
    }
    http_response_code(500);
    echo '<pre style="font-family:monospace;padding:2rem">' . htmlspecialchars($e->getMessage()) . '</pre>';
}
