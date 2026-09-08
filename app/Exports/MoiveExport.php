<?php

namespace App\Exports;

use Modules\Entertainment\Models\Entertainment;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Auth;

class MoiveExport extends BaseExport
{
    public function __construct($columns, $dateRange = [], $type = 'movie')
    {
        parent::__construct($columns, $dateRange, $type, __('movie.title'));
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        foreach ($this->columns as $column) {
            switch ($column) {
                case 'name':
                    $modifiedHeadings[] = __('movie.lbl_name');
                    break;
                case 'description':
                    $modifiedHeadings[] = __('movie.lbl_description');
                    break;
                case 'status':
                    $modifiedHeadings[] = __('messages.lbl_status');
                    break;
                case 'is_restricted':
                    $modifiedHeadings[] = __('movie.lbl_age_restricted');
                    break;
                case 'movie_access':
                    $modifiedHeadings[] = __('movie.lbl_movie_access');
                    break;
                case 'language':
                    $modifiedHeadings[] = __('movie.lbl_movie_language');
                    break;
                case 'release_date':
                    $modifiedHeadings[] = __('movie.lbl_release_date');
                    break;
                case 'like_count':
                    $modifiedHeadings[] = __('movie.likes');
                    break;
                case 'watch_count':
                    $modifiedHeadings[] = __('movie.watch');
                    break;
                case 'duration':
                    $modifiedHeadings[] = __('movie.lbl_duration');
                    break;
                case 'IMDb_rating':
                case 'imdb_rating':
                    $modifiedHeadings[] = __('movie.lbl_imdb_rating');
                    break;
                case 'content_rating':
                    $modifiedHeadings[] = __('movie.lbl_content_rating');
                    break;
                default:
                    $modifiedHeadings[] = ucwords(str_replace('_', ' ', $column));
                    break;
            }
        }

        return $modifiedHeadings;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Entertainment::where('type', 'movie')
            ->withCount([
                'entertainmentLike' => function ($query) {
                    $query->where('is_like', 1);
                },
                'entertainmentView'
            ])
            ->orderBy('updated_at', 'desc')
            ->get();

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

                    case 'is_restricted':
                        $selectedData[$column] = 'No';
                        if ($row[$column]) {
                            $selectedData[$column] = 'Yes';
                        }
                        break;

                    case 'movie_access':
                        $selectedData[$column] = $row[$column] ? ucfirst($row[$column]) : '-';
                        break;

                    case 'language':
                        $selectedData[$column] = $row[$column] ? ucfirst($row[$column]) : '-';
                        break;

                    case 'release_date':
                        $selectedData[$column] = $row[$column] ? formatDate($row[$column]) : '-';
                        break;

                    case 'like_count':
                        $selectedData[$column] = $row->entertainment_like_count > 0 ? $row->entertainment_like_count : '-';
                        break;

                    case 'watch_count':
                        $selectedData[$column] = $row->entertainment_view_count > 0 ? $row->entertainment_view_count : '-';
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
     * Override registerEvents to set proper column widths for status and is_restricted
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $worksheet = $sheet->getDelegate();

                $isRtl = $this->applyDefaultSheetSettings($sheet, $worksheet);

                // Get all the parent class properties and methods we need
                $generatedBy = Auth::user()->first_name . ' ' . Auth::user()->last_name ?? 'System';
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
                // Keep left alignment but add a small left indent so header text isn't stuck to the border.
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

                // Page setup
                $pageSetup = $worksheet->getPageSetup();
                $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setOrientation(count($this->columns) > 3 ? PageSetup::ORIENTATION_LANDSCAPE : PageSetup::ORIENTATION_PORTRAIT)
                    ->setHorizontalCentered(true);

                // Use scaling instead of fit-to-width to ensure all columns are visible
                // This prevents columns from being cut off in PDF
                $columnCount = count($this->columns);
                if ($columnCount >= 10) {
                    // 10+ columns - use smaller scale to fit all including Status
                    $pageSetup->setFitToWidth(false)
                        ->setFitToHeight(false)
                        ->setScale(65); // Reduced from 70 to ensure status column fits
                } elseif ($columnCount > 8) {
                    // 9 columns - use moderate scale
                    $pageSetup->setFitToWidth(false)
                        ->setFitToHeight(false)
                        ->setScale(70); // Reduced from 75
                } elseif ($columnCount > 6) {
                    // 7-8 columns - use larger scale
                    $pageSetup->setFitToWidth(false)
                        ->setFitToHeight(false)
                        ->setScale(80); // Reduced from 85
                } else {
                    // Fewer columns - use fit-to-width
                    $pageSetup->setFitToWidth(1)
                        ->setFitToHeight(0)
                        ->setScale(100);
                }

                // Very tight margins to maximize space for all columns
                $worksheet->getPageMargins()
                    ->setTop(0.15)
                    ->setBottom(0.15)
                    ->setLeft(0.15)
                    ->setRight(0.15);

                // Print area
                $lastRow = $worksheet->getHighestRow();
                $worksheet->getPageSetup()->setPrintArea("A1:{$lastColumn}{$lastRow}");

                // Wrap text and auto-size
                $sheet->getStyle("A{$headingsRow}:{$lastColumn}{$lastRow}")
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical('top')
                    ->setHorizontal($isRtl ? 'right' : 'left')
                    ->setIndent(1);

                // Minimum row height for breathing space; rows with long text will auto-expand below.
                $worksheet->getDefaultRowDimension()->setRowHeight(22);

                // Set optimized column widths for Movie export
                // Ensure status and is_restricted have proper widths to prevent cutting
                $lastColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastColumn);
                $columnWidthMap = [
                    'name' => 23, // Reduced to make room for status column
                    'movie_access' => 14,
                    'IMDb_rating' => 11,
                    'imdb_rating' => 11,
                    'content_rating' => 17, // Reduced slightly
                    'duration' => 9,
                    'release_date' => 18, // Slightly reduced
                    'language' => 11, // Reduced slightly
                    'is_restricted' => 15, // Sufficient for "Is Restricted" heading and "Yes"/"No" values
                    'status' => 16, // Increased to 16 to ensure "Status" heading and "Active"/"Inactive" values are fully visible
                    'like_count' => 9,
                    'watch_count' => 11,
                ];

