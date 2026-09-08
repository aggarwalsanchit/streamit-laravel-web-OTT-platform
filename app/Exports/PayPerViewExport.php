<?php

namespace App\Exports;

use Modules\Frontend\Models\PayPerView;

class PayPerViewExport extends BaseExport
{
    protected array $headingMap = [];

    public function __construct($columns, $dateRange = [], $type = 'pay_per_view')
    {
        parent::__construct($columns, $dateRange, $type, __('messages.pay_per_view'));


        $this->headingMap = [
            'user_details'   => __('users.user_details'),
            'content'        => __('messages.lbl_content'),
            'duration'       => __('movie.lbl_duration'),
            'payment_method' => __('messages.payment_method'),
            'amount'         => __('messages.price'),
            'discount'       => __('messages.discount'),
            'total_amount'   => __('messages.total_amount'),
            'start_date'     => __('messages.start_date'),
            'end_date'       => __('messages.end_date'),
            'status'         => __('messages.lbl_status'),
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
        $data = PayPerView::with([
                'user',
                'video',
                'episode',
                'movie',
                'PayperviewTransaction'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return $data->map(function ($row) {
            $exportRow = [];

            foreach ($this->columns as $column) {
                switch ($column) {

                    case 'user_details':
                        $user = $row->user;
                        $exportRow[$column] = $user
                            ? ($user->full_name ?? '-') . ', ' . ($user->email ?? '-')
                            : '-';
                        break;

                    case 'content':
                        $exportRow[$column] = match ($row->type) {
                            'video'   => optional($row->video)->name,
                            'episode' => optional($row->episode)->name,
                            'movie'   => optional($row->movie)->name,
                            default   => '-'
                        };
                        break;

                    case 'duration':
                        $exportRow[$column] = $row->available_for . ' Days';
                        break;

                    case 'payment_method':
                        $exportRow[$column] =
                            ucfirst(optional($row->PayperviewTransaction)->payment_type ?? '-');
                        break;

                    case 'start_date':
                        $exportRow[$column] = $row->created_at
                            ? formatDateTimeWithTimezone($row->created_at, 'date')
                            : '-';
                        break;

                    case 'end_date':
                        $exportRow[$column] = $row->view_expiry_date
                            ? formatDateTimeWithTimezone($row->view_expiry_date, 'date')
                            : '-';
                        break;

                    case 'amount':
                        $exportRow[$column] =
                            \Currency::format($row->content_price ?? 0);
                        break;

                    case 'discount':
                        $exportRow[$column] =
                            ($row->discount_percentage ?? 0) . '%';
                        break;

                    case 'total_amount':
                        $exportRow[$column] =
                            \Currency::format(optional($row->PayperviewTransaction)->amount ?? 0);
                        break;

                    case 'status':
                        $exportRow[$column] = $row->status ?? '-';
                        break;

                    default:
                        $exportRow[$column] = $row->{$column} ?? '-';
                        break;
                }
            }

            return $exportRow;
        });
    }
}
