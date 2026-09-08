<?php

namespace App\Exports\Concerns;

use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

trait RegistersStandardExportSheet
{
    /**
     * @return array<class-string, callable>
     */
    protected function registerStandardExportSheetEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                $isRtl = in_array(app()->getLocale(), ['ar', 'fa', 'he', 'ur'], true);

                $sheet->getDelegate()->getParent()
                    ->getDefaultStyle()
                    ->getFont()
                    ->setName('DejaVu Sans')
                    ->setSize(10);

                // Report user
                $generatedBy = (Auth::user()->first_name.' '.Auth::user()->last_name) ?? 'System';

                // Convert to timezone
                $generatedAt = formatDateTimeWithTimezone(now());

                // Get the last column based on number of columns
                $lastColumn = $this->getLastColumn();

                // Set report info in merged cells
                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->setCellValue('A1', $this->reportName);

                $currentRow = 2;

                // Only show type if it's available and not empty
                if (! empty($this->type)) {
                    $sheet->mergeCells("A{$currentRow}:{$lastColumn}{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", __('messages.lbl_type').': '.ucfirst((string) $this->type));
                    $currentRow++;
                }

                $sheet->mergeCells("A{$currentRow}:{$lastColumn}{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", __('messages.lbl_generated_by').': '.$generatedBy);
                $currentRow++;

                $sheet->mergeCells("A{$currentRow}:{$lastColumn}{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", __('messages.lbl_generated_on').': '.$generatedAt);

                // Apply styles
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                // Keep left alignment but add a small left indent so header text isn't stuck to the border.
                $sheet->getStyle("A1:A{$currentRow}")->getAlignment()
                    ->setHorizontal($isRtl ? 'right' : 'left')
                    ->setIndent(1);
                // Bold headings row dynamically
                $headingsRow = ! empty($this->type) ? 5 : 4;
                $sheet->getStyle("A{$headingsRow}:{$lastColumn}{$headingsRow}")->getFont()->setBold(true);

