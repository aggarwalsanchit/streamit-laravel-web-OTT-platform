<?php

namespace App\Exports;

use Modules\Genres\Models\Genres;

class GenresExport extends BaseExport
{
    public function __construct($columns, $dateRange = [], $type = 'genres')
    {
        parent::__construct($columns, $dateRange, $type, __('messages.genres_reports'));
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        foreach ($this->columns as $column) {
            // Map column names to their translation keys
            switch ($column) {
                case 'name':
                    $modifiedHeadings[] = __('messages.name');
                    break;
                case 'description':
                    $modifiedHeadings[] = __('messages.description');
                    break;
                case 'status':
                    $modifiedHeadings[] = __('messages.lbl_status');
                    break;
                default:
                    // Fallback: Capitalize each word and replace underscores with spaces
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
        $query = Genres::query();

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
}
