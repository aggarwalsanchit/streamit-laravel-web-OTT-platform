<?php

namespace App\Exports;

use Modules\Subscriptions\Models\PlanLimitation;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Auth;

class PlanLimitationExport extends BaseExport
{
    public function __construct($columns, $dateRange = [], $type = 'plan_limitation')
    {
        parent::__construct($columns, $dateRange, $type, 'Plan Limitation Report');
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        foreach ($this->columns as $column) {
            $modifiedHeadings[] = ucwords(str_replace('_', ' ', $column));
        }

        return $modifiedHeadings;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = PlanLimitation::query();

        $query = $query->orderBy('updated_at', 'desc');

        $query = $query->get();

        $newQuery = $query->map(function ($row) {
            $selectedData = [];

            foreach ($this->columns as $column) {
                switch ($column) {

                    case 'status':
                        $selectedData[$column] = __('messages.inactive');
                        if ($row[$column]) {
                            $selectedData[$column] = __('messages.active');
                        }
                        break;

                    default:
                        $selectedData[$column] = $row[$column];
                        break;
                }
            }

            return $selectedData;
        });

        return $newQuery;
    }

    /**
     * Override registerEvents to set proper margins and column widths for Plan Limitation Report
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
                
                // Page setup - use portrait for 3 columns
                $pageSetup = $worksheet->getPageSetup();
                $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
                    ->setHorizontalCentered(true);
                
                // Use fit-to-width for better display
                $pageSetup->setFitToWidth(1)
                    ->setFitToHeight(0)
                    ->setScale(100);
                
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
                
                // Set optimized column widths for Plan Limitation export
                $lastColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastColumn);
                $columnWidthMap = [
                    'title' => 25,
                    'description' => 60, // Wide column for long descriptions
                    'status' => 12,
                ];
                
                // Map columns to their widths
                for ($col = 1; $col <= $lastColumnIndex; $col++) {
                    $idx = $col - 1;
                    $columnKey = $this->columns[$idx] ?? '';
                    
                    if ($columnKey && array_key_exists($columnKey, $columnWidthMap)) {
                        $worksheet->getColumnDimensionByColumn($col)
                            ->setAutoSize(false)
                            ->setWidth($columnWidthMap[$columnKey]);
                    } else {
                        // Auto-size for columns not in the map
                        $worksheet->getColumnDimensionByColumn($col)->setAutoSize(true);
                    }
                }
                
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
