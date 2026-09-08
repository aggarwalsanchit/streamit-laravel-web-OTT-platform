<?php

namespace App\Exports;

use Modules\CastCrew\Models\CastCrew;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class CastCrewExport extends BaseExport implements WithColumnWidths
{
    protected $casttype;

    public function __construct($columns, $dateRange, $casttype)
    {
        $this->casttype = $casttype ?? 'actor';
        $reportName = __('castcrew.lbl_' . $this->casttype);
        parent::__construct($columns, $dateRange, '', $reportName);
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        foreach ($this->columns as $column) {
            switch ($column) {
                case 'name':
                    $modifiedHeadings[] = __('castcrew.lbl_name');
                    break;
                case 'place_of_birth':
                    $modifiedHeadings[] = __('castcrew.lbl_birth_place');
                    break;
                case 'dob':
                    $modifiedHeadings[] = __('castcrew.lbl_dob');
                    break;
                case 'bio':
                    $modifiedHeadings[] = __('castcrew.lbl_bio');
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


    public function collection()
    {
        // Use stored casttype instead of $this->type
        $type = $this->casttype ?? 'actor';
        $query = CastCrew::where('type', $type)
            ->orderBy('updated_at', 'desc')
            ->get();

        $newQuery = $query->map(function ($row) {
            $selectedData = [];

            foreach ($this->columns as $column) {
                switch ($column) {
                    case 'dob':
                        $selectedData[$column] = formatDate($row[$column]);
                        break;
                    case 'status':
                        $selectedData[$column] = $row[$column] ? __('messages.active') : __('messages.inactive');
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
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1);

            switch ($column) {
                case 'name':
                    $columnWidths[$columnLetter] = 30;
                    break;
                case 'place_of_birth':
                    $columnWidths[$columnLetter] = 25;
                    break;
                case 'dob':
                    $columnWidths[$columnLetter] = 15;
                    break;
                case 'bio':
                    // Reduced to fit A4 landscape better
                    $columnWidths[$columnLetter] = 65;
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
