<?php

declare(strict_types=1);

final class BudgetService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function getSetting(string $key, ?string $default = null): ?string
    {
        $stmt = $this->db->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? (string) $row['setting_value'] : $default;
    }

    public function setSetting(string $key, string $value): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute([$key, $value]);
    }

    /** @return array<string, string> */
    public function allSettings(): array
    {
        $rows = $this->db->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $out[$row['setting_key']] = $row['setting_value'];
        }
        return $out;
    }

    public function salaryAmount(): float
    {
        return (float) $this->getSetting('salary_amount', '31364');
    }

    public function savingsRate(): float
    {
        return (float) $this->getSetting('savings_rate', '5');
    }

    public function personalBudget(): float
    {
        return (float) $this->getSetting('personal_budget', '5000');
    }

    public function motherBudget(): float
    {
        return (float) $this->getSetting('mother_budget', '10000');
    }

    /** @return list<array<string, mixed>> */
    public function activeLoans(): array
    {
        return $this->db->query(
            'SELECT * FROM loans WHERE is_active = 1 ORDER BY sort_order, id'
        )->fetchAll();
    }

    public function getMonth(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM budget_months WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getMonthByYm(string $ym): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM budget_months WHERE budget_ym = ?');
        $stmt->execute([$ym]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function currentMonth(): ?array
    {
        $stmt = $this->db->query(
            "SELECT * FROM budget_months WHERE status IN ('draft','active') ORDER BY budget_ym DESC LIMIT 1"
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function allMonths(): array
    {
        return $this->db->query(
            'SELECT * FROM budget_months ORDER BY budget_ym DESC'
        )->fetchAll();
    }

    public function lastClosedCarry(): float
    {
        $stmt = $this->db->query(
            "SELECT carry_forward FROM budget_months WHERE status = 'closed' ORDER BY budget_ym DESC LIMIT 1"
        );
        $row = $stmt->fetch();
        return $row ? (float) $row['carry_forward'] : 0.0;
    }

    public function createDraftMonth(?string $yearMonth = null): array
    {
        $ym = $yearMonth ?: date('Y-m');
        $existing = $this->getMonthByYm($ym);
        if ($existing) {
            return $existing;
        }

        $salary = $this->salaryAmount();
        $carry = $this->lastClosedCarry();
        $available = $carry + $salary;

        $stmt = $this->db->prepare(
            'INSERT INTO budget_months
             (budget_ym, opening_balance, salary_amount, available_total, status, wizard_step, opened_at)
             VALUES (?, ?, ?, ?, ?, 1, NOW())'
        );
        $stmt->execute([$ym, $carry, $salary, $available, 'draft']);

        return $this->getMonth((int) $this->db->lastInsertId());
    }

    public function updateWizardStep(int $monthId, int $step): void
    {
        $stmt = $this->db->prepare('UPDATE budget_months SET wizard_step = ? WHERE id = ?');
        $stmt->execute([$step, $monthId]);
    }

    public function confirmOpening(int $monthId, float $opening, float $salary): void
    {
        $available = $opening + $salary;
        $stmt = $this->db->prepare(
            'UPDATE budget_months
             SET opening_balance = ?, salary_amount = ?, available_total = ?, wizard_step = 2
             WHERE id = ? AND status = ?'
        );
        $stmt->execute([$opening, $salary, $available, $monthId, 'draft']);
    }

    /**
     * @param array<int, float> $loanPayments loan_id => amount
     */
    public function saveLoanAllocations(int $monthId, array $loanPayments): void
    {
        $this->db->prepare(
            "DELETE FROM budget_allocations WHERE month_id = ? AND category = 'loan'"
        )->execute([$monthId]);

        $loans = $this->activeLoans();
        $insert = $this->db->prepare(
            'INSERT INTO budget_allocations
             (month_id, category, loan_id, label, planned_amount, sort_order)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        foreach ($loans as $loan) {
            $id = (int) $loan['id'];
            $amount = (float) ($loanPayments[$id] ?? $loan['expected_payment']);
            if ($amount <= 0) {
                continue;
            }
            $insert->execute([
                $monthId,
                'loan',
                $id,
                $loan['name'],
                $amount,
                (int) $loan['sort_order'],
            ]);
        }

        $this->updateWizardStep($monthId, 3);
    }

    public function saveFixedAllocations(int $monthId, float $mother, float $personal): void
    {
        $this->db->prepare(
            "DELETE FROM budget_allocations WHERE month_id = ? AND category IN ('mother','personal')"
        )->execute([$monthId]);

        $insert = $this->db->prepare(
            'INSERT INTO budget_allocations
             (month_id, category, loan_id, label, planned_amount, sort_order)
             VALUES (?, ?, NULL, ?, ?, ?)'
        );
        $insert->execute([$monthId, 'mother', 'ให้คุณแม่', $mother, 90]);
        $insert->execute([$monthId, 'personal', 'ค่าใช้จ่ายส่วนตัว', $personal, 100]);

        $this->updateWizardStep($monthId, 4);
    }

    /** @return list<array<string, mixed>> */
    public function allocations(int $monthId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM budget_allocations WHERE month_id = ? ORDER BY sort_order, id'
        );
        $stmt->execute([$monthId]);
        return $stmt->fetchAll();
    }

    public function plannedTotal(int $monthId): float
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(planned_amount), 0) AS total FROM budget_allocations WHERE month_id = ?'
        );
        $stmt->execute([$monthId]);
        return (float) $stmt->fetch()['total'];
    }

    public function remainingAfterPlan(array $month): float
    {
        return (float) $month['available_total'] - $this->plannedTotal((int) $month['id']);
    }

    public function projectedSavings(float $remaining): float
    {
        $rate = $this->savingsRate() / 100;
        return round(max(0, $remaining) * $rate, 2);
    }

    public function activateMonth(int $monthId): void
    {
        $month = $this->getMonth($monthId);
        if (!$month || $month['status'] !== 'draft') {
            throw new RuntimeException('เดือนนี้ไม่สามารถเปิดใช้งานได้');
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "UPDATE budget_months SET status = 'active', wizard_step = 5, opened_at = COALESCE(opened_at, NOW()) WHERE id = ?"
            );
            $stmt->execute([$monthId]);

            // บันทึกรายรับเงินเดือน + ยกยอดเข้า
            if ((float) $month['opening_balance'] > 0) {
                $this->insertTransaction([
                    'month_id' => $monthId,
                    'type' => 'income',
                    'category' => 'carry',
                    'txn_date' => $month['budget_ym'] . '-01',
                    'amount' => (float) $month['opening_balance'],
                    'description' => 'ยอดยกมาจากเดือนก่อน',
                ]);
            }

            if ((float) $month['salary_amount'] > 0) {
                $this->insertTransaction([
                    'month_id' => $monthId,
                    'type' => 'income',
                    'category' => 'salary',
                    'txn_date' => $month['budget_ym'] . '-01',
                    'amount' => (float) $month['salary_amount'],
                    'description' => 'เงินเดือนเข้า (ใช้สำหรับเดือนนี้)',
                ]);
            }

            // สร้างรายการจ่ายตามซองงบที่จัดสรร (สินเชื่อ / คุณแม่) — ส่วนตัวใช้บันทึกรายวัน
            $allocs = $this->allocations($monthId);
            foreach ($allocs as $alloc) {
                if ($alloc['category'] === 'personal') {
                    continue;
                }
                if ((float) $alloc['planned_amount'] <= 0) {
                    continue;
                }

                $txnId = $this->insertTransaction([
                    'month_id' => $monthId,
                    'type' => 'expense',
                    'category' => $alloc['category'],
                    'loan_id' => $alloc['loan_id'],
                    'allocation_id' => $alloc['id'],
                    'txn_date' => $month['budget_ym'] . '-01',
                    'amount' => (float) $alloc['planned_amount'],
                    'description' => 'จัดสรร: ' . $alloc['label'],
                ]);

                $this->db->prepare(
                    'UPDATE budget_allocations SET spent_amount = spent_amount + ? WHERE id = ?'
                )->execute([(float) $alloc['planned_amount'], $alloc['id']]);

                if ($alloc['category'] === 'loan' && $alloc['loan_id']) {
                    $this->reduceLoanBalance((int) $alloc['loan_id'], (float) $alloc['planned_amount']);
                }
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function insertTransaction(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO transactions
             (month_id, type, category, loan_id, allocation_id, txn_date, amount, description, payee, payment_method, reference_no, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['month_id'],
            $data['type'],
            $data['category'],
            $data['loan_id'] ?? null,
            $data['allocation_id'] ?? null,
            $data['txn_date'],
            $data['amount'],
            $data['description'] ?? '',
            $data['payee'] ?? null,
            $data['payment_method'] ?? null,
            $data['reference_no'] ?? null,
            $data['notes'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function reduceLoanBalance(int $loanId, float $amount): void
    {
        $stmt = $this->db->prepare(
            'UPDATE loans SET balance = GREATEST(0, balance - ?) WHERE id = ?'
        );
        $stmt->execute([$amount, $loanId]);
    }

    public function getCashBalance(int $monthId): float
    {
        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0)
              - COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS balance
             FROM transactions WHERE month_id = ?"
        );
        $stmt->execute([$monthId]);
        return (float) $stmt->fetch()['balance'];
    }

    public function getPersonalRemaining(int $monthId): float
    {
        $stmt = $this->db->prepare(
            "SELECT planned_amount, spent_amount FROM budget_allocations
             WHERE month_id = ? AND category = 'personal' LIMIT 1"
        );
        $stmt->execute([$monthId]);
        $row = $stmt->fetch();
        if (!$row) {
            return 0.0;
        }
        return (float) $row['planned_amount'] - (float) $row['spent_amount'];
    }

    public function addExpense(array $data, ?array $file = null): int
    {
        $month = $this->getMonth((int) $data['month_id']);
        if (!$month || $month['status'] !== 'active') {
            throw new RuntimeException('บันทึกรายจ่ายได้เฉพาะเดือนที่กำลังใช้งาน');
        }

        $amount = (float) $data['amount'];
        if ($amount <= 0) {
            throw new RuntimeException('จำนวนเงินต้องมากกว่า 0');
        }

        $balance = $this->getCashBalance((int) $month['id']);
        if ($amount > $balance && empty($data['force'])) {
            throw new RuntimeException('ยอดจ่ายเกินเงินคงเหลือในเดือนนี้ (' . money($balance) . ' บาท)');
        }

        if (($data['category'] ?? '') === 'personal') {
            $remain = $this->getPersonalRemaining((int) $month['id']);
            if ($amount > $remain && empty($data['force'])) {
                throw new RuntimeException('เกินงบส่วนตัวที่เหลือ (' . money($remain) . ' บาท)');
            }
        }

        $this->db->beginTransaction();
        try {
            $allocationId = null;
            if (!empty($data['allocation_id'])) {
                $allocationId = (int) $data['allocation_id'];
            } elseif (($data['category'] ?? '') === 'personal') {
                $stmt = $this->db->prepare(
                    "SELECT id FROM budget_allocations WHERE month_id = ? AND category = 'personal' LIMIT 1"
                );
                $stmt->execute([$month['id']]);
                $row = $stmt->fetch();
                $allocationId = $row ? (int) $row['id'] : null;
            }

            $txnId = $this->insertTransaction([
                'month_id' => (int) $month['id'],
                'type' => 'expense',
                'category' => $data['category'],
                'loan_id' => $data['loan_id'] ?? null,
                'allocation_id' => $allocationId,
                'txn_date' => $data['txn_date'],
                'amount' => $amount,
                'description' => $data['description'] ?? '',
                'payee' => $data['payee'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($allocationId) {
                $this->db->prepare(
                    'UPDATE budget_allocations SET spent_amount = spent_amount + ? WHERE id = ?'
                )->execute([$amount, $allocationId]);
            }

            if (($data['category'] ?? '') === 'loan' && !empty($data['loan_id'])) {
                $this->reduceLoanBalance((int) $data['loan_id'], $amount);
            }

            if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $this->storeAttachment($txnId, $file);
            }

            $this->db->commit();
            return $txnId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function addIncome(array $data, ?array $file = null): int
    {
        $month = $this->getMonth((int) $data['month_id']);
        if (!$month || !in_array($month['status'], ['active', 'draft'], true)) {
            throw new RuntimeException('ไม่สามารถบันทึกรายรับได้');
        }

        $amount = (float) $data['amount'];
        if ($amount <= 0) {
            throw new RuntimeException('จำนวนเงินต้องมากกว่า 0');
        }

        $this->db->beginTransaction();
        try {
            $txnId = $this->insertTransaction([
                'month_id' => (int) $month['id'],
                'type' => 'income',
                'category' => $data['category'] ?? 'other',
                'txn_date' => $data['txn_date'],
                'amount' => $amount,
                'description' => $data['description'] ?? '',
                'payee' => $data['payee'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $this->storeAttachment($txnId, $file);
            }

            $this->db->commit();
            return $txnId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function storeAttachment(int $transactionId, array $file): int
    {
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('แนบได้เฉพาะไฟล์ JPG, PNG, WEBP หรือ PDF');
        }
        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new RuntimeException('ไฟล์ใหญ่เกิน 5 MB');
        }

        $stored = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        $dest = upload_path($stored);
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('อัปโหลดไฟล์ไม่สำเร็จ');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO attachments (transaction_id, original_name, stored_name, mime_type, file_size)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $transactionId,
            $file['name'] ?? $stored,
            $stored,
            $mime,
            (int) ($file['size'] ?? 0),
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** @return list<array<string, mixed>> */
    public function transactions(?int $monthId = null, ?string $type = null): array
    {
        $sql = 'SELECT t.*, l.name AS loan_name,
                       (SELECT COUNT(*) FROM attachments a WHERE a.transaction_id = t.id) AS attachment_count
                FROM transactions t
                LEFT JOIN loans l ON l.id = t.loan_id
                WHERE 1=1';
        $params = [];
        if ($monthId) {
            $sql .= ' AND t.month_id = ?';
            $params[] = $monthId;
        }
        if ($type) {
            $sql .= ' AND t.type = ?';
            $params[] = $type;
        }
        $sql .= ' ORDER BY t.txn_date DESC, t.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getTransaction(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, l.name AS loan_name FROM transactions t
             LEFT JOIN loans l ON l.id = t.loan_id WHERE t.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function attachmentsFor(int $transactionId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM attachments WHERE transaction_id = ? ORDER BY id');
        $stmt->execute([$transactionId]);
        return $stmt->fetchAll();
    }

    public function deleteTransaction(int $id): void
    {
        $txn = $this->getTransaction($id);
        if (!$txn) {
            throw new RuntimeException('ไม่พบรายการ');
        }

        $month = $this->getMonth((int) $txn['month_id']);
        if ($month && $month['status'] === 'closed') {
            throw new RuntimeException('ไม่สามารถลบรายการในเดือนที่ปิดแล้ว');
        }

        $this->db->beginTransaction();
        try {
            $atts = $this->attachmentsFor($id);
            foreach ($atts as $att) {
                $path = upload_path($att['stored_name']);
                if (is_file($path)) {
                    unlink($path);
                }
            }

            $this->revertTransactionSideEffects($txn);
            $this->db->prepare('DELETE FROM transactions WHERE id = ?')->execute([$id]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** @param array<string, mixed> $txn */
    private function revertTransactionSideEffects(array $txn): void
    {
        if ($txn['type'] === 'expense' && $txn['allocation_id']) {
            $this->db->prepare(
                'UPDATE budget_allocations SET spent_amount = GREATEST(0, spent_amount - ?) WHERE id = ?'
            )->execute([(float) $txn['amount'], $txn['allocation_id']]);
        }

        if ($txn['type'] === 'expense' && $txn['category'] === 'loan' && $txn['loan_id']) {
            $this->db->prepare(
                'UPDATE loans SET balance = balance + ? WHERE id = ?'
            )->execute([(float) $txn['amount'], $txn['loan_id']]);
        }
    }

    public function updateTransaction(int $id, array $data, ?array $file = null): void
    {
        $txn = $this->getTransaction($id);
        if (!$txn) {
            throw new RuntimeException('ไม่พบรายการ');
        }

        $month = $this->getMonth((int) $txn['month_id']);
        if (!$month || $month['status'] === 'closed') {
            throw new RuntimeException('แก้ไขได้เฉพาะเดือนที่ยังไม่ปิด');
        }

        $amount = (float) $data['amount'];
        if ($amount <= 0) {
            throw new RuntimeException('จำนวนเงินต้องมากกว่า 0');
        }

        $type = $data['type'] ?? $txn['type'];
        $category = $data['category'] ?? $txn['category'];
        $loanId = ($data['loan_id'] ?? '') !== '' && ($data['loan_id'] ?? null) !== null
            ? (int) $data['loan_id']
            : null;

        // ตรวจกรอบงบจากยอดหลังแก้ (คืนยอดเดิมก่อนคิด)
        if ($type === 'expense') {
            if ($txn['type'] === 'income') {
                $balanceWithout = $this->getCashBalance((int) $month['id']) - (float) $txn['amount'];
            } else {
                $balanceWithout = $this->getCashBalance((int) $month['id']) + (float) $txn['amount'];
            }
            if ($amount > $balanceWithout && empty($data['force'])) {
                throw new RuntimeException('ยอดจ่ายเกินเงินคงเหลือหลังแก้ไข (' . money($balanceWithout) . ' บาท)');
            }
        }

        $this->db->beginTransaction();
        try {
            $this->revertTransactionSideEffects($txn);

            $allocationId = null;
            if ($type === 'expense') {
                if ($category === 'personal') {
                    $stmt = $this->db->prepare(
                        "SELECT id, planned_amount, spent_amount FROM budget_allocations
                         WHERE month_id = ? AND category = 'personal' LIMIT 1"
                    );
                    $stmt->execute([$month['id']]);
                    $personal = $stmt->fetch();
                    if ($personal) {
                        $allocationId = (int) $personal['id'];
                        $remain = (float) $personal['planned_amount'] - (float) $personal['spent_amount'];
                        if ($amount > $remain && empty($data['force'])) {
                            throw new RuntimeException('เกินงบส่วนตัวที่เหลือ (' . money($remain) . ' บาท)');
                        }
                    }
                } elseif ($category === 'mother') {
                    $stmt = $this->db->prepare(
                        "SELECT id FROM budget_allocations WHERE month_id = ? AND category = 'mother' LIMIT 1"
                    );
                    $stmt->execute([$month['id']]);
                    $row = $stmt->fetch();
                    $allocationId = $row ? (int) $row['id'] : null;
                } elseif ($category === 'loan' && $loanId) {
                    $stmt = $this->db->prepare(
                        "SELECT id FROM budget_allocations WHERE month_id = ? AND category = 'loan' AND loan_id = ? LIMIT 1"
                    );
                    $stmt->execute([$month['id'], $loanId]);
                    $row = $stmt->fetch();
                    $allocationId = $row ? (int) $row['id'] : null;
                }
            }

            $upd = $this->db->prepare(
                'UPDATE transactions SET
                    type = ?, category = ?, loan_id = ?, allocation_id = ?,
                    txn_date = ?, amount = ?, description = ?, payee = ?,
                    payment_method = ?, reference_no = ?, notes = ?
                 WHERE id = ?'
            );
            $upd->execute([
                $type,
                $category,
                $loanId,
                $allocationId,
                $data['txn_date'],
                $amount,
                $data['description'] ?? '',
                $data['payee'] ?? null,
                $data['payment_method'] ?? null,
                $data['reference_no'] ?? null,
                $data['notes'] ?? null,
                $id,
            ]);

            if ($type === 'expense' && $allocationId) {
                $this->db->prepare(
                    'UPDATE budget_allocations SET spent_amount = spent_amount + ? WHERE id = ?'
                )->execute([$amount, $allocationId]);
            }

            if ($type === 'expense' && $category === 'loan' && $loanId) {
                $this->reduceLoanBalance($loanId, $amount);
            }

            if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $this->storeAttachment($id, $file);
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * สรุปยอดรายจ่ายแยกหมวดสำหรับกราฟวงกลม
     * @return list<array{category: string, label: string, total: float}>
     */
    public function expenseBreakdownByCategory(?int $monthId): array
    {
        if (!$monthId) {
            return [];
        }
        $stmt = $this->db->prepare(
            "SELECT category, COALESCE(SUM(amount), 0) AS total
             FROM transactions
             WHERE month_id = ? AND type = 'expense'
             GROUP BY category
             HAVING total > 0
             ORDER BY total DESC"
        );
        $stmt->execute([$monthId]);
        $rows = $stmt->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'category' => $row['category'],
                'label' => category_label($row['category']),
                'total' => (float) $row['total'],
            ];
        }
        return $out;
    }

    /**
     * สรุปยอดรายจ่ายสินเชื่อแยกวงเงิน
     * @return list<array{label: string, total: float}>
     */
    public function loanExpenseBreakdown(?int $monthId): array
    {
        if (!$monthId) {
            return [];
        }
        $stmt = $this->db->prepare(
            "SELECT COALESCE(l.name, 'สินเชื่อ') AS label, COALESCE(SUM(t.amount), 0) AS total
             FROM transactions t
             LEFT JOIN loans l ON l.id = t.loan_id
             WHERE t.month_id = ? AND t.type = 'expense' AND t.category = 'loan'
             GROUP BY t.loan_id, l.name
             HAVING total > 0
             ORDER BY total DESC"
        );
        $stmt->execute([$monthId]);
        return array_map(static fn ($r) => [
            'label' => (string) $r['label'],
            'total' => (float) $r['total'],
        ], $stmt->fetchAll());
    }

    public function closeMonth(int $monthId): array
    {
        $month = $this->getMonth($monthId);
        if (!$month || $month['status'] !== 'active') {
            throw new RuntimeException('ปิดได้เฉพาะเดือนที่กำลังใช้งาน');
        }

        $cash = $this->getCashBalance($monthId);
        $savings = $this->projectedSavings($cash);
        $carry = round(max(0, $cash - $savings), 2);

        $this->db->beginTransaction();
        try {
            if ($savings > 0) {
                $this->insertTransaction([
                    'month_id' => $monthId,
                    'type' => 'expense',
                    'category' => 'savings',
                    'txn_date' => date('Y-m-t', strtotime($month['budget_ym'] . '-01')),
                    'amount' => $savings,
                    'description' => 'ออม ' . $this->savingsRate() . '% จากยอดคงเหลือ',
                ]);
            }

            $stmt = $this->db->prepare(
                "UPDATE budget_months
                 SET status = 'closed', savings_amount = ?, carry_forward = ?, closed_at = NOW(), wizard_step = 5
                 WHERE id = ?"
            );
            $stmt->execute([$savings, $carry, $monthId]);

            // สร้างเดือนถัดไปแบบ draft
            $nextYm = date('Y-m', strtotime($month['budget_ym'] . '-01 +1 month'));
            if (!$this->getMonthByYm($nextYm)) {
                $salary = $this->salaryAmount();
                $available = $carry + $salary;
                $ins = $this->db->prepare(
                    "INSERT INTO budget_months
                     (budget_ym, opening_balance, salary_amount, available_total, status, wizard_step)
                     VALUES (?, ?, ?, ?, 'draft', 1)"
                );
                $ins->execute([$nextYm, $carry, $salary, $available]);
            }

            $this->db->commit();
            return $this->getMonth($monthId);
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function dashboardStats(?array $month): array
    {
        if (!$month) {
            return [
                'available' => 0,
                'spent' => 0,
                'income' => 0,
                'balance' => 0,
                'personal_planned' => 0,
                'personal_spent' => 0,
                'personal_remain' => 0,
                'planned_total' => 0,
                'projected_savings' => 0,
                'projected_carry' => 0,
            ];
        }

        $id = (int) $month['id'];
        $incomeStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount),0) AS total FROM transactions WHERE month_id = ? AND type = 'income'"
        );
        $incomeStmt->execute([$id]);
        $income = (float) $incomeStmt->fetch()['total'];

        $expenseStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount),0) AS total FROM transactions WHERE month_id = ? AND type = 'expense'"
        );
        $expenseStmt->execute([$id]);
        $spent = (float) $expenseStmt->fetch()['total'];

        $balance = $income - $spent;
        $personalRemain = $this->getPersonalRemaining($id);
        $personalStmt = $this->db->prepare(
            "SELECT planned_amount, spent_amount FROM budget_allocations WHERE month_id = ? AND category = 'personal' LIMIT 1"
        );
        $personalStmt->execute([$id]);
        $personal = $personalStmt->fetch() ?: ['planned_amount' => 0, 'spent_amount' => 0];

        $remainingPlan = $this->remainingAfterPlan($month);
        $projSave = $this->projectedSavings($month['status'] === 'active' ? $balance : $remainingPlan);
        $projCarry = ($month['status'] === 'active' ? $balance : $remainingPlan) - $projSave;

        return [
            'available' => (float) $month['available_total'],
            'spent' => $spent,
            'income' => $income,
            'balance' => $balance,
            'personal_planned' => (float) $personal['planned_amount'],
            'personal_spent' => (float) $personal['spent_amount'],
            'personal_remain' => $personalRemain,
            'planned_total' => $this->plannedTotal($id),
            'projected_savings' => max(0, $projSave),
            'projected_carry' => max(0, $projCarry),
        ];
    }

    public function updateLoan(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE loans SET name = ?, principal = ?, interest_rate = ?, term_months = ?,
             expected_payment = ?, balance = ?, is_active = ?, notes = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['name'],
            $data['principal'],
            $data['interest_rate'],
            $data['term_months'],
            $data['expected_payment'],
            $data['balance'],
            $data['is_active'] ?? 1,
            $data['notes'] ?? null,
            $id,
        ]);
    }

    public function getLoan(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM loans WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function loanPayments(int $loanId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM transactions WHERE loan_id = ? AND type = 'expense' ORDER BY txn_date DESC, id DESC"
        );
        $stmt->execute([$loanId]);
        return $stmt->fetchAll();
    }
}
