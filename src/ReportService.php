<?php

declare(strict_types=1);

final class ReportService
{
    private BudgetService $budget;

    public function __construct(?BudgetService $budget = null)
    {
        $this->budget = $budget ?? new BudgetService();
    }

    public function budget(): BudgetService
    {
        return $this->budget;
    }

    /**
     * รวบรวมข้อมูลรายงานรายเดือน
     * @return array<string, mixed>
     */
    public function buildMonthlyReport(?int $monthId): array
    {
        $month = $monthId ? $this->budget->getMonth($monthId) : $this->budget->currentMonth();
        if (!$month) {
            throw new RuntimeException('ยังไม่มีข้อมูลเดือนสำหรับออกรายงาน');
        }

        $id = (int) $month['id'];
        $stats = $this->budget->dashboardStats($month);
        $transactions = $this->budget->transactions($id);
        $allocations = $this->budget->allocations($id);
        $expenseBreakdown = $this->budget->expenseBreakdownByCategory($id);
        $loanBreakdown = $this->budget->loanExpenseBreakdown($id);
        $loans = $this->budget->activeLoans();

        $incomes = array_values(array_filter($transactions, static fn ($t) => $t['type'] === 'income'));
        $expenses = array_values(array_filter($transactions, static fn ($t) => $t['type'] === 'expense'));

        return [
            'generated_at' => date('Y-m-d H:i:s'),
            'app_name' => $this->budget->getSetting('app_name', 'Mali'),
            'month' => $month,
            'month_label' => thai_month($month['budget_ym']),
            'stats' => $stats,
            'transactions' => $transactions,
            'incomes' => $incomes,
            'expenses' => $expenses,
            'allocations' => $allocations,
            'expense_breakdown' => $expenseBreakdown,
            'loan_breakdown' => $loanBreakdown,
            'loans' => $loans,
            'salary' => (float) $month['salary_amount'],
            'opening' => (float) $month['opening_balance'],
            'savings_rate' => $this->budget->savingsRate(),
        ];
    }

    public function filename(array $report, string $ext): string
    {
        $ym = preg_replace('/[^0-9\-]/', '', (string) $report['month']['budget_ym']);
        return 'Mali-report-' . $ym . '-' . date('Ymd-His') . '.' . ltrim($ext, '.');
    }
}
