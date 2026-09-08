<?php

namespace Modules\User\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Traits\ModuleTrait;
use Currency;
use Modules\Subscriptions\Models\Subscription;
use Illuminate\Support\Facades\Mail;
use App\Mail\ExpiringSubscriptionEmail;
use Maatwebsite\Excel\Facades\Excel;

class SoonToExpireUsersController extends Controller
{
    protected string $exportClass = '\App\Exports\SoonToExpireUserExport';

    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct()
    {
        $this->traitInitializeModuleTrait(
            'users.soon_to_expire',
            'users',
            'fa-solid fa-clipboard-list'
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $module_action = 'List';
        $module_title = 'users.soon_to_expire';
        $this->traitInitializeModuleTrait(
            $module_title,
            'users',
            'fa-solid fa-clipboard-list'
        );

        $export_import = true;
        $export_columns = [
            [
                'value' => 'first_name',
                'text' => __('users.lbl_first_name'),
            ],
            [
                'value' => 'last_name',
                'text' => __('users.lbl_last_name'),
            ],
            [
                'value' => 'email',
                'text' => __('users.lbl_email'),
            ],
            [
                'value' => 'mobile',
                'text' => __('users.lbl_contact_number'),
            ],
            [
                'value' => 'gender',
                'text' => __('users.lbl_gender'),
            ],
            [
                'value' => 'plan',
                'text' => __('messages.plan'),
            ],
            [
                'value' => 'duration',
                'text' => __('dashboard.duration'),
            ],
            [
                'value' => 'subscription_start_date',
                'text' => __('messages.start_date'),
            ],
            [
                'value' => 'payment_method',
                'text' => __('messages.payment_method'),
            ],
            [
                'value' => 'subscription_amount',
                'text' => __('dashboard.amount'),
            ],
            [
                'value' => 'expire_date',
                'text' => __('messages.end_date'),
            ],
        ];

        $export_url = route('backend.soon-to-expire-users.export');
        $module_name = 'users';
        $type = 'soon-to-expire';

        return view('user::backend.users.soon-to-expire', compact('module_action', 'module_title', 'export_import', 'export_columns', 'export_url', 'type', 'module_name'));
    }

    public function index_data(Datatables $datatable, Request $request)
    {
        $currentDate = Carbon::now();
        $expiryThreshold = $currentDate->copy()->addDays(7);

        $subscriptions = Subscription::with('user', 'plan', 'subscription_transaction')
            ->where('status', 'active')
            ->whereDate('end_date', '<=', $expiryThreshold)
            ->get();

        $userIds = $subscriptions->pluck('user_id');
        $query = User::where('user_type', 'user')
            ->whereIn('id', $userIds)
            ->with('subscriptionPackage.plan', 'subscriptionPackage.subscription_transaction');

        $filter = $request->filter;

        if (isset($filter['name'])) {
            $fullName = $filter['name'];
            $query->where(function($query) use ($fullName) {
                $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$fullName%"]);
            });
        }

        if (isset($filter['email'])) {
            $query->where('email', $filter['email']);
        }

        return $datatable->eloquent($query)
            ->addColumn('check', function ($data) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-'.$data->id.'"  name="datatable_ids[]" value="'.$data->id.'" data-type="users" onclick="dataTableRowCheck('.$data->id.', this)">';
            })
            ->editColumn('name', function ($data) {
                return view('user::backend.users.user_details', compact('data'));
            })
            ->editColumn('mobile', function ($data) {
                if ($data->mobile != null) {
                    return $data->mobile;
                }
                return '-';
            })
            ->editColumn('gender', function ($data) {
                if ($data->gender != null) {
                    return $data->gender;
                }
                return '-';
            })
            ->addColumn('plan', function ($data) {
                return optional($data->subscriptionPackage)->name ?? '-';
            })
            ->addColumn('duration', function ($data) {
                if ($data->subscriptionPackage) {
                    $duration = $data->subscriptionPackage->duration ?? '-';
                    $type = $data->subscriptionPackage->type ?? '';
                    return $duration ? ($duration . ' ' . ucfirst($type)) : '-';
                }
                return '-';
            })
            ->addColumn('subscription_start_date', function ($data) {
                if ($data->subscriptionPackage && $data->subscriptionPackage->start_date) {
                    $start_date = Carbon::createFromFormat('Y-m-d H:i:s', $data->subscriptionPackage->start_date);
                    return formatDate($start_date->format('Y-m-d'));
                }
                return '-';
            })
            ->addColumn('payment_method', function ($data) {
                return ucfirst(optional($data->subscriptionPackage->subscription_transaction)->payment_type ?? '-');
            })
            ->addColumn('subscription_amount', function ($data) {
                return Currency::format($data->subscriptionPackage->amount ?? 0);
            })
            ->editColumn('expire_date', function ($data) {
                if (!$data->subscriptionPackage || !$data->subscriptionPackage->end_date) {
                    return '-';
                }
                $endDate = Carbon::createFromFormat('Y-m-d H:i:s', $data->subscriptionPackage->end_date);
                return formatDate($endDate->format('Y-m-d'));
            })
            ->filterColumn('expire_date', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('subscriptionPackage', function ($q) use ($keyword) {
                        try {
                            $date = Carbon::createFromFormat('jS F Y', $keyword);
                            $q->whereDate('end_date', $date->format('Y-m-d'));
                        } catch (\Exception $e) {
                            $q->whereRaw("DATE_FORMAT(end_date, '%D %M %Y') LIKE ?", ["%{$keyword}%"]);
                        }
                    });
                }
            })
            ->filterColumn('name', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->where('first_name', 'like', '%' . $keyword . '%')
                        ->orWhere('last_name', 'like', '%' . $keyword . '%')
                        ->orWhere('email', 'like', '%' . $keyword . '%');
                }
            })
            ->editColumn('status', function ($row) {
                $checked = $row->status ? 'checked="checked"' : '';
                return '
                    <div class="form-check form-switch">
                        <input type="checkbox" data-url="' . route('backend.users.update_status', $row->id) . '"
                               data-token="' . csrf_token() . '" class="switch-status-change form-check-input"
                               id="datatable-row-' . $row->id . '" name="status" value="' . $row->id . '"
                               ' . $checked . '>
                    </div>
                ';
            })
            ->rawColumns(['check', 'name', 'status'])
            ->toJson();
    }

    public function sendEmail(Request $request)
    {
        $expiryThreshold = Carbon::now()->addDays(7);
        $subscriptions = Subscription::with('user')
            ->where('status', 'active')
            ->whereDate('end_date', '<=', $expiryThreshold)
            ->get();
        $userIds = $subscriptions->pluck('user_id');

        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            if (isSmtpConfigured()) {
                Mail::to($user->email)->send(new ExpiringSubscriptionEmail($user));
            } else {
                return response()->json(['message' => 'There is an issue with mail service please check configurations.', 'status' => true], 200);
            }
        }

        $message = __('customer.email_sent');
        return response()->json(['message' => $message, 'status' => true], 200);
    }
}



 