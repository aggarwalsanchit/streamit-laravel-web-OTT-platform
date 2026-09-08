<?php

namespace App\Exports;

use Modules\Subscriptions\Models\Subscription;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class SubscriptionExport extends BaseExport implements WithColumnWidths
{
    protected array $headingMap = [];
    protected array $statusMap = [];

    public function __construct($columns, $dateRange = [], $type = 'subscription')
    {
        parent::__construct($columns, $dateRange, $type, __('messages.lbl_subscriptions'));

        // ✅ Resolve translations ONCE
        $this->headingMap = [
            'name'            => __('messages.plan'),
            'user_details'    => __('users.user_details'),
            'duration'        => __('movie.lbl_duration'),
            'payment_method'  => __('messages.payment_method'),
            'amount'          => __('messages.price'),
            'discount'        => __('messages.discount'),
            'coupon_discount' => __('messages.coupon_discount'),
            'tax_amount'      => __('messages.tax_amount'),
            'total_amount'    => __('messages.total_amount'),
            'start_date'      => __('messages.start_date'),
            'end_date'        => __('messages.end_date'),
            'status'          => __('messages.lbl_status'),
        ];

        $this->statusMap = [
            'cancel' => __('messages.lbl_canceled'),
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
        $query = Subscription::with(['user', 'plan', 'subscription_transaction'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $query->map(function ($row) {
            $selectedData = [];

            foreach ($this->columns as $column) {
                switch ($column) {

                    case 'status':
                        $status = strtolower($row[$column] ?? '');
                        $selectedData[$column] =
                            $this->statusMap[$status]
                            ?? ucfirst($row[$column] ?? '-');
                        break;

                    case 'user_details':
                        $user = $row->user;
                        $selectedData[$column] = $user
                            ? ($user->full_name ?? '-') . ', ' . ($user->email ?? '-')
                            : '-';
                        break;

                    case 'duration':
                        if ($row->plan) {
                            $value = $row->plan->duration_value;
                            $unit  = \Illuminate\Support\Str::plural(
                                $row->plan->duration,
                                $value
                            );
                            $selectedData[$column] =
                                $value . ' ' . ucfirst($unit);
                        } else {
                            $selectedData[$column] = '-';
                        }
                        break;

                    case 'payment_method':
                        $selectedData[$column] =
                            $row->subscription_transaction
                                ? ucfirst($row->subscription_transaction->payment_type)
                                : '-';
                        break;

                    case 'amount':
                    case 'coupon_discount':
                    case 'tax_amount':
                    case 'total_amount':
                        $selectedData[$column] =
                            $row[$column]
                                ? \Currency::format($row[$column])
                                : '-';
                        break;

                    case 'discount':
                        if ($row->discount_percentage !== null) {
                            $percentage =
                                ($row->amount / 100) * $row->discount_percentage;
                            $selectedData[$column] =
                                \Currency::format($percentage);
                        } else {
                            $selectedData[$column] = '-';
                        }
                        break;

                    case 'start_date':
                    case 'end_date':
                        $selectedData[$column] =
                            $row[$column]
                                ? formatDate(
                                    Carbon::parse($row[$column])->format('Y-m-d')
                                )
                                : '-';
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
                case 'user_details':
                    // Name and email combined - needs more space
                    $columnWidths[$columnLetter] = 22;
                    break;
                case 'name':
                    // Plan name
                    $columnWidths[$columnLetter] = 16;
                    break;
                case 'duration':
                    $columnWidths[$columnLetter] = 10;
                    break;
                case 'payment_method':
                    $columnWidths[$columnLetter] = 10;
                    break;
                case 'start_date':
                case 'end_date':
                    $columnWidths[$columnLetter] = 14;
                    break;
                case 'amount':
                case 'discount':
                case 'coupon_discount':
                case 'tax_amount':
                case 'total_amount':
                    // Price columns - narrower since they contain currency
                    $columnWidths[$columnLetter] = 9;
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
