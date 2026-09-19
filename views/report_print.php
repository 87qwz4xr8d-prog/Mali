<?php
/** @var array $report */
/** @var ReportExporter $exporter */
$html = $exporter->reportHtml($report, true);
// inject print toolbar
$toolbar = <<<HTML
<div class="no-print" style="position:sticky;top:0;background:#fff;border-bottom:1px solid #d7e3db;padding:10px 16px;margin-bottom:16px;font-family:Sarabun,sans-serif">
  <strong>ตัวอย่างก่อนพิมพ์</strong>
  <button onclick="window.print()" style="margin-left:12px;padding:6px 14px;background:#1f7a4d;color:#fff;border:0;border-radius:6px;cursor:pointer">พิมพ์ / Save as PDF</button>
  <a href="index.php?page=reports&month_id={$report['month']['id']}" style="margin-left:8px">กลับ</a>
</div>
HTML;
echo str_replace('<body>', '<body>' . $toolbar, $html);
