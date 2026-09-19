<?php

declare(strict_types=1);

use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\IOFactory as PptIOFactory;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Alignment as PptAlignment;
use PhpOffice\PhpPresentation\Style\Fill as PptFill;

final class ReportExporter
{
    /** @param array<string, mixed> $report */
    public function toPdf(array $report): string
    {
        $html = $this->reportHtml($report, false);
        $tmp = sys_get_temp_dir() . '/mali-mpdf';
        if (!is_dir($tmp)) {
            mkdir($tmp, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'garuda',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 14,
            'margin_bottom' => 14,
            'tempDir' => $tmp,
        ]);
        $mpdf->SetTitle('รายงาน ' . $report['month_label']);
        $mpdf->WriteHTML($html);
        return $mpdf->Output('', 'S');
    }

    /** @param array<string, mixed> $report */
    public function toExcel(array $report): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('สรุป');

        $sheet->setCellValue('A1', $report['app_name'] . ' — รายงานรายรับ-รายจ่าย');
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A2', 'เดือน' . $report['month_label'] . ' · สถานะ ' . status_label($report['month']['status']));
        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A3', 'สร้างเมื่อ ' . $report['generated_at']);

        $sheet->fromArray([
            ['รายการ', 'จำนวน (บาท)'],
            ['ยอดยกมา', $report['opening']],
            ['เงินเดือน', $report['salary']],
            ['ยอดใช้ได้', $report['stats']['available']],
            ['รายรับรวม', $report['stats']['income']],
            ['รายจ่ายรวม', $report['stats']['spent']],
            ['คงเหลือเงินสด', $report['stats']['balance']],
            ['คาดการณ์ออม ' . $report['savings_rate'] . '%', $report['stats']['projected_savings']],
            ['คาดการณ์ยกไป', $report['stats']['projected_carry']],
        ], null, 'A5');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A5:B5')->getFont()->setBold(true);
        $sheet->getStyle('A5:B5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F7A4D');
        $sheet->getStyle('A5:B5')->getFont()->getColor()->setRGB('FFFFFF');

