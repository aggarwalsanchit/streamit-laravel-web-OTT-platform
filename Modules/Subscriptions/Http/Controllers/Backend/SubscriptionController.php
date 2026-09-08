<?php

namespace Modules\Subscriptions\Http\Controllers\Backend;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Modules\Subscriptions\Models\Subscription;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use Currency;
use App\Traits\ModuleTrait;
use Modules\Subscriptions\Models\Plan;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Modules\Subscriptions\Services\SubscriptionService;
use Modules\Subscriptions\Http\Requests\SubscriptionRequest;
use Modules\Tax\Models\Tax;
use Modules\Frontend\Models\PayPerView;
use Barryvdh\DomPDF\Facade\Pdf;

class SubscriptionController extends Controller
{
    protected string $exportClass = '\App\Exports\SubscriptionExport';
    protected $subscriptionService;
    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
        }
    public function __construct(SubscriptionService $subscriptionService,Request $request)
    {
        $routeName = $request->route()->getName();

        if ($routeName == 'backend.pay-per-view-export' || $routeName == 'backend.pay-per-view-history') {
            $this->exportClass = '\App\Exports\PayPerViewExport';
            $this->module_name = 'renthistory';
        }else{
            $this->exportClass = '\App\Exports\SubscriptionExport';
            $this->module_name = 'subscriptions';
        }
        $this->subscriptionService = $subscriptionService;
        // Page Title
        $this->module_title = 'messages.lbl_subscriptions';

        // module icon
        $this->module_icon = 'fa-solid fa-clipboard-list';

        view()->share([
            'module_title' => $this->module_title,
            'module_icon' => $this->module_icon,
            'module_name' => $this->module_name,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Renderable
     */
    public function index(Request $request)
    {
        $module_action = 'User List';
        $export_import = true;


        $export_columns = [

            [
                'value' => 'user_details',
                'text' => __('messages.user'),
            ],
            [
                'value' => 'name',
                'text' => __('messages.plan'),
            ],
            [
                'value' => 'duration',
                'text' => __('dashboard.duration'),
            ],
             [
                'value' => 'payment_method',
                'text' => __('messages.payment_method'),
            ],
            [
                'value' => 'start_date',
                'text' => __('messages.start_date'),
            ],
            [
                'value' => 'end_date',
                'text' => __('messages.end_date'),
            ],
            [
                'value' => 'amount',
                'text' => __('dashboard.amount'),
            ],
            [
                'value' => 'discount',
                'text' => __('frontend.discount'),
            ],
            [
                'value' => 'coupon_discount',
                'text' => __('messages.coupon_discount'),
            ],
            [
                'value' => 'tax_amount',
                'text' => __('tax.title') . ' ' . __('dashboard.amount'),
            ],
            [
                'value' => 'total_amount',
                'text' => __('messages.total_amount'),
            ],
            [
                'value' => 'status',
                'text' => __('plan.lbl_status'),
            ],
        ];
        $export_url = route('backend.subscriptions.export');
        $filter = [
            'status' => $request->status,
        ];
        $plans= Plan::all();

        $defaultFormat = Setting::where('name', 'default_date_format')->where('datatype', 'misc')->value('val')  ?? 'Y-m-d';

        return view('subscriptions::backend.subscriptions.index', compact('module_action','export_import', 'export_columns', 'export_url','filter','plans', 'defaultFormat'));
    }


     public function pay_per_view_data(Request $request)
    {
        $module_action = 'Pay Per View List';
        $module_title = __('messages.lbl_rent_history');
        $module_name = 'pay-per-view';
        $export_import = true;
        $export_columns = [

            [
                'value' => 'user_details',
                'text' => __('messages.user'),
            ],
            [
                'value' => 'content',
                'text' => __('messages.lbl_content'),
            ],
            [
                'value' => 'duration',
                'text' => __('dashboard.duration'),
            ],
             [
                'value' => 'payment_method',
                'text' => __('messages.payment_method'),
            ],
            [
                'value' => 'start_date',
                'text' => __('messages.start_date'),
            ],
            [
                'value' => 'end_date',
                'text' => __('messages.end_date'),
            ],
            [
                'value' => 'amount',
                'text' => __('dashboard.amount'),
            ],
            [
                'value' => 'discount',
                'text' => __('frontend.discount'),
            ],

            [
                'value' => 'total_amount',
                'text' => __('messages.total_amount'),
            ],
        ];
        $export_url = route('backend.pay-per-view-export');
        $filter = [
            'status' => $request->status,
        ];
        $plans= Plan::all();

        $defaultFormat = Setting::where('name', 'default_date_format')->where('datatype', 'misc')->value('val')  ?? 'Y-m-d';


        
        // Set default date range for display if none provided
       

        return view('subscriptions::backend.subscriptions.pay-per-view-data', compact('module_title','module_action','export_import', 'export_columns', 'export_url','filter','plans','defaultFormat'));
    }

     public function RentData(Datatables $datatable,Request $request)
    {
        $query = PayPerView::query()->with(['user','movie','episode','video','PayperviewTransaction']);


            if ($request->filled('date_range') &&  $request->date_range !=null ) {
                $dates = explode(' to ', $request->date_range); // Ensure correct delimiter
                $startDate = Carbon::createFromFormat('Y-m-d', trim($dates[0]))->startOfDay();
                $endDate = isset($dates[1])
                ? Carbon::createFromFormat('Y-m-d', trim($dates[1]))->endOfDay()
                : $startDate->copy()->endOfDay();
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
        $datatable = $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-'.$row->id.'"  name="datatable_ids[]" value="'.$row->id.'" data-type="subscriptions" onclick="dataTableRowCheck('.$row->id.', this)">';
            })

        ->editColumn('user_id', function ($data) {
             $user = optional($data->user);
             $url = $user && $user->id ? route('backend.users.details', ['id' => $user->id, 'type' => 'rent']) : null;
             return view('components.user-detail-card', [
                'image' => setBaseUrlWithFileName($user->file_url ?? null,'image','users') ?? default_user_avatar(),
                'name' => $user->full_name ?? default_user_name(),
                'email' => $user->email ?? '-',
                'url' => $url,
             ])->render();
            })
           ->editColumn('start_date', function ($data) {

                    $start_date = Carbon::createFromFormat('Y-m-d H:i:s', $data->created_at);
                    return formatDate($start_date->format('Y-m-d'));

            })
            ->editColumn('end_date', function ($data) {
                $end_date = Carbon::createFromFormat('Y-m-d H:i:s', $data->view_expiry_date);
                return formatDate($end_date->format('Y-m-d'));
            })
            ->editColumn('amount', function ($data) {
                return Currency::format($data->content_price);
            })
            ->editColumn('coupon_discount', function ($data) {
                return ($data->discount_percentage ?? 0) . '%';
            })

            ->filterColumn('coupon_discount', function ($query, $keyword) {
                $query->whereRaw("CONCAT(discount_percentage, '%') LIKE ?", ["%{$keyword}%"]);
            })

            ->orderColumn('coupon_discount', function ($query, $orderDirection) {
                $query->orderBy('discount_percentage', $orderDirection);
            })

            ->editColumn('total_amount', function ($data) {
                return Currency::format(optional($data->PayperviewTransaction)->amount);
            })

           ->orderColumn('total_amount', function ($query, $orderDirection) {
                $query->leftJoin('payperviewstransactions', 'pay_per_views.id', '=', 'payperviewstransactions.pay_per_view_id')
                      ->orderBy('payperviewstransactions.amount', $orderDirection)
                      ->select('pay_per_views.*');
            })
            ->addColumn('name', function ($data) {

                if($data->type=='video'){

                     return optional($data->video)->name ?? '-';

                }elseif($data->type=='episode'){

                    return optional($data->episode)->name ?? '-';

                }elseif($data->type=='movie'){

                  return optional($data->movie)->name ?? '-';

                }else{

                     return '-';
                }


            })
            ->filterColumn('name', function($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where(function ($q) use ($keyword) {
                        $q->where('type', 'video')
                          ->whereHas('video', function ($q) use ($keyword) {
                              $q->where('name', 'like', "%{$keyword}%");
                          });
                    })->orWhere(function ($q) use ($keyword) {
                        $q->where('type', 'episode')
                          ->whereHas('episode', function ($q) use ($keyword) {
                              $q->where('name', 'like', "%{$keyword}%");
                          });
                    })->orWhere(function ($q) use ($keyword) {
                        $q->where('type', 'movie')
                          ->whereHas('movie', function ($q) use ($keyword) {
                              $q->where('name', 'like', "%{$keyword}%");
                          });
                    });
                });
            })

            ->orderColumn('name', function($query, $order) {
                $query->orderByRaw("
                    COALESCE(
                        (SELECT name FROM videos WHERE videos.id = pay_per_views.movie_id AND pay_per_views.type = 'video'),
                        (SELECT name FROM episodes WHERE episodes.id = pay_per_views.movie_id AND pay_per_views.type = 'episode'),
                        (SELECT name FROM entertainments WHERE entertainments.id = pay_per_views.movie_id AND pay_per_views.type = 'movie'),
                        ''
                    ) {$order}
                ");
            })
            ->filterColumn('status', function($query, $keyword) {
                if ($keyword == 'inactive') {
                    $query->where('status', 'inactive');
                } else if ($keyword == 'active') {
                    $query->where('status', 'active');
                }
            })
            ->filterColumn('user_id', function($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('user', function($q) use ($keyword) {

                        $q->where('first_name', 'like', '%' . $keyword . '%')->orWhere('last_name', 'like', '%' . $keyword . '%')->orWhere('email', 'like', '%' . $keyword . '%');

                    });
                }
            })
            ->filterColumn('start_date', function($query, $keyword) {
                $query->whereRaw("DATE_FORMAT(created_at, '%D %M %Y') like ?", ["%$keyword%"]);
            })
            ->filterColumn('end_date', function($query, $keyword) {
                $query->whereRaw("DATE_FORMAT(view_expiry_date, '%D %M %Y') like ?", ["%$keyword%"]);
            })

            ->orderColumn('start_date', function ($query, $order) {
                 $query->orderBy('pay_per_views.created_at', $order);
            })

            ->orderColumn('end_date', function ($query, $order) {
                   $query->orderBy('view_expiry_date', $order);
            })

            ->filterColumn('amount', function ($query, $keyword) {
                // Clean keyword to allow only numbers and dot (decimal)
                $cleanedKeyword = preg_replace('/[^0-9.]/', '', $keyword);


                // Check if the cleaned keyword is not empty

                // Check if the cleaned keyword is not empty
                if ($cleanedKeyword !== '') {
                    $query->where('content_price', 'like', "%{$cleanedKeyword}%");
                }
            })

            ->orderColumn('amount', function ($query, $orderDirection) {
                $query->orderBy('content_price', $orderDirection);
            })

            ->filterColumn('total_amount', function($query, $keyword) {
                $cleanedKeyword = preg_replace('/[^0-9.]/', '', $keyword);

                if ($cleanedKeyword !== '') {
                    $query->whereHas('PayperviewTransaction', function ($q) use ($cleanedKeyword) {
                        $q->whereRaw("CAST(amount AS CHAR) LIKE ?", ["%{$cleanedKeyword}%"]);
                    });
                }
            })


            ->addColumn('duration', function ($data) {
                return $data->available_for. ' Days' ?? '-' ;
            })

            ->filterColumn('duration', function($query, $keyword) {
                // Strip " Days" if users type it
                $numericKeyword = preg_replace('/[^0-9]/', '', $keyword);

                if (is_numeric($numericKeyword)) {
                    $query->where('available_for', '=', $numericKeyword);
                } else {
                    // Optional: return no match if not numeric
                    $query->whereRaw('1 = 0');
                }
            })

            ->orderColumn('duration', function($query, $order) {
                $query->orderBy('available_for', $order);
            })


            ->addColumn('payment_method', function ($data) {
                return ucfirst($data->PayperviewTransaction->payment_type ?? '-');
            })
            ->filterColumn('payment_method', function ($query, $keyword) {
                $query->whereHas('PayperviewTransaction', function ($query) use ($keyword) {
                    $query->where('payment_type', 'like', "%$keyword%");
                });
            })


            ->orderColumns(['id'], '-:column $1')
            ->addColumn('action', function ($data) {
                return '<a href="' . route('backend.pay-per-view-download-invoice', ['id' => $data->id]) . '"
                        class="btn btn-info-subtle btn-sm"
                        title="' . __('messages.download_invoice') . '">
                        <i class="ph ph-cloud-arrow-down align-middle"></i>
                    </a>';
            });

        return $datatable->rawColumns(array_merge(['action','check','coupon_discount','user_id', 'start_date', 'end_date', 'amount', 'name','duration']))
            ->toJson();
    }



    public function index_data(Datatables $datatable,Request $request)
    {
        // Debug: inspect incoming DataTables ordering & filtering


        // Base query without any default ORDER BY;
        // ordering will be fully controlled by DataTables (orderColumn/orderColumns)
        $query = Subscription::query()->with(['user','plan','subscription_transaction'])->orderBy('created_at', 'desc');

        // Handle duration search in the main query
        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $searchValueLower = strtolower(trim($searchValue));

            // Always apply duration search for any search term
            $query->where(function($q) use ($searchValue, $searchValueLower) {
                $q->whereHas('plan', function($planQuery) use ($searchValue) {
                    $planQuery->where('duration_value', 'like', "%{$searchValue}%")
                              ->orWhere('duration', 'like', "%{$searchValue}%")
                              ->orWhereRaw("CONCAT(duration_value, ' ', duration) LIKE ?", ["%{$searchValue}%"]);
                })
                // Also search in other fields
                ->orWhereHas('user', function($userQuery) use ($searchValue) {
                    $userQuery->where('first_name', 'like', "%{$searchValue}%")
                              ->orWhere('last_name', 'like', "%{$searchValue}%")
                              ->orWhere('email', 'like', "%{$searchValue}%");
                })
                ->orWhereHas('plan', function($planQuery) use ($searchValue) {
                    $planQuery->where('name', 'like', "%{$searchValue}%");
                })
                // Search by payment method
                ->orWhereHas('subscription_transaction', function($paymentQuery) use ($searchValue) {
                    $paymentQuery->whereRaw('LOWER(payment_type) LIKE ?', ['%' . strtolower($searchValue) . '%']);
                })
                // Search by status
                ->orWhere(function($statusQuery) use ($searchValueLower) {
                    if ($searchValueLower == 'inactive') {
                        $statusQuery->whereRaw('LOWER(status) = ?', ['inactive']);
                    } else if ($searchValueLower == 'active') {
                        $statusQuery->whereRaw('LOWER(status) = ?', ['active']);
                    } else if ($searchValueLower == 'cancel' || $searchValueLower == 'cancelled' || $searchValueLower == 'canceled') {
                        $statusQuery->where(function($cancelQuery) {
                            $cancelQuery->whereRaw('LOWER(status) = ?', ['cancel'])
                                        ->orWhereRaw('LOWER(status) = ?', ['cancelled']);
                        });
                    } else {
                        // Allow partial matching for status
                        $statusQuery->whereRaw('LOWER(status) LIKE ?', ['%' . $searchValueLower . '%']);
                    }
                });
            });
        }

        // Handle individual column searches
        if ($request->has('columns')) {
            foreach ($request->columns as $column) {
                if (isset($column['search']['value']) && !empty($column['search']['value'])) {
                    $columnName = $column['data'];
                    $searchValue = $column['search']['value'];

                    if ($columnName === 'duration') {
                        $query->whereHas('plan', function($q) use ($searchValue) {
                            $q->where('duration_value', 'like', "%{$searchValue}%")
                              ->orWhere('duration', 'like', "%{$searchValue}%")
                              ->orWhereRaw("CONCAT(duration_value, ' ', duration) LIKE ?", ["%{$searchValue}%"]);
                        });
                    }
                }
            }
        }

            if ($request->filled('plan_id') && $request->plan_id !=null ) {
                $query->where('plan_id', $request->plan_id);
            }
            if ($request->filled('date_range') && $request->date_range != null) {
                $dates = explode(' to ', $request->date_range);
                $startDate = Carbon::createFromFormat('Y-m-d', trim($dates[0]))->startOfDay();
                $endDate = isset($dates[1]) ? Carbon::createFromFormat('Y-m-d', trim($dates[1]))->endOfDay() : $startDate->copy()->endOfDay();
                $query->whereBetween('start_date', [$startDate, $endDate]);
            }
        $datatable = $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-'.$row->id.'"  name="datatable_ids[]" value="'.$row->id.'" data-type="subscriptions" onclick="dataTableRowCheck('.$row->id.', this)">';
            })

            ->editColumn('user_id', function ($data) {
                $user = $data->user;
                $url = $user && $user->id ? route('backend.users.details', ['id' => $user->id, 'type' => 'subscription']) : null;

                return view('components.user-detail-card', [
                    'image' => setBaseUrlWithFileName(optional($user)->file_url, 'image', 'users') ?? default_user_avatar(),
                    'name' => optional($user)->full_name ?? default_user_name(),
                    'email' => optional($user)->email ?? '-',
                    'url' => $url,
                ])->render();
            })
           ->editColumn('start_date', function ($data) {
                    $start_date = Carbon::createFromFormat('Y-m-d H:i:s', $data->start_date);
                    return formatDate($start_date->format('Y-m-d'));
            })
            ->editColumn('end_date', function ($data) {
                $end_date = Carbon::createFromFormat('Y-m-d H:i:s', $data->end_date);
                return formatDate($end_date->format('Y-m-d'));
            })
            ->editColumn('amount', function ($data) {
                return Currency::format($data->amount);
            })
            ->editColumn('coupon_discount', function ($data) {
                return Currency::format($data->coupon_discount ?? 0);
            })
            ->editColumn('tax_amount', function ($data) {
                return Currency::format($data->tax_amount);
            })
            ->editColumn('total_amount', function ($data) {
                return Currency::format($data->total_amount);
            })
            ->editColumn('discount', function ($data) {
                $percentage = $data->discount_percentage ?? 0;
                $baseAmount = $data->amount ?? 0;
                $discountValue = ($baseAmount * $percentage) / 100;
                return Currency::format($discountValue);
            })
            ->addColumn('name', function ($data) {
                return $data->name ?? optional($data->plan)->name ?? '-';
            })
            ->filterColumn('status', function($query, $keyword) {
                if (!empty($keyword)) {
                    $keywordLower = strtolower(trim($keyword));
                    if ($keywordLower == 'inactive') {
                        $query->whereRaw('LOWER(status) = ?', ['inactive']);
                    } else if ($keywordLower == 'active') {
                        $query->whereRaw('LOWER(status) = ?', ['active']);
                    } else if ($keywordLower == 'cancel' || $keywordLower == 'cancelled' || $keywordLower == 'canceled') {
                        $query->where(function($q) {
                            $q->whereRaw('LOWER(status) = ?', ['cancel'])
                              ->orWhereRaw('LOWER(status) = ?', ['cancelled']);
                        });
                    } else {
                        // Allow partial matching for status
                        $query->whereRaw('LOWER(status) LIKE ?', ['%' . $keywordLower . '%']);
                    }
                }
            })
            ->filterColumn('user_id', function($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('user', function($q) use ($keyword) {
                        $q->where(function($subQuery) use ($keyword) {
                            $subQuery->where('first_name', 'like', '%' . $keyword . '%')
                                     ->orWhere('last_name', 'like', '%' . $keyword . '%')
                                     ->orWhere('email', 'like', '%' . $keyword . '%')
                                     ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $keyword . '%']);
                        });
                    });
                }
            })
            ->filterColumn('start_date', function($query, $keyword) {
                $query->whereRaw("DATE_FORMAT(start_date, '%D %M %Y') like ?", ["%$keyword%"]);
            })
            ->filterColumn('end_date', function($query, $keyword) {
                $query->whereRaw("DATE_FORMAT(end_date, '%D %M %Y') like ?", ["%$keyword%"]);
            })
            ->filterColumn('amount', function($query, $keyword) {
                // Remove any non-numeric characters except for the decimal point
                $cleanedKeyword = preg_replace('/[^0-9.]/', '', $keyword);

                // Check if the cleaned keyword is not empty
                if ($cleanedKeyword !== '') {
                    // Filter the query by removing non-numeric characters from the amount column
                    $query->whereRaw("CAST(REGEXP_REPLACE(amount, '[^0-9.]', '') AS DECIMAL(10, 2)) LIKE ?", ["%{$cleanedKeyword}%"]);
                }
            })
            ->filterColumn('total_amount', function($query, $keyword) {

                $cleanedKeyword = preg_replace('/[^0-9.]/', '', $keyword);

                if ($cleanedKeyword !== '') {
                    $query->whereRaw("CAST(REGEXP_REPLACE(total_amount, '[^0-9.]', '') AS DECIMAL(10, 2)) LIKE ?", ["%{$cleanedKeyword}%"]);
                }
            })

            // Filter by computed discount amount (amount * discount_percentage / 100)
            ->filterColumn('discount', function($query, $keyword) {
                $cleanedKeyword = preg_replace('/[^0-9.]/', '', $keyword);
                if ($cleanedKeyword !== '') {
                    $query->whereRaw("(
                        (CAST(REGEXP_REPLACE(amount, '[^0-9.]', '') AS DECIMAL(10,2)) * COALESCE(discount_percentage, 0)) / 100
                    ) LIKE ?", ["%{$cleanedKeyword}%"]);
                }
            })

            ->filterColumn('name', function($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('plan', function($q) use ($keyword) {
                        $q->where('name', 'like', '%' . $keyword . '%');
                    });
                }
            })
            ->filterColumn('duration', function($query, $keyword) {
                if (!empty($keyword)) {
                    // Extract numeric value from keyword (e.g., "30 days" -> "30")
                    $numericKeyword = preg_replace('/[^0-9]/', '', $keyword);
                    $textKeyword = trim(preg_replace('/[0-9]/', '', $keyword));

                    $query->whereHas('plan', function($q) use ($keyword, $numericKeyword, $textKeyword) {
                        $q->where(function($subQuery) use ($keyword, $numericKeyword, $textKeyword) {
                            if (!empty($numericKeyword)) {
                                $subQuery->where('duration_value', '=', $numericKeyword)
                                         ->orWhere('duration_value', 'like', "%{$numericKeyword}%");
                            }
                            if (!empty($textKeyword)) {
                                $subQuery->orWhere('duration', 'like', "%{$textKeyword}%");
                            }
                            $subQuery->orWhereRaw("CONCAT(duration_value, ' ', duration) LIKE ?", ["%{$keyword}%"]);
                        });
                    });
                }
            })
             ->orderColumn('name', function ($query, $order) {
                $query->select('subscriptions.*')
                ->leftJoin('plan', 'plan.id', '=', 'subscriptions.plan_id')
                ->groupBy('subscriptions.id')  // Add grouping by primary key
                ->orderBy('plan.name', $order);
            })
            ->orderColumn('duration', function ($query, $order) {
                $query->select('subscriptions.*')
                      ->leftJoin('plan', 'plan.id', '=', 'subscriptions.plan_id')
                      ->groupBy('subscriptions.id')
                      ->orderByRaw("CONCAT(plan.duration_value, ' ', plan.duration) " . validate_sort_direction($order));
            })
            ->addColumn('duration', function ($data) {
                    $value =  $data->duration ?? $data->plan->duration_value ;
                    $unit =   $data->type ?? $data->plan->duration;
                    $unit = \Illuminate\Support\Str::plural($unit, $value);
                    return $value . ' ' . ucfirst($unit);
                return '-';
            })
            ->orderColumn('user_id', function ($query, $order) {
                $query->select('subscriptions.*')
                ->leftJoin('users', 'users.id', '=', 'subscriptions.user_id')
                ->orderByRaw("CONCAT(users.first_name, ' ', users.last_name) " . validate_sort_direction($order));
            })
            ->addColumn('payment_method', function ($data) {
                return  ucfirst($data->subscription_transaction->payment_type ?? '-');
            })
            ->filterColumn('payment_method', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('subscription_transaction', function ($q) use ($keyword) {
                        $q->whereRaw('LOWER(payment_type) LIKE ?', ['%' . strtolower($keyword) . '%']);
                    });
                }
            })
            ->orderColumn('payment_method', function ($query, $order) {
                $query->select('subscriptions.*')
                      ->leftJoin('subscriptions_transactions', 'subscriptions_transactions.subscriptions_id', '=', 'subscriptions.id')
                      ->groupBy('subscriptions.id')
                      ->orderBy('subscriptions_transactions.payment_type', $order);
            })
            // Order by computed discount amount (amount * discount_percentage / 100)
             ->orderColumn('discount', function ($query, $order) {
                 $query->select('subscriptions.*')
                       ->orderByRaw("(
                         (CAST(REGEXP_REPLACE(amount, '[^0-9.]', '') AS DECIMAL(10,2)) * COALESCE(discount_percentage, 0)) / 100
                       ) " . validate_sort_direction($order));
             })
             ->addColumn('action', function ($data) {
                 return view('subscriptions::backend.subscriptions.action', compact('data'));
             });

         // Execute the DataTable and log the IDs in the returned order for debugging
         $result = $datatable
             ->rawColumns(array_merge(['action','check','coupon_discount','user_id', 'start_date', 'end_date', 'amount', 'name','duration','discount']))
             ->toArray();



         return response()->json($result);
    }
    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $moduleName = 'subscription';
        $messageKey = __('subscription.Post_status');

        return $this->performBulkAction(subscription::class, $ids, $actionType, $moduleName);
    }

    public function create()
    {
        $module_title = __('messages.new_subscription');
        $plans = Plan::where('status',1)->get();
        $users = User::role('user')->where('status',1)->get();
        $fixedTax = Tax::active()->where('type', 'fixed')->sum('value');
        $percentageTax = Tax::active()->where('type', 'percentage')->sum('value');
        return view('subscriptions::backend.subscriptions.create', compact('module_title','plans','users','fixedTax','percentageTax'));

    }

    public function store(SubscriptionRequest $request)
{
    try {
        $this->subscriptionService->createPayment($request->all());

        return redirect()->route('backend.subscriptions.index')
                         ->with('success', __('messages.create_form_subscription', ['type' => __('messages.payment')]));
    } catch (\Exception $e) {
        return redirect()->back()
                         ->withInput()
                         ->withErrors(['coupon_discount' => $e->getMessage()]);
    }
}

    public function edit(int $id)
    {
        $module_action = __('messages.edit_payment');
        $data = $this->subscriptionService->getPaymentById($id);

        return view('subscriptions::backend.subscriptions.edit', array_merge(['module_action' => $module_action], $data));
    }
    public function update(SubscriptionRequest $request,$id)
    {
        $requestData = $request->all();
        $requestData['id'] = $id;

        $this->subscriptionService->updatePayment($requestData);
        return redirect()->route('backend.subscriptions.index')->with('success', __('messages.update_form_subscription'));

    }
    public function destroy(int $id)    
    {
        $this->subscriptionService->deletePayment($id);
        $message = __('messages.delete_form_subscription', ['form' => 'Payment']);
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function restore(int $id)    
    {
        $this->subscriptionService->restorePayment($id);
        $message = __('messages.restore_form_subscription', ['form' => 'Payment']);
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function forceDelete(int $id)    
    {
        $this->subscriptionService->forceDeletePayment($id);
        $message = __('messages.permanent_delete_form_subscription', ['form' => 'Payment']);
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function downloadInvoice(int $id)    
    {
        $data = Subscription::with('plan','subscription_transaction','user')->find($id);
            if (!$data) {
                return response()->json(['status' => false, 'message' => 'subscription not found'], 404);
            }

        $pdf = PDF::loadView('frontend::components.partials.invoice', compact('data'))
        ->setOptions([
            'defaultFont' => 'dejavu sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ]);
        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf->output();
            },
            "invoice.pdf",
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="invoice.pdf"',
            ]
        );
    }

    public function downloadPayPerViewInvoice(int $id)    
    {
        $ppv = PayPerView::with(['movie', 'episode', 'video', 'user', 'PayperviewTransaction'])
            ->findOrFail($id);

    
        $pdf = PDF::loadView('frontend::components.partials.pay-per-view', ['ppv' => $ppv])
            ->setOptions([
                'defaultFont' => 'dejavu sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
      
        $fileName = 'ppv-invoice-' . $ppv->id . '.pdf';
        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf->output();
            },
            $fileName,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]
        );
    }
}
