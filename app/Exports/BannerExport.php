<?php

namespace App\Exports;

use Modules\Banner\Models\Banner;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class BannerExport extends BaseExport implements WithColumnWidths
{
    public function __construct($columns, $dateRange = [], $type = 'banner')
    {
        parent::__construct($columns, $dateRange, $type, __('messages.lbl_banners'));
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        foreach ($this->columns as $column) {
            switch ($column) {
                case 'type':
                    $modifiedHeadings[] = __('messages.lbl_type');
                    break;
                case 'banner_for':
                    $modifiedHeadings[] = __('banner.lbl_banner_for');
                    break;
                case 'status':
                    $modifiedHeadings[] = __('messages.lbl_status');
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
        $query = Banner::query();

        $query = $query->orderBy('updated_at', 'desc');

        $query = $query->get();

        $newQuery = $query->map(function ($row) {
            $selectedData = [];

            foreach ($this->columns as $column) {
                switch ($column) {

                    case 'type':
                        $selectedData[$column] = ($row[$column] !== null && $row[$column] !== '') ? ucfirst($row[$column]) : '-';
                        break;

                    case 'type_name':
                        $selectedData[$column] = ($row[$column] !== null && $row[$column] !== '') ? $row[$column] : '-';
                        break;

                    case 'banner_for':
                        $selectedData[$column] = ($row[$column] !== null && $row[$column] !== '') ? ucfirst($row[$column]) : '-';
                        break;

                    case 'status':
                        $selectedData[$column] = __('messages.inactive');
                        if ($row[$column]) {
                            $selectedData[$column] = __('messages.active');
                        }
                        break;

                    default:
                        $val = $row[$column] ?? null;
                        $selectedData[$column] = ($val !== null && $val !== '') ? $val : '-';
                        break;
                }
            }

            return $selectedData;
        });

        return $newQuery;
    }

    public function columnWidths(): array
    {
        $columnWidths = [];
        $columnIndex = 0;

        foreach ($this->columns as $column) {
            $columnLetter = Coordinate::stringFromColumnIndex($columnIndex + 1);

            switch ($column) {
                case 'type':
                    $columnWidths[$columnLetter] = 15;
                    break;
                case 'type_name':
                    $columnWidths[$columnLetter] = 40;
                    break;
                case 'banner_for':
                    $columnWidths[$columnLetter] = 18;
                    break;
                case 'status':
                    $columnWidths[$columnLetter] = 12;
                    break;
                default:
                    $columnWidths[$columnLetter] = 25;
                    break;
            }

            $columnIndex++;
        }

        return $columnWidths;
    }
}
