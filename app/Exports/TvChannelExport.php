<?php

namespace App\Exports;

use Modules\LiveTV\Models\LiveTvChannel;

class TvChannelExport extends BaseExport
{
    protected array $headingMap = [];
    protected array $statusMap = [];

    public function __construct($columns, $dateRange = [], $type = 'tv_channel')
    {
        parent::__construct($columns, $dateRange, $type, __('messages.lbl_tv_channels'));

        // ✅ Resolve translations ONCE
        $this->headingMap = [
            'name'         => __('movie.lbl_name'),
            'category_id'  => __('messages.lbl_tv_category'),
            'description'  => __('movie.lbl_description'),
            'access'       => __('movie.lbl_movie_access'),
            'plan_id'      => __('messages.plan'),
            'poster'       => __('movie.lbl_poster'),
            'poster_tv'    => __('movie.lbl_poster_tv'),
            'type'         => __('messages.lbl_type'),
            'stream_type'  => __('movie.lbl_stream_type'),
            'server_url'   => __('movie.server_url'),
            'server_url1'  => __('movie.server_url1'),
            'embedded'     => __('movie.embedded'),
            'status'       => __('messages.lbl_status'),
        ];

        $this->statusMap = [
            1 => __('messages.active'),
            0 => __('messages.inactive'),
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
        $query = LiveTvChannel::with('TvChannelStreamContentMappings')
            ->orderBy('updated_at', 'desc')
            ->get();

        return $query->map(function ($row) {
            $selectedData = [];

            foreach ($this->columns as $column) {
                switch ($column) {

                    case 'status':
                        $selectedData[$column] =
                            $this->statusMap[(int) $row[$column]] ?? '-';
                        break;

                    case 'stream_type':
                        $selectedData[$column] =
                            $row->TvChannelStreamContentMappings->stream_type ?? '';
                        break;

                    case 'embedded':
                        $selectedData[$column] =
                            $row->TvChannelStreamContentMappings->embedded ?? '';
                        break;

                    case 'server_url':
                        $selectedData[$column] =
                            $row->TvChannelStreamContentMappings->server_url ?? '';
                        break;

                    case 'description':
                        $selectedData[$column] =
                            strip_tags($row[$column] ?? '');
                        break;

                    default:
                        $selectedData[$column] = $row[$column];
                        break;
                }

                // Soft-wrap very long URLs for PDF/Excel
                if (
                    isset($selectedData[$column]) &&
                    is_string($selectedData[$column]) &&
                    preg_match('#^https?://#i', $selectedData[$column])
                ) {
                    $url = $selectedData[$column];
                    $urlWithBreaks = preg_replace('/([\/\.\?\&\=\-_])/u', "$1\n", $url);

                    if ($urlWithBreaks === $url) {
                        $urlWithBreaks = rtrim(chunk_split($url, 60, "\n"));
                    }

                    $selectedData[$column] = $urlWithBreaks;
                }
            }

            return $selectedData;
        });
    }
}
