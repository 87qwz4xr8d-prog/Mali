<?php
/** @var BudgetService $budget */
/** @var array|null $month */
/** @var array $stats */
/** @var list $loans */
/** @var list $recent */
ob_start();
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h2 class="page-title">แดชบอร์ด</h2>
        <p class="page-sub mb-0">
            <?php if ($month): ?>
                เดือน<?= e(thai_month($month['budget_ym'])) ?>
                <span class="badge text-bg-<?= status_badge($month['status']) ?>"><?= e(status_label($month['status'])) ?></span>
            <?php else: ?>
                ยังไม่มีเดือนงบประมาณ — เริ่มจากเปิดเดือนแบบทีละขั้นตอน
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <?php if (!$month || $month['status'] === 'draft'): ?>
            <a href="index.php?page=wizard" class="btn btn-mali"><i class="fa-solid fa-play me-1"></i> เปิดเดือน</a>
        <?php elseif ($month['status'] === 'active'): ?>
            <a href="index.php?page=transactions&action=create" class="btn btn-mali"><i class="fa-solid fa-plus me-1"></i> บันทึกรายการ</a>
            <form method="post" action="index.php?page=months&action=close" id="close-month-form" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="month_id" value="<?= (int) $month['id'] ?>">
                <button type="button" class="btn btn-outline-dark" data-confirm="ปิดเดือนนี้? ระบบจะตัดออม 5% และยกยอดไปเดือนถัดไป" data-form="close-month-form">
                    <i class="fa-solid fa-flag-checkered me-1"></i> ปิดเดือน
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="label"><i class="fa-solid fa-wallet me-1"></i> ยอดใช้ได้</div>
            <div class="value text-success"><?= money($stats['available']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="label"><i class="fa-solid fa-arrow-down me-1"></i> ใช้ไปแล้ว</div>
            <div class="value"><?= money($stats['spent']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="label"><i class="fa-solid fa-coins me-1"></i> คงเหลือเงินสด</div>
            <div class="value"><?= money($stats['balance']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="label"><i class="fa-solid fa-piggy-bank me-1"></i> คาดการณ์ออม / ยกไป</div>
            <div class="value" style="font-size:1.1rem;">
                <?= money($stats['projected_savings']) ?>
                <span class="text-muted fw-normal">/</span>
                <?= money($stats['projected_carry']) ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="panel h-100">
            <h5 class="mb-3"><i class="fa-solid fa-chart-pie me-2 text-success"></i>สัดส่วนรายจ่ายตามหมวด</h5>
            <?php if (empty($expenseBreakdown)): ?>
                <p class="text-muted mb-0">ยังไม่มีรายจ่ายในเดือนนี้</p>
            <?php else: ?>
                <div class="chart-wrap">
                    <canvas id="expensePieChart" height="220"></canvas>
                </div>
                <ul class="list-unstyled small mt-3 mb-0">
                    <?php
                    $expenseTotal = array_sum(array_column($expenseBreakdown, 'total'));
                    foreach ($expenseBreakdown as $row):
                        $share = $expenseTotal > 0 ? round(($row['total'] / $expenseTotal) * 100, 1) : 0;
                    ?>
                        <li class="d-flex justify-content-between py-1 border-bottom border-light">
                            <span><?= e($row['label']) ?></span>
                            <span><?= money($row['total']) ?> <span class="text-muted">(<?= $share ?>%)</span></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="panel h-100">
            <h5 class="mb-3"><i class="fa-solid fa-user me-2 text-success"></i>งบส่วนตัว</h5>
            <?php
            $planned = max(0.01, $stats['personal_planned']);
            $pct = min(100, round(($stats['personal_spent'] / $planned) * 100));
            ?>
            <div class="d-flex justify-content-between mb-1">
                <span>ใช้ไป <?= money($stats['personal_spent']) ?> / <?= money($stats['personal_planned']) ?></span>
                <strong>เหลือ <?= money($stats['personal_remain']) ?></strong>
            </div>
            <div class="progress mb-3">
                <div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div>
            </div>
            <p class="small text-muted mb-0">ให้คุณแม่และงวดสินเชื่อถูกกันงบไว้ตอนเปิดเดือนแล้ว — บันทึกรายวันเน้นซองส่วนตัว</p>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="panel h-100">
            <h5 class="mb-3"><i class="fa-solid fa-building-columns me-2 text-success"></i>สัดส่วนชำระสินเชื่อ</h5>
            <?php if (empty($loanBreakdown)): ?>
                <p class="text-muted mb-0">ยังไม่มีรายการชำระสินเชื่อในเดือนนี้</p>
            <?php else: ?>
                <div class="chart-wrap">
                    <canvas id="loanPieChart" height="220"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="fa-solid fa-building-columns me-2 text-success"></i>สินเชื่อ</h5>
                <a href="index.php?page=loans" class="small">ดูทั้งหมด</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                    <tr>
                        <th>วงเงิน</th>
                        <th class="text-end">งวด/เดือน</th>
                        <th class="text-end">คงเหลือ</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($loans as $loan): ?>
                        <tr>
                            <td><?= e($loan['name']) ?></td>
                            <td class="text-end"><?= money($loan['expected_payment']) ?></td>
                            <td class="text-end"><?= money($loan['balance']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="fa-solid fa-clock-rotate-left me-2 text-success"></i>รายการล่าสุด</h5>
        <a href="index.php?page=transactions" class="small">ดูทั้งหมด</a>
    </div>
    <?php if (!$recent): ?>
        <p class="text-muted mb-0">ยังไม่มีรายการ</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>วันที่</th>
                    <th>ประเภท</th>
                    <th>หมวด</th>
                    <th>รายละเอียด</th>
                    <th class="text-end">จำนวน</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recent as $txn): ?>
                    <tr>
                        <td><?= e($txn['txn_date']) ?></td>
                        <td>
                            <?php if ($txn['type'] === 'income'): ?>
                                <span class="badge text-bg-success">รับ</span>
                            <?php else: ?>
                                <span class="badge text-bg-danger">จ่าย</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e(category_label($txn['category'])) ?></td>
                        <td>
                            <?= e($txn['description']) ?>
                            <?php if (!empty($txn['payee'])): ?>
                                <div class="small text-muted"><?= e($txn['payee']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end <?= $txn['type'] === 'income' ? 'text-success' : '' ?>">
                            <?= $txn['type'] === 'income' ? '+' : '-' ?><?= money($txn['amount']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($expenseBreakdown) || !empty($loanBreakdown)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(() => {
  const palette = ['#1f7a4d', '#3cb371', '#0e4d33', '#c47a12', '#5f7268', '#2a9d8f', '#e76f51', '#264653'];
  const makePie = (canvasId, labels, values) => {
    const el = document.getElementById(canvasId);
    if (!el) return;
    new Chart(el, {
      type: 'pie',
      data: {
        labels,
        datasets: [{
          data: values,
          backgroundColor: labels.map((_, i) => palette[i % palette.length]),
          borderWidth: 1,
          borderColor: '#fff',
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { boxWidth: 12, font: { family: 'Sarabun' } } },
          tooltip: {
            callbacks: {
              label: (ctx) => {
                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                const val = ctx.parsed;
                const pct = total ? ((val / total) * 100).toFixed(1) : 0;
                return ` ${ctx.label}: ${Number(val).toLocaleString('th-TH', { minimumFractionDigits: 2 })} (${pct}%)`;
              },
            },
          },
        },
      },
    });
  };

  <?php if (!empty($expenseBreakdown)): ?>
  makePie(
    'expensePieChart',
    <?= json_encode(array_column($expenseBreakdown, 'label'), JSON_UNESCAPED_UNICODE) ?>,
    <?= json_encode(array_map('floatval', array_column($expenseBreakdown, 'total'))) ?>
  );
  <?php endif; ?>

  <?php if (!empty($loanBreakdown)): ?>
  makePie(
    'loanPieChart',
    <?= json_encode(array_column($loanBreakdown, 'label'), JSON_UNESCAPED_UNICODE) ?>,
    <?= json_encode(array_map('floatval', array_column($loanBreakdown, 'total'))) ?>
  );
  <?php endif; ?>
})();
</script>
<?php endif; ?>
<?php
$content = ob_get_clean();
$title = 'แดชบอร์ด';
$active = 'dashboard';
require base_path('views/layout.php');
