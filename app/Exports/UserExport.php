<?php

namespace App\Exports;

use App\Models\User;
use Modules\Subscriptions\Models\Subscription;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class UserExport extends BaseExport implements WithColumnWidths
{
    public function __construct($columns, $dateRange = [], $type = 'user')
    {
        parent::__construct($columns, $dateRange, $type, __('users.title'));
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        foreach ($this->columns as $column) {
            switch ($column) {
                case 'first_name':
                    $modifiedHeadings[] = __('users.lbl_first_name');
                    break;
                case 'last_name':
                    $modifiedHeadings[] = __('users.lbl_last_name');
                    break;
                case 'email':
                    $modifiedHeadings[] = __('users.lbl_email');
                    break;
                case 'mobile':
                    $modifiedHeadings[] = __('users.lbl_contact_number');
                    break;
                case 'gender':
                    $modifiedHeadings[] = __('users.lbl_gender');
                    break;
                case 'expire_date':
                    $modifiedHeadings[] = __('messages.end_date');
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

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = User::role('user');

        // Filter for soon-to-expire users if type is 'soon-to-expire' or if expire_date column is present
        $isSoonToExpire = ($this->type == 'soon-to-expire' || in_array('expire_date', $this->columns));

        if ($isSoonToExpire) {
            $currentDate = Carbon::now();
            $expiryThreshold = $currentDate->copy()->addDays(7);
            $subscriptions = Subscription::where('status', 'active')
                ->whereDate('end_date', '<=', $expiryThreshold)
                ->get();
            $userIds = $subscriptions->pluck('user_id');
            $query = $query->whereIn('id', $userIds);
        }

        $query = $query->with('subscriptionPackage')->orderBy('updated_at', 'desc');

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

                    case 'gender':
                        $selectedData[$column] = ucfirst($row[$column]);
                        break;

                    case 'expire_date':
                        if ($row->subscriptionPackage && $row->subscriptionPackage->end_date) {
                            $selectedData[$column] = formatDateTimeWithTimezone($row->subscriptionPackage->end_date);
                        } else {
                            $selectedData[$column] = '-';
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
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1);

            switch ($column) {
                case 'first_name':
                case 'last_name':
                    $columnWidths[$columnLetter] = 30;
                    break;
                case 'email':
                    $columnWidths[$columnLetter] = 35;
                    break;
                case 'mobile':
                    $columnWidths[$columnLetter] = 25;
                    break;
                case 'gender':
                    $columnWidths[$columnLetter] = 15;
                    break;
                case 'expire_date':
                    $columnWidths[$columnLetter] = 20;
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