                // Map columns to their widths
                for ($col = 1; $col <= $lastColumnIndex; $col++) {
                    $idx = $col - 1;
                    $columnKey = $this->columns[$idx] ?? '';

                    if ($columnKey && array_key_exists($columnKey, $columnWidthMap)) {
                        $width = $columnWidthMap[$columnKey];
                        // Ensure status column has minimum width of 16
                        if ($columnKey === 'status' && $width < 16) {
                            $width = 16;
                        }
                        $worksheet->getColumnDimensionByColumn($col)
                            ->setAutoSize(false)
                            ->setWidth($width);
                    } else {
                        // Auto-size for columns not in the map
                        $worksheet->getColumnDimensionByColumn($col)->setAutoSize(true);
                    }
                }

                // Double-check status column width - force it if needed
                $statusColumnIndex = null;
                foreach ($this->columns as $idx => $colName) {
                    if ($colName === 'status') {
                        $statusColumnIndex = $idx + 1; // +1 because columns are 1-indexed
                        break;
                    }
                }
                if ($statusColumnIndex !== null) {
                    $worksheet->getColumnDimensionByColumn($statusColumnIndex)
                        ->setAutoSize(false)
                        ->setWidth(16); // Force status column to 16 characters
                }

                // Row heights: min height for padding, auto only for long/wrapped rows
                $minHeaderHeight = 24;
                $minRowHeight = 26;
                $longCellThreshold = 35;
                $lastColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastColumn);

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
