<?php

namespace App\Exports;

use App\Models\User;
use Modules\Subscriptions\Models\Subscription;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Illuminate\Support\Facades\Auth;

class SoonToExpireUserExport extends BaseExport implements WithColumnWidths
{
    protected array $headingMap = [];
    protected array $statusMap = [];
    protected string $nameLabel;

    public function __construct($columns, $dateRange = [], $type = 'soon-to-expire')
    {
        parent::__construct($columns, $dateRange, $type, __('users.soon_to_expire'));

        // ✅ Resolve translations ONCE
        $this->nameLabel = __('users.lbl_name');

        $this->headingMap = [
            'mobile'                   => __('users.lbl_contact_number'),
            'expire_date'              => __('messages.end_date'),
            'subscription_start_date'  => __('messages.start_date'),
            'subscription_amount'      => __('messages.price'),
            'payment_method'           => __('messages.payment_method'),
            'email'                    => __('users.lbl_email'),
            'plan'                     => __('messages.plan'),
            'duration'                 => __('movie.lbl_duration'),
            'gender'                   => __('users.lbl_gender'),
            'status'                   => __('messages.lbl_status'),
        ];

        $this->statusMap = [
            1 => __('messages.active'),
            0 => __('messages.inactive'),
        ];
    }

    public function headings(): array
    {
        $modifiedHeadings = [];
        $hasNameColumn = false;

        foreach ($this->columns as $column) {

            if ($column === 'first_name' || $column === 'last_name') {
                if (!$hasNameColumn) {
                    $modifiedHeadings[] = $this->nameLabel;
                    $hasNameColumn = true;
                }
                continue;
            }

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
        $expiryThreshold = Carbon::now()->addDays(7);

        $subscriptions = Subscription::where('status', 'active')
            ->whereDate('end_date', '<=', $expiryThreshold)
            ->pluck('user_id');

        $users = User::role('user')
            ->whereIn('id', $subscriptions)
            ->with('subscriptionPackage.plan', 'subscriptionPackage.subscription_transaction')
            ->orderBy('updated_at', 'desc')
            ->get();

        return $users->map(function ($row) {
            $selectedData = [];
            $hasNameColumn = false;

            foreach ($this->columns as $column) {

                if ($column === 'first_name' || $column === 'last_name') {
                    if (!$hasNameColumn) {
                        $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                        $selectedData['name'] = $name ?: '-';
                        $hasNameColumn = true;
                    }
                    continue;
                }

                switch ($column) {

                    case 'status':
                        $selectedData[$column] =
                            $this->statusMap[(int) $row[$column]] ?? '-';
                        break;

                    case 'gender':
                        $selectedData[$column] =
                            $row[$column] ? ucfirst($row[$column]) : '-';
                        break;

                    case 'expire_date':
                        $selectedData[$column] =
                            optional($row->subscriptionPackage)->end_date
                                ? formatDateTimeWithTimezone(
                                    $row->subscriptionPackage->end_date,
                                    'date'
                                )
                                : '-';
                        break;

                    case 'plan':
                        $selectedData[$column] =
                            optional($row->subscriptionPackage)->name ?? '-';
                        break;

                    case 'duration':
                        if ($row->subscriptionPackage) {
                            $duration = $row->subscriptionPackage->duration;
                            $type     = $row->subscriptionPackage->type;
                            $selectedData[$column] =
                                $duration && $type
                                    ? $duration . ' ' . ucfirst($type) . ((int)$duration > 1 ? 's' : '')
                                    : '-';
                        } else {
                            $selectedData[$column] = '-';
                        }
                        break;

                    case 'subscription_start_date':
                        $selectedData[$column] =
                            optional($row->subscriptionPackage)->start_date
                                ? formatDateTimeWithTimezone(
                                    $row->subscriptionPackage->start_date,
                                    'date'
                                )
                                : '-';
                        break;

                    case 'payment_method':
                        $selectedData[$column] =
                            ucfirst(optional(
                                $row->subscriptionPackage->subscription_transaction
                            )->payment_type ?? '-');
                        break;

                    case 'subscription_amount':
                        $selectedData[$column] =
                            $row->subscriptionPackage
                                ? \Currency::format($row->subscriptionPackage->amount ?? 0)
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
        $widths = [];
        $index = 0;
        $hasNameColumn = false;

        foreach ($this->columns as $column) {

            if ($column === 'first_name' || $column === 'last_name') {
                if (!$hasNameColumn) {
                    $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                    $widths[$letter] = 20;
                    $hasNameColumn = true;
                    $index++;
                }
                continue;
            }

            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);

            $widths[$letter] = match ($column) {
                'email' => 20,
                'mobile' => 17,
                'plan' => 15,
                'duration' => 12,
                'gender' => 10,
                'expire_date' => 15,
                'payment_method' => 10,
                'subscription_start_date' => 10,
                'subscription_amount' => 10,
                'status' => 12,
                default => 15,
            };

            $index++;
        }

        return $widths;
    }

    /**
     * registerEvents unchanged (safe)
     */
}