                // Remove borders from report info section
                $sheet->getStyle("A1:{$lastColumn}{$currentRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_NONE,
                        ],
                    ],
                ]);

                // PDF/print rendering setup
                $worksheet = $sheet->getDelegate();
                $pageSetup = $worksheet->getPageSetup();
                $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
                    ->setHorizontalCentered(true)
                    ->setVerticalCentered(false);

                $columnCount = count($this->columns);

                // Tight margins to utilize page area
                $worksheet->getPageMargins()
                    ->setTop(0.25)
                    ->setBottom(0.25)
                    ->setLeft(0.25)
                    ->setRight(0.25);

                // Define print area to actual used range
                $lastRow = $worksheet->getHighestRow();
                $worksheet->getPageSetup()->setPrintArea("A1:{$lastColumn}{$lastRow}");

                // Ensure all content is visible in Excel/PDF
                // 1) Wrap text for all data cells (headings + body)
                $sheet->getStyle("A{$headingsRow}:{$lastColumn}{$lastRow}")
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical('top')
                    ->setHorizontal($isRtl ? 'right' : 'left')
                    ->setIndent(1); // slight padding-like spacing inside cells (keeps wide tables fitting)

                // 2) Auto row height for wrapped text
                // Increase default row height so rows don't look "stuck" together in PDF.
                // Rows that need more height will still expand when set to auto-height (-1).
                $worksheet->getDefaultRowDimension()->setRowHeight(22);

                // 3) Apply per-field column widths (so PDF wraps correctly) or autosize
                $lastColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastColumn);
                // Map normalized heading key => column width (characters)
                // Keys use snake_case of headings/columns (e.g., Target Selection => target_selection)
                $columnWidthMap = [
                    // Long text
                    'target_selection' => 50,
                    'description'      => 50,
                    'remarks'          => 50,
                    'notes'            => 50,
                    'bio'              => 60, // Wide column for bio to prevent truncation
                    'title'            => 30,
                    'redirect_url'     => 20,
                    'target_categories' => 30,
                    // TV / Streaming
                    'server_url'       => 30,
                    'server_url1'      => 30,
                    'stream_type'      => 15,
                    'embedded'         => 15,

                    // Common identifiers
                    'name'             => 30,
                    'user_details'     => 25,
                    'coupon_discount'  => 10,
                    'tax_amount'       => 10,
                    'total_amount'     => 10,
                    'duration'         => 10,
                    'payment_method'   => 10,
                    'access'           => 12,
                    'release_date'     => 12,
                    'plan_id'          => 25,
                    'entertainment_id' => 35,
                    'category_id'      => 25,
                    'place_of_birth'   => 20,
                    'dob'              => 15,
                    'type_name'        => 10,
                    'gender'           => 10,
                    'banner_for'       => 5,
                    'placement'        => 10,
                    'type'             => 10,
                    'target_type'      => 10,
                    'content_type'     => 10,
                    'content_rating'       => 20,
                    'imdb_rating'        => 10,
                    'like_count'        => 7,
                    'watch_count'        => 7,
                    'is_restricted'        => 10,
                    // Dates and status
                    'start_date'       => 10,
                    'end_date'         => 10,
                    'status'           => 12,
                ];
                // If the export explicitly defines column widths (e.g. Cast/Crew), respect those
                // and avoid overriding them with generic width mapping.
                $export = $this;
                if ($export instanceof WithColumnWidths) {
                    /** @var array<string,int|float> $explicitWidths */
                    $explicitWidths = $export->columnWidths();
                    foreach ($explicitWidths as $columnLetter => $width) {
                        $worksheet->getColumnDimension($columnLetter)
                            ->setAutoSize(false)
                            ->setWidth($width);
                    }
                } else {
                    // For small tables (2-3 columns), Excel/PDF won't scale up automatically.
                    // We slightly oversize widths and later scale to fit the chosen paper size.
                    $scaleUpFactor = $columnCount <= 3 ? 1.5 : 1.0;
                    $defaultWidth = $columnCount <= 3 ? 32 : 22;

                    $headingMap = array_map(static function ($h) {
                        return strtolower(str_replace(' ', '_', $h));
                    }, $this->columns ?: []);
                    for ($col = 1; $col <= $lastColumnIndex; $col++) {
                        $idx = $col - 1;
                        $headingKey = $headingMap[$idx] ?? '';
                        $baseWidth = ($headingKey && array_key_exists($headingKey, $columnWidthMap))
                            ? (float) $columnWidthMap[$headingKey]
                            : (float) $defaultWidth;
                        $finalWidth = max(8, min(120, $baseWidth * $scaleUpFactor));

                        $worksheet->getColumnDimensionByColumn($col)
                            ->setAutoSize(false)
                            ->setWidth($finalWidth);
                    }
                }

                // --- Auto paper size + fit (reliable for DomPDF) ---
                // DomPDF can ignore "fit-to-width", so we:
                // 1) pick paper/orientation, 2) expand column widths to fill the page when table is narrow,
                // 3) only scale DOWN when the table is still too wide.
                $sumWidths = 0.0;
                for ($col = 1; $col <= $lastColumnIndex; $col++) {
                    $w = $worksheet->getColumnDimensionByColumn($col)->getWidth();
                    if (is_numeric($w)) {
                        $sumWidths += (float) $w;
                    }
                }

                // Heuristic "character width budgets"
                $budgetA4Portrait = 95.0;
                $budgetA4Landscape = 140.0;

                // Choose orientation: landscape if many columns OR total width exceeds A4 portrait budget
                $orientation = ($columnCount > 3 || $sumWidths > $budgetA4Portrait)
                    ? PageSetup::ORIENTATION_LANDSCAPE
                    : PageSetup::ORIENTATION_PORTRAIT;

                // Always use A4 (fixed page size requirement)
                $paperSize = PageSetup::PAPERSIZE_A4;
                $allowed = $orientation === PageSetup::ORIENTATION_LANDSCAPE
                    ? $budgetA4Landscape
                    : $budgetA4Portrait;

                // If the table is narrow, prefer widening columns (keeps font size consistent)
                // instead of scaling up the whole page (which makes fonts huge).
                // Do not gate on column count: Short Drama (and similar) exports often use 5
                // visible columns; the old `<= 4` check skipped fill and left empty right margin in PDF.
                if ($sumWidths > 0 && $sumWidths < ($allowed * 0.95)) {
                    $fillFactor = $allowed / $sumWidths;
                    // Cap widening so we don't create absurdly wide single columns
                    $fillFactor = max(1.0, min(3.0, $fillFactor));

                    for ($col = 1; $col <= $lastColumnIndex; $col++) {
                        $current = $worksheet->getColumnDimensionByColumn($col)->getWidth();
                        if (is_numeric($current)) {
                            $worksheet->getColumnDimensionByColumn($col)->setWidth(
                                max(8, min(120, (float) $current * $fillFactor))
                            );
                        }
                    }

                    // Recalculate after filling
                    $sumWidths = 0.0;
                    for ($col = 1; $col <= $lastColumnIndex; $col++) {
                        $w = $worksheet->getColumnDimensionByColumn($col)->getWidth();
                        if (is_numeric($w)) {
                            $sumWidths += (float) $w;
                        }
                    }
                }

                // Scale DOWN only when needed to fit
                $scale = 100;
                if ($sumWidths > 0 && $sumWidths > $allowed) {
                    $ratio = $allowed / $sumWidths;
                    $scale = (int) floor($ratio * 100);
                    $scale = max(55, min(100, $scale));
                }

                $pageSetup->setPaperSize($paperSize)
                    ->setOrientation($orientation)
                    ->setHorizontalCentered(true)
                    ->setVerticalCentered(false)
                    ->setFitToWidth(false)
                    ->setFitToHeight(false)
                    ->setScale($scale);

                // 4) Row heights: keep a minimum height to create "padding/breathing space",
                // but allow auto-height for rows that contain long/wrapped content (to avoid clipping).
                $minHeaderHeight = 24;
                $minRowHeight = 26;
                $longCellThreshold = 35; // characters; if longer, let row auto-expand

                for ($row = $headingsRow; $row <= $lastRow; $row++) {
                    if ($row === $headingsRow) {
                        $worksheet->getRowDimension($row)->setRowHeight($minHeaderHeight);
                        continue;
                    }

                    $shouldAuto = false;
                    for ($col = 1; $col <= $lastColumnIndex; $col++) {
                        $val = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                        if ($val instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                            $val = $val->getPlainText();
                        }
                        $text = is_scalar($val) ? (string) $val : '';
                        $text = trim($text);

                        if ($text !== '' && (strpos($text, "\n") !== false || mb_strlen($text) > $longCellThreshold)) {
                            $shouldAuto = true;
                            break;
                        }
                    }

                    if ($shouldAuto) {
                        $worksheet->getRowDimension($row)->setRowHeight(-1);
                        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                            ->getAlignment()
                            ->setVertical('top');
                    } else {
                        $worksheet->getRowDimension($row)->setRowHeight($minRowHeight);
                        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                            ->getAlignment()
                            ->setVertical('center');
                    }
                }
            },
        ];
    }

    protected function getLastColumn(): string
    {
        $lastColumnIndex = count($this->columns) - 1;

        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColumnIndex + 1);
    }
}
