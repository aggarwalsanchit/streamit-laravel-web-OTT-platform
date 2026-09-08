<?php

namespace App\Exports;

use Modules\Subscriptions\Models\Plan;
use Illuminate\Support\Str;

class PlanExport extends BaseExport
{
    protected array $headingMap = [];
    protected array $statusMap = [];

    public function __construct($columns, $dateRange = [], $type = 'plan')
    {
        parent::__construct($columns, $dateRange, $type, __('messages.subscription_plan'));

        // ✅ Resolve translations ONCE
        $this->headingMap = [
            'name'                => __('movie.lbl_name'),
            'duration'            => __('movie.lbl_duration'),
            'price'               => __('messages.price'),
            'status'              => __('messages.lbl_status'),
            'discount_percentage' => __('messages.discount'),
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
        $plans = Plan::orderBy('updated_at', 'desc')->get();

        return $plans->map(function ($row) {
            $selectedData = [];

            foreach ($this->columns as $column) {
                switch ($column) {

                    case 'status':
                        $selectedData[$column] =
                            $this->statusMap[(int) $row[$column]] ?? '-';
                        break;

                    case 'duration':
                        $durationUnit = Str::plural(
                            $row->duration,
                            $row->duration_value
                        );
                        $selectedData[$column] =
                            $row->duration_value . ' ' . ucfirst($durationUnit);
                        break;

                    case 'discount_percentage':
                        $selectedData[$column] =
                            ($row->discount_percentage ?? 0) > 0
                                ? number_format($row->discount_percentage, 2, '.', '') . '%'
                                : '0.00%';
                        break;

                    case 'price':
                    case 'amount':
                    case 'monthly_price':
                    case 'subscription_fee':
                    case 'total_price':
                        $selectedData[$column] =
                            $row[$column]
                                ? \Currency::format($row[$column])
                                : '-';
                        break;

                    case 'name':
                        $selectedData[$column] =
                            $row[$column] ? ucfirst($row[$column]) : '';
                        break;

                    default:
                        $value = $row[$column];
                        $selectedData[$column] =
                            is_string($value) && !empty($value)
                                ? ucfirst($value)
                                : $value;
                        break;
                }
            }

            return $selectedData;
        });
    }
}