        // Sheet รายการ
        $detail = $spreadsheet->createSheet();
        $detail->setTitle('รายการ');
        $headers = ['วันที่', 'ประเภท', 'หมวด', 'รายละเอียด', 'ผู้รับ/ร้าน', 'ช่องทาง', 'อ้างอิง', 'จำนวน', 'หมายเหตุ'];
        $detail->fromArray($headers, null, 'A1');
        $detail->getStyle('A1:I1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $detail->getStyle('A1:I1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F7A4D');

        $row = 2;
        foreach ($report['transactions'] as $txn) {
            $detail->fromArray([
                $txn['txn_date'],
                $txn['type'] === 'income' ? 'รายรับ' : 'รายจ่าย',
                category_label($txn['category']),
                $txn['description'],
                $txn['payee'] ?? '',
                payment_method_label($txn['payment_method'] ?? null),
                $txn['reference_no'] ?? '',
                (float) $txn['amount'],
                $txn['notes'] ?? '',
            ], null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'I') as $col) {
            $detail->getColumnDimension($col)->setAutoSize(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Sheet สัดส่วนรายจ่าย
        $pie = $spreadsheet->createSheet();
        $pie->setTitle('สัดส่วนรายจ่าย');
        $pie->fromArray(['หมวด', 'จำนวน'], null, 'A1');
        $pie->getStyle('A1:B1')->getFont()->setBold(true);
        $r = 2;
        foreach ($report['expense_breakdown'] as $item) {
            $pie->fromArray([$item['label'], $item['total']], null, 'A' . $r);
            $r++;
        }

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        $tmpFile = tempnam(sys_get_temp_dir(), 'mali-xlsx-');
        $writer->save($tmpFile);
        $content = file_get_contents($tmpFile) ?: '';
        unlink($tmpFile);
        $spreadsheet->disconnectWorksheets();
        return $content;
    }

    /** @param array<string, mixed> $report */
    public function toPowerPoint(array $report): string
    {
        $ppt = new PhpPresentation();
        $ppt->getDocumentProperties()
            ->setCreator('Mali')
            ->setTitle('สรุปรายรับ-รายจ่าย ' . $report['month_label'])
            ->setSubject('รายงานภาพรวมประจำเดือน');

        // Slide 1: Cover
        $slide = $ppt->getActiveSlide();
        $this->fillSlide($slide, '1F7A4D');
        $this->addText($slide, $report['app_name'], 40, true, 'FFFFFF', 60, 120, 800, 80);
        $this->addText($slide, 'สรุปรายรับ-รายจ่ายประจำเดือน', 22, false, 'E8F5EE', 60, 210, 800, 40);
        $this->addText($slide, $report['month_label'], 28, true, 'FFFFFF', 60, 270, 800, 50);
        $this->addText($slide, 'สร้างเมื่อ ' . $report['generated_at'], 14, false, 'CDE8D8', 60, 450, 800, 30);

        // Slide 2: Overview numbers
        $slide2 = $ppt->createSlide();
        $this->fillSlide($slide2, 'F3F6F4');
        $this->addText($slide2, 'ภาพรวมยอดเงิน', 28, true, '123528', 40, 30, 800, 40);
        $cards = [
            ['ยอดใช้ได้', money($report['stats']['available'])],
            ['รายรับรวม', money($report['stats']['income'])],
            ['รายจ่ายรวม', money($report['stats']['spent'])],
            ['คงเหลือ', money($report['stats']['balance'])],
        ];
        $x = 40;
        foreach ($cards as $card) {
            $shape = $slide2->createRichTextShape()->setHeight(120)->setWidth(200)->setOffsetX($x)->setOffsetY(120);
            $shape->getFill()->setFillType(PptFill::FILL_SOLID)->setStartColor(new Color('FFFFFFFF'));
            $shape->getActiveParagraph()->getAlignment()->setHorizontal(PptAlignment::HORIZONTAL_CENTER);
            $run = $shape->createTextRun($card[0] . "\n");
            $run->getFont()->setSize(12)->setColor(new Color('FF5F7268'));
            $run2 = $shape->createTextRun($card[1]);
            $run2->getFont()->setBold(true)->setSize(18)->setColor(new Color('FF1F7A4D'));
            $x += 220;
        }
        $this->addText(
            $slide2,
            'คาดการณ์ออม ' . $report['savings_rate'] . '% = ' . money($report['stats']['projected_savings'])
            . ' บาท · ยกไปเดือนถัดไป ' . money($report['stats']['projected_carry']) . ' บาท',
            14,
            false,
            '123528',
            40,
            280,
            860,
            40
        );

        // Slide 3: Expense breakdown
        $slide3 = $ppt->createSlide();
        $this->fillSlide($slide3, 'F3F6F4');
        $this->addText($slide3, 'สัดส่วนรายจ่ายตามหมวด', 28, true, '123528', 40, 30, 800, 40);
        $y = 100;
        $totalExp = array_sum(array_column($report['expense_breakdown'], 'total')) ?: 1;
        if (!$report['expense_breakdown']) {
            $this->addText($slide3, 'ยังไม่มีรายจ่ายในเดือนนี้', 16, false, '5F7268', 40, 120, 800, 30);
        } else {
            foreach ($report['expense_breakdown'] as $item) {
                $pct = round(($item['total'] / $totalExp) * 100, 1);
                $this->addText(
                    $slide3,
                    $item['label'] . ' — ' . money($item['total']) . ' บาท (' . $pct . '%)',
                    16,
                    false,
                    '123528',
                    60,
                    $y,
                    800,
                    28
                );
                $y += 36;
            }
        }

        // Slide 4: Loans
        $slide4 = $ppt->createSlide();
        $this->fillSlide($slide4, 'F3F6F4');
        $this->addText($slide4, 'สถานะสินเชื่อ', 28, true, '123528', 40, 30, 800, 40);
        $y = 100;
        foreach ($report['loans'] as $loan) {
            $this->addText(
                $slide4,
                $loan['name'] . ' · คาดจ่าย/เดือน ' . money($loan['expected_payment'])
                . ' · คงเหลือ ' . money($loan['balance']),
                14,
                false,
                '123528',
                50,
                $y,
                860,
                26
            );
            $y += 32;
        }

        // Slide 5: Closing
        $slide5 = $ppt->createSlide();
        $this->fillSlide($slide5, '0E4D33');
        $this->addText($slide5, 'สรุปท้ายเดือน', 30, true, 'FFFFFF', 60, 140, 800, 50);
        $this->addText(
            $slide5,
            'รายรับ ' . money($report['stats']['income']) . ' บาท'
            . "\nรายจ่าย " . money($report['stats']['spent']) . ' บาท'
            . "\nคงเหลือ " . money($report['stats']['balance']) . ' บาท',
            20,
            false,
            'E8F5EE',
            60,
            220,
            800,
            120
        );
        $this->addText($slide5, 'จัดทำโดยระบบ ' . $report['app_name'], 14, false, '9BC9AE', 60, 420, 800, 30);

        $tmpFile = tempnam(sys_get_temp_dir(), 'mali-pptx-') . '.pptx';
        $writer = PptIOFactory::createWriter($ppt, 'PowerPoint2007');
        $writer->save($tmpFile);
        $content = file_get_contents($tmpFile) ?: '';
        unlink($tmpFile);
        return $content;
    }

    /** @param array<string, mixed> $report */
    public function reportHtml(array $report, bool $forPrint = true): string
    {
        $stats = $report['stats'];
        $rows = '';
        foreach ($report['transactions'] as $txn) {
            $rows .= '<tr>'
                . '<td>' . htmlspecialchars($txn['txn_date']) . '</td>'
                . '<td>' . ($txn['type'] === 'income' ? 'รายรับ' : 'รายจ่าย') . '</td>'
                . '<td>' . htmlspecialchars(category_label($txn['category'])) . '</td>'
                . '<td>' . htmlspecialchars($txn['description'])
                . (!empty($txn['payee']) ? '<br><small>' . htmlspecialchars((string) $txn['payee']) . '</small>' : '')
                . '</td>'
                . '<td style="text-align:right">' . money($txn['amount']) . '</td>'
                . '</tr>';
        }

        $breakdown = '';
        $expTotal = array_sum(array_column($report['expense_breakdown'], 'total')) ?: 1;
        foreach ($report['expense_breakdown'] as $item) {
            $pct = round(($item['total'] / $expTotal) * 100, 1);
            $breakdown .= '<tr><td>' . htmlspecialchars($item['label']) . '</td>'
                . '<td style="text-align:right">' . money($item['total']) . '</td>'
                . '<td style="text-align:right">' . $pct . '%</td></tr>';
        }

        $printCss = $forPrint ? '@media print { .no-print { display:none !important; } }' : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>รายงาน {$report['month_label']}</title>
<style>
body { font-family: 'Garuda', 'Sarabun', DejaVu Sans, sans-serif; font-size: 12px; color: #1a2e24; }
h1 { font-size: 20px; margin: 0 0 4px; color: #123528; }
h2 { font-size: 14px; margin: 18px 0 8px; color: #1f7a4d; border-bottom: 1px solid #d7e3db; padding-bottom: 4px; }
.meta { color: #5f7268; margin-bottom: 16px; }
.cards { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
.cards td { border: 1px solid #d7e3db; padding: 8px; width: 25%; }
.cards .label { color: #5f7268; font-size: 11px; }
.cards .value { font-size: 15px; font-weight: bold; }
table.data { width: 100%; border-collapse: collapse; }
table.data th, table.data td { border: 1px solid #d7e3db; padding: 5px 6px; }
table.data th { background: #1f7a4d; color: #fff; text-align: left; }
{$printCss}
</style>
</head>
<body>
<h1>{$report['app_name']} — รายงานรายรับ-รายจ่าย</h1>
<div class="meta">เดือน{$report['month_label']} · สถานะ {$this->statusText($report)} · สร้างเมื่อ {$report['generated_at']}</div>

<table class="cards">
<tr>
<td><div class="label">ยอดใช้ได้</div><div class="value">{$this->m($stats['available'])}</div></td>
<td><div class="label">รายรับรวม</div><div class="value">{$this->m($stats['income'])}</div></td>
<td><div class="label">รายจ่ายรวม</div><div class="value">{$this->m($stats['spent'])}</div></td>
<td><div class="label">คงเหลือ</div><div class="value">{$this->m($stats['balance'])}</div></td>
</tr>
</table>

<p>ยอดยกมา {$this->m($report['opening'])} · เงินเดือน {$this->m($report['salary'])}
 · คาดการณ์ออม {$report['savings_rate']}% = {$this->m($stats['projected_savings'])}
 · ยกไป {$this->m($stats['projected_carry'])}</p>

<h2>สัดส่วนรายจ่ายตามหมวด</h2>
<table class="data">
<thead><tr><th>หมวด</th><th style="text-align:right">จำนวน</th><th style="text-align:right">สัดส่วน</th></tr></thead>
<tbody>{$breakdown}</tbody>
</table>

<h2>รายการรับ-จ่ายทั้งหมด</h2>
<table class="data">
<thead><tr><th>วันที่</th><th>ประเภท</th><th>หมวด</th><th>รายละเอียด</th><th style="text-align:right">จำนวน</th></tr></thead>
<tbody>{$rows}</tbody>
</table>
</body>
</html>
HTML;
    }

    private function m(float|int|string $n): string
    {
        return money($n);
    }

    /** @param array<string, mixed> $report */
    private function statusText(array $report): string
    {
        return status_label($report['month']['status']);
    }

    private function fillSlide($slide, string $rgb): void
    {
        $argb = strlen($rgb) === 6 ? 'FF' . $rgb : $rgb;
        $bg = new \PhpOffice\PhpPresentation\Slide\Background\Color();
        $bg->setColor(new Color($argb));
        $slide->setBackground($bg);
    }

    private function addText($slide, string $text, int $size, bool $bold, string $color, int $x, int $y, int $w, int $h): void
    {
        $argb = strlen($color) === 6 ? 'FF' . $color : $color;
        $shape = $slide->createRichTextShape()
            ->setHeight($h)
            ->setWidth($w)
            ->setOffsetX($x)
            ->setOffsetY($y);
        $shape->getActiveParagraph()->getAlignment()->setHorizontal(PptAlignment::HORIZONTAL_LEFT);
        $run = $shape->createTextRun($text);
        $run->getFont()
            ->setBold($bold)
            ->setSize($size)
            ->setColor(new Color($argb))
            ->setName('Arial');
    }
}
