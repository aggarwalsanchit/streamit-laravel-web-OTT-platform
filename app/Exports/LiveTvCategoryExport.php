<?php

namespace App\Exports;

use Modules\LiveTV\Models\LiveTvCategory;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class LiveTvCategoryExport extends BaseExport implements WithColumnWidths
{
    protected array $headingMap = [];
    protected array $statusMap = [];

    public function __construct($columns, $dateRange = [], $type = 'live_tv_category')
    {
        parent::__construct($columns, $dateRange, $type, __('messages.lbl_tv_category'));

        // ✅ Resolve translations ONCE
        $this->headingMap = [
            'name'        => __('movie.lbl_name'),
            'description' => __('movie.lbl_description'),
            'status'      => __('messages.lbl_status'),
        ];

        $this->statusMap = [
            true  => __('messages.active'),
            false => __('messages.inactive'),
        ];
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        foreach ($this->columns as $column) {
            $modifiedHeadings[] =
                $this->headingMap[$column]
                ?? ucwords(str_replace('_', ' ', $column));
        }

        return $modifiedHeadings;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return LiveTvCategory::orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($row) {
                $selectedData = [];

                foreach ($this->columns as $column) {
                    switch ($column) {

                        case 'status':
                            // ✅ No translation call here
                            $selectedData[$column] =
                                $this->statusMap[(bool) $row[$column]] ?? '-';
                            break;

                        case 'description':
                            $selectedData[$column] =
                                strip_tags($row[$column] ?? '');
                            break;

                        default:
                            $selectedData[$column] = $row[$column];
                            break;
                    }
                }

                return $selectedData;
            });
    }

    public function columnWidths(): array
    {
        $columnWidths = [];
        $columnIndex = 0;

        foreach ($this->columns as $column) {
            $columnLetter = Coordinate::stringFromColumnIndex($columnIndex + 1);

            switch ($column) {
                case 'name':
                    $columnWidths[$columnLetter] = 30;
                    break;
                case 'description':
                    // Reduced to fit A4 landscape better
                    $columnWidths[$columnLetter] = 70;
                    break;
                case 'status':
                    $columnWidths[$columnLetter] = 15;
                    break;
                default:
                    $columnWidths[$columnLetter] = 20;
                    break;
            }

            $columnIndex++;
        }

        return $columnWidths;
    }
}
