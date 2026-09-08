<?php

namespace App\Exports;

use Modules\Video\Models\Video;

class VideoExport extends BaseExport
{
    protected array $headingMap = [];
    protected array $statusMap = [];

    public function __construct($columns, $dateRange = [], $type = 'video')
    {
        parent::__construct($columns, $dateRange, $type, __('messages.video'));

        // ✅ Resolve translations ONCE
        $this->headingMap = [
            'name'               => __('movie.lbl_name'),
            'description'        => __('movie.lbl_description'),
            'access'             => __('movie.lbl_movie_access'),
            'plan_id'            => __('messages.plan'),
            'duration'           => __('movie.lbl_duration'),
            'release_date'       => __('movie.lbl_release_date'),
            'poster'             => __('movie.lbl_poster'),
            'poster_tv'          => __('movie.lbl_poster_tv'),
            'video_upload_type'  => __('movie.lbl_video_upload_type'),
            'video_url'          => __('movie.video_url_input'),
            'quality'            => __('movie.lbl_video_quality'),
            'quality_type'       => __('movie.lbl_quality_video_download_type'),
            'quality_url'        => __('movie.download_quality_video_url'),
            'status'             => __('messages.lbl_status'),
            'like'               => __('movie.likes'),
            'like_count'         => __('movie.likes'),
            'watch'              => __('movie.watch'),
            'watch_count'        => __('movie.watch'),
        ];

        $this->statusMap = [
            1 => __('messages.active'),
            0 => __('messages.inactive'),
        ];
    }

    public function headings(): array
    {
        $headings = [];

        foreach ($this->columns as $column) {
            $headings[] =
                $this->headingMap[$column]
                ?? ucwords(str_replace('_', ' ', $column));
        }

        return $headings;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $videos = Video::withCount([
                'entertainmentLike' => function ($query) {
                    $query->where('is_like', 1)->where('type', 'video');
                },
                'entertainmentView',
            ])
            ->orderBy('updated_at', 'desc')
            ->get();

        return $videos->map(function ($row) {
            $selectedData = [];

            foreach ($this->columns as $column) {
                switch ($column) {

                    case 'status':
                        $selectedData[$column] =
                            $this->statusMap[(int) $row[$column]] ?? '-';
                        break;

                    case 'is_restricted':
                        $selectedData[$column] = $row[$column] ? 'Yes' : 'No';
                        break;

                    case 'access':
                        $value = $row[$column] ?? '';
                        $selectedData[$column] =
                            $value !== '' ? ucfirst($value) : '';
                        break;

                    case 'release_date':
                        $selectedData[$column] =
                            $row[$column] ? formatDate($row[$column]) : '';
                        break;

                    case 'like':
                    case 'like_count':
                        $count = $row->entertainment_like_count ?? 0;
                        $selectedData[$column] = $count > 0 ? $count : '-';
                        break;

                    case 'watch':
                    case 'watch_count':
                        $count = $row->entertainment_view_count ?? 0;
                        $selectedData[$column] = $count > 0 ? $count : '-';
                        break;

                    default:
                        $selectedData[$column] = $row[$column];
                        break;
                }
            }

            return $selectedData;
        });
    }
}
