<?php

namespace App\Exports;

use Modules\Coupon\Models\Coupon;
use Carbon\Carbon;
use Modules\Currency\Models\Currency;
use App\Currency\CurrencyChange;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Auth;

class CouponExport extends BaseExport
{
    protected $currencyFormatter;

    public function __construct($columns, $dateRange = [], $type = 'coupon')
    {
        parent::__construct($columns, $dateRange, $type, __('messages.lbl_coupons'));
        $this->currencyFormatter = new CurrencyChange();
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        foreach ($this->columns as $column) {
            switch ($column) {
                case 'code':
                    $modifiedHeadings[] = __('messages.coupon_code');
                    break;
                case 'discount_type':
                    $modifiedHeadings[] = __('messages.discount_type');
                    break;
                case 'discount':
                    $modifiedHeadings[] = __('messages.discount');
                    break;
                case 'subscription_type':
                    $modifiedHeadings[] = __('messages.subscription_type');
                    break;
                case 'start_date':
                    $modifiedHeadings[] = __('messages.start_date_coupon');
                    break;
                case 'expire_date':
                    $modifiedHeadings[] = __('messages.expire_date');
                    break;
                case 'status':
                    $modifiedHeadings[] = __('messages.lbl_status');
                    break;
                case 'is_expired':
                    $modifiedHeadings[] = __('messages.is_expired');
                    break;
                default:
                    $modifiedHeadings[] = ucwords(str_replace('_', ' ', $column));
                    break;
            }
        }

        return $modifiedHeadings;
    }

    public function collection()
    {
        $query = Coupon::with('subscriptionPlans')->orderBy('updated_at', 'desc')->get();

        return $query->map(function ($row) {
            $selectedData = [];

            foreach ($this->columns as $column) {
                switch ($column) {
                    case 'status':
                        $selectedData[$column] = $row[$column] ? __('messages.active') : __('messages.inactive');
                        break;

                    case 'start_date':
                    case 'expire_date':
                    case 'created_at':
                    case 'updated_at':
                        $selectedData[$column] = $row[$column] ? formatDate($row[$column]) : '';
                        break;

                    case 'discount':
                        $selectedData[$column] = $row->discount_type === 'percentage'
                            ? $row[$column] . '%'
                            : $this->currencyFormatter->format($row[$column]);
                        break;

                    case 'subscription_type':
                        $selectedData[$column] = $row->subscriptionPlans->pluck('name')->join(', ');
                        break;

                    case 'is_expired':
                        $selectedData[$column] = $row[$column] ? 'Yes' : 'No';
                        break;

                    default:
                        $selectedData[$column] = $row[$column];
                        break;
                }
            }

            return $selectedData;
        });
    }

