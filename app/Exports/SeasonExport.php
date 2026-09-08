<?php

namespace App\Exports;

use Modules\Season\Models\Season;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class SeasonExport extends BaseExport implements WithColumnWidths
{
    public function __construct($columns, $dateRange = [], $type = 'season')
    {
        parent::__construct($columns, $dateRange, $type, __('movie.season_details'));
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        foreach ($this->columns as $column) {
            switch ($column) {
                case 'entertainment_id':
                    $modifiedHeadings[] = __('messages.lbl_tvshow_name');
                    break;
                case 'name':
                    $modifiedHeadings[] = __('movie.lbl_name');
                    break;
                case 'description':
                    $modifiedHeadings[] = __('movie.lbl_description');
                    break;
                case 'status':
                    $modifiedHeadings[] = __('messages.lbl_status');
                    break;
                case 'season_year':
                    $modifiedHeadings[] = __('messages.lbl_year');
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
        $query = Season::with(['entertainmentdata', 'plan']);

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
                        $selectedData[$column] = 'no';
                        if ($row[$column]) {
                            $selectedData[$column] = 'yes';
                        }
                        break;

                    case 'entertainment_id':
                        // Show TV Show name instead of ID
                        $selectedData[$column] = $row->entertainmentdata ? $row->entertainmentdata->name : ($row[$column] ?? '');
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

    public function columnWidths(): array
    {
        $columnWidths = [];
        $columnIndex = 0;

        foreach ($this->columns as $column) {
            $columnLetter = Coordinate::stringFromColumnIndex($columnIndex + 1);

            switch ($column) {
                case 'name':
                    $columnWidths[$columnLetter] = 38;
                    break;
                case 'entertainment_id':
                    // TV Show Name column needs to be wide so it fills the page and avoids big right blank space
                    $columnWidths[$columnLetter] = 45;
                    break;
                case 'status':
                    $columnWidths[$columnLetter] = 14;
                    break;
                case 'description':
                    $columnWidths[$columnLetter] = 60;
                    break;
                default:
                    $columnWidths[$columnLetter] = 22;
                    break;
            }

            $columnIndex++;
        }

        return $columnWidths;
    }
}
