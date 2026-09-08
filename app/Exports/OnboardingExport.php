<?php

namespace App\Exports;

use Modules\Onboarding\Models\Onboarding;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class OnboardingExport extends BaseExport implements WithColumnWidths
{
    public function __construct($columns, $dateRange = [], $type = 'onboarding')
    {
        parent::__construct($columns, $dateRange, $type, 'Onboarding Report');
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        foreach ($this->columns as $column) {
            // Capitalize each word and replace underscores with spaces
            $modifiedHeadings[] = ucwords(str_replace('_', ' ', $column));
        }

        return $modifiedHeadings;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Onboarding::query();

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

    public function columnWidths(): array
    {
        $columnWidths = [];
        $columnIndex = 0;

        foreach ($this->columns as $column) {
            $columnLetter = Coordinate::stringFromColumnIndex($columnIndex + 1);

            switch ($column) {
                case 'title':
                    $columnWidths[$columnLetter] = 50;
                    break;
                case 'description':
                    $columnWidths[$columnLetter] = 50;
                    break;
                case 'status':
                    $columnWidths[$columnLetter] = 15;
                    break;
                default:
                    $columnWidths[$columnLetter] = 30;
                    break;
            }

            $columnIndex++;
        }

        return $columnWidths;
    }
}
