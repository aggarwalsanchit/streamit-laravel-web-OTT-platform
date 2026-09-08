<?php

namespace App\Exports;

use Modules\Ad\Models\CustomAdsSetting;
use Modules\Entertainment\Models\Entertainment;
use Modules\Video\Models\Video;
use Modules\Episode\Models\Episode;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class CustomAdsExport extends BaseExport implements WithColumnWidths
{
    public function __construct($columns, $dateRange = [], $type = 'custom_ads')
    {
        parent::__construct($columns, $dateRange, $type, 'Custom Ads Report');
    }

    public function headings(): array
    {
        $modifiedHeadings = [];
        foreach ($this->columns as $column) {
            $modifiedHeadings[] = ucwords(str_replace('_', ' ', $column));
        }
        return $modifiedHeadings;
    }

    public function collection()
    {
        $query = CustomAdsSetting::query();
        $query = $query->orderBy('updated_at', 'desc');
        $query = $query->get();

        $newQuery = $query->map(function ($row) {
            $selectedData = [];
            foreach ($this->columns as $column) {
                switch ($column) {
                    case 'status':
                        $selectedData[$column] = $row[$column] ? __('messages.active') : __('messages.inactive');
                        break;
                    // case 'skip_enabled':
                    //     $selectedData[$column] = $row[$column] ? 'Yes' : 'No';
                    //     break;
                    case 'target_categories':
                        $ids = $row[$column] ? json_decode($row[$column], true) : [];
                        $names = [];
                        if (!empty($ids)) {
                            switch ($row['target_content_type']) {
                                case 'movie':
                                    $names = Entertainment::whereIn('id', $ids)->pluck('name')->toArray();
                                    break;
                                case 'video':
                                    $names = Video::whereIn('id', $ids)->pluck('name')->toArray();
                                    break;
                                case 'tvshow':
                                    // For each episode, append the parent TV show name in brackets
                                    $episodes = Episode::whereIn('id', $ids)->get(['id', 'name', 'entertainment_id']);
                                    $entertainmentNames = Entertainment::whereIn('id', $episodes->pluck('entertainment_id')->unique()->toArray())->pluck('name', 'id');
                                    foreach ($episodes as $episode) {
                                        $tvShowName = $entertainmentNames[$episode->entertainment_id] ?? '';
                                        $names[] = $episode->name . ($tvShowName ? ' (' . $tvShowName . ')' : '');
                                    }
                                    break;
                            }
                        }
                        $selectedData[$column] = implode(', ', $names);
                        break;
                    case 'start_date':
                    case 'end_date':
                        $selectedData[$column] = $row[$column] ? formatDate($row[$column]) : '';
                        break;
                    default:
                        if (in_array($column, ['type', 'placement', 'target_content_type'])) {
                            $selectedData[$column] = ucwords(str_replace('_', ' ', $row[$column]));
                        } else {
                            $selectedData[$column] = $row[$column];
                        }
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
                    $columnWidths[$columnLetter] = 19;
                    break;
                case 'type':
                    $columnWidths[$columnLetter] = 10;
                    break;
                case 'placement':
                    $columnWidths[$columnLetter] = 14;
                    break;
                case 'redirect_url':
                    $columnWidths[$columnLetter] = 15;
                    break;
                case 'target_content_type':
                    $columnWidths[$columnLetter] = 15;
                    break;
                case 'target_categories':
                    // Reduced width - long lists will wrap
                    $columnWidths[$columnLetter] = 35;
                    break;
                case 'start_date':
                case 'end_date':
                    $columnWidths[$columnLetter] = 14;
                    break;
                case 'status':
                    $columnWidths[$columnLetter] = 10;
                    break;
                default:
                    $columnWidths[$columnLetter] = 10;
                    break;
            }

            $columnIndex++;
        }

        return $columnWidths;
    }
}