    /**
     * Override registerEvents to set proper margins and column widths for Coupon Report
     * to utilize full width of the page with proper margins
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $worksheet = $sheet->getDelegate();

                $isRtl = $this->applyDefaultSheetSettings($sheet, $worksheet);

                // Get all the parent class properties and methods we need
                $generatedBy = (Auth::user()->first_name . ' ' . Auth::user()->last_name) ?? 'System';
                $generatedAt = formatDateTimeWithTimezone(now());
                $lastColumn = $this->getLastColumn();

                // Set report info (same as parent)
                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->setCellValue('A1', $this->reportName);

                $currentRow = 2;
                if (!empty($this->type)) {
                    $sheet->mergeCells("A{$currentRow}:{$lastColumn}{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", __('messages.lbl_type') . ': ' . ucfirst($this->type));
                    $currentRow++;
                }

                $sheet->mergeCells("A{$currentRow}:{$lastColumn}{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", __('messages.lbl_generated_by') . ': ' . $generatedBy);
                $currentRow++;

                $sheet->mergeCells("A{$currentRow}:{$lastColumn}{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", __('messages.lbl_generated_on') . ': ' . $generatedAt);

                // Apply styles (same as parent)
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle("A1:A{$currentRow}")->getAlignment()
                    ->setHorizontal($isRtl ? 'right' : 'left')
                    ->setIndent(1);
                $headingsRow = !empty($this->type) ? 5 : 4;
                $sheet->getStyle("A{$headingsRow}:{$lastColumn}{$headingsRow}")->getFont()->setBold(true);

                // Remove borders from report info section (same as parent)
                $sheet->getStyle("A1:{$lastColumn}{$currentRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_NONE,
                        ],
                    ],
                ]);

                // Page setup - use landscape for multiple columns to utilize full width
                $columnCount = count($this->columns);
                $pageSetup = $worksheet->getPageSetup();

                // Force landscape for 7 or more columns to prevent cutting
                $orientation = $columnCount >= 7 ? PageSetup::ORIENTATION_LANDSCAPE : PageSetup::ORIENTATION_PORTRAIT;

                $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setOrientation($orientation)
                    ->setHorizontalCentered(true);

                // Tight margins to utilize page area (same as BaseExport)
                $worksheet->getPageMargins()
                    ->setTop(0.25)
                    ->setBottom(0.25)
                    ->setLeft(0.25)
                    ->setRight(0.25);

                // Print area
                $lastRow = $worksheet->getHighestRow();
                $worksheet->getPageSetup()->setPrintArea("A1:{$lastColumn}{$lastRow}");

                // Wrap text for all data cells with indent for padding
                $sheet->getStyle("A{$headingsRow}:{$lastColumn}{$lastRow}")
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical('top')
                    ->setHorizontal($isRtl ? 'right' : 'left')
                    ->setIndent(1);

                // Set optimized column widths for Coupon export - reduced to fit all columns within A4
                $lastColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastColumn);
                $columnWidthMap = [
                    'code' => 10,
                    'description' => 25, // Reduced - will wrap for long descriptions
                    'start_date' => 12,
                    'expire_date' => 12,
                    'discount' => 10,
                    'subscription_type' => 16, // Reduced - will wrap for multiple plans
                    'status' => 10,
                    'created_at' => 12,
                    'updated_at' => 12,
                ];

                // Map columns to their widths
                for ($col = 1; $col <= $lastColumnIndex; $col++) {
                    $idx = $col - 1;
                    $columnKey = $this->columns[$idx] ?? '';

                    if ($columnKey && array_key_exists($columnKey, $columnWidthMap)) {
                        $width = $columnWidthMap[$columnKey];
                        // Ensure status column has minimum width of 14
                        if ($columnKey === 'status' && $width < 14) {
                            $width = 14;
                        }
                        $worksheet->getColumnDimensionByColumn($col)
                            ->setAutoSize(false)
                            ->setWidth($width);
                    } else {
                        // Auto-size for columns not in the map
                        $worksheet->getColumnDimensionByColumn($col)->setAutoSize(true);
                    }
                }

                // Ensure all columns have proper widths (remove status-specific override)

                // Calculate scale to fit all columns within A4 landscape
                // Total approximate width: ~112 characters (with reduced widths)
                // A4 landscape can fit ~140 characters, so we should be fine
                // But add slight scaling for safety if needed
                $totalContentWidth = 0;
                for ($col = 1; $col <= $lastColumnIndex; $col++) {
                    $totalContentWidth += $worksheet->getColumnDimensionByColumn($col)->getWidth();
                }

                $targetWidth = 140; // Approximate character width for A4 landscape
                $scale = 100;
                if ($totalContentWidth > $targetWidth) {
                    $scale = floor(($targetWidth / $totalContentWidth) * 100);
                    $scale = max(80, min(100, $scale)); // Ensure scale is reasonable (80-100%)
                }

                $pageSetup->setFitToWidth(false)
                    ->setFitToHeight(false)
                    ->setScale($scale);

                // Row heights: keep a minimum height to create "padding/breathing space",
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
}


