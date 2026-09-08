<?php

namespace App\Exports;

use Modules\Episode\Models\Episode;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Auth;

class EpisodeExport extends BaseExport
{
    public function __construct($columns, $dateRange = [], $type = 'episode')
    {
        parent::__construct($columns, $dateRange, $type, __('movie.episode_details'));
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        foreach ($this->columns as $column) {
            switch ($column) {
                case 'entertainment_id':
                    $modifiedHeadings[] = __('messages.lbl_tvshow_name');
                    break;
                case 'season_id':
                    $modifiedHeadings[] = __('movie.lbl_season');
                    break;
                case 'status':
                    $modifiedHeadings[] = __('messages.lbl_status');
                    break;
                case 'is_restricted':
                    $modifiedHeadings[] = __('movie.lbl_age_restricted');
                    break;
                case 'access':
                    $modifiedHeadings[] = __('movie.lbl_movie_access');
                    break;
                case 'release_date':
                    $modifiedHeadings[] = __('movie.lbl_release_date');
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
        $query = Episode::with(['entertainmentdata', 'seasondata']);

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

                    case 'is_restricted':
                        $selectedData[$column] = 'No';
                        if ($row[$column]) {
                            $selectedData[$column] = 'Yes';
                        }
                        break;

                    case 'access':
                        // Capitalize first character of access value
                        $accessValue = $row[$column] ?? '';
                        $selectedData[$column] = !empty($accessValue) ? ucfirst($accessValue) : '';
                        break;

                    case 'release_date':
                        $selectedData[$column] = $row[$column] ? formatDate($row[$column]) : '';
                        break;

                    case 'entertainment_id':
                        // Show TV show name instead of ID
                        $selectedData[$column] = $row->entertainmentdata ? $row->entertainmentdata->name : ($row[$column] ?? '');
                        break;

                    case 'season_id':
                        // Show Season name instead of ID
                        $selectedData[$column] = $row->seasondata ? $row->seasondata->name : ($row[$column] ?? '');
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
     * Override registerEvents to force landscape orientation for Episode exports only
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
                // Use the same datetime formatting used by BaseExport/Genres
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
                // Keep left alignment, but add slight left padding so header isn't stuck to border
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

                // Page setup - FORCE LANDSCAPE for Episode Report only, but A4 only
                $pageSetup = $worksheet->getPageSetup();
                $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE) // Force landscape for Episode
                    ->setHorizontalCentered(true);

                // For Episode reports with many columns, use scale to fit within A4 landscape
                $columnCount = count($this->columns);
                if ($columnCount >= 10) {
                    // 10+ columns - scale down to fit in A4 landscape
                    $pageSetup->setFitToWidth(false)
                        ->setFitToHeight(false)
                        ->setScale(65);
                } elseif ($columnCount > 8) {
                    // 9 columns - use smaller scale
                    $pageSetup->setFitToWidth(false)
                        ->setFitToHeight(false)
                        ->setScale(70);
                } elseif ($columnCount > 6) {
                    // Medium columns (7-8) - use moderate scale
                    $pageSetup->setFitToWidth(false)
                        ->setFitToHeight(false)
                        ->setScale(75);
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

                // Minimum default height for breathing space (BaseExport style)
                $worksheet->getDefaultRowDimension()->setRowHeight(22);

                // Set optimized column widths for Episode export
                $lastColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastColumn);
                $columnWidthMap = [
                    'name' => 24, // Reduced from 30
                    'access' => 9, // Reduced from 12
                    'entertainment_id' => 19, // TV Show - Reduced from 25
                    'season_id' => 19, // Season - Reduced from 25
                    'IMDb_rating' => 10, // Note: capital letters - Reduced from 12
                    'content_rating' => 17, // Reduced from 20
                    'duration' => 9, // Reduced from 10
                    'release_date' => 10, // Reduced from 12
                    'is_restricted' => 9, // Reduced from 12
                    'status' => 9,
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

                // Row heights: keep minimum height for padding, auto only for long/wrapped rows
                $minHeaderHeight = 24;
                $minRowHeight = 26;
                $longCellThreshold = 35;
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
