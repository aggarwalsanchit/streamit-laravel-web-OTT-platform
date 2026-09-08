<?php

namespace Modules\User\Http\Controllers\Backend;

use Hash;
use Currency;
use Carbon\Carbon;
use App\Models\User;
use App\Authorizable;
use App\Traits\ModuleTrait;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Mail\ExpiringSubscriptionEmail;
use Modules\Frontend\Models\PayPerView;
use Modules\User\Http\Requests\UserRequest;
use Modules\Subscriptions\Models\Subscription;
use Modules\User\Http\Requests\PasswordRequest;

class UsersController extends Controller
{
    protected string $exportClass = '\App\Exports\UserExport';

    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
        }

    public function __construct()
    {
        $this->traitInitializeModuleTrait(
            'users.title', // module title
            'users', // module name
            'fa-solid fa-clipboard-list' // module icon
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */

    public function index(Request $request)
    {
        $filter = [
            'status' => $request->status,
        ];

        $module_action = 'List';
        $type=$request->type;
        $module_title = $type == 'soon-to-expire' ? 'users.soon_to_expire' : 'users.title';
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
            ]
        ];

        if ($type == 'soon-to-expire') {
            $export_columns[] = [
                'value' => 'expire_date',
                'text' => __('messages.end_date'),
            ];
        }

        $export_url = route('backend.users.export');

        $module_name = 'users';
        return view('user::backend.users.index_datatable', compact('module_action','module_title', 'filter', 'export_import', 'export_columns', 'export_url', 'type', 'module_name'));
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $moduleName = 'User'; // Adjust as necessary for dynamic use

        return $this->performBulkAction(User::class, $ids, $actionType, $moduleName);
    }

    public function index_data(Datatables $datatable, Request $request)
    {
        $query = User::where('user_type','user')->withTrashed();
        $filterValue  = $request->type;
        if($filterValue == 'soon-to-expire'){
            $query = User::role('user');
            $currentDate = Carbon::now();
            $expiryThreshold = $currentDate->copy()->addDays(7);
            $subscriptions = Subscription::with('user')
            ->where('status', 'active')
            ->whereDate('end_date','<=',$expiryThreshold)
            ->get();
            $userIds = $subscriptions->pluck('user_id');
            $query = User::where('user_type','user')->whereIn('id', $userIds);
        }
        $filter = $request->filter;

        if(isset($filter['name'])) {
            $fullName = $filter['name'];

            $query->where(function($query) use ($fullName) {
                $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$fullName%"]);
            });
        }
        if(isset($filter['email'])) {

            $query->where('email',$filter['email']);
        }

        if (isset($filter['column_status'])) {
            $query->where('status', $filter['column_status']);
        }
        return $datatable->eloquent($query)

          ->addColumn('check', function ($data) {
              return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-'.$data->id.'"  name="datatable_ids[]" value="'.$data->id.'" data-type="users" onclick="dataTableRowCheck('.$data->id.', this)">';
          })
          ->editColumn('name', function ($data) {
              return view('user::backend.users.user_details', compact('data'));
          })
          ->editColumn('mobile', function ($data) {
            if($data->mobile != null){
                return $data->mobile;
            }
            return '-';
        })
        ->editColumn('gender', function ($data) {
                $gender = "";
                if ($data->gender != null) {
                    if($data->gender === "male"){
                        $gender = __('messages.lbl_male');
                    } else if($data->gender === "female"){
                        $gender = __('messages.lbl_female');
                    } else {
                        $gender = $data->gender;
                    }
                }
                return $gender ?? '-';
            })
          ->editColumn('action', function ($data) {
             return view('user::backend.users.action_column', compact('data'));
          })
          ->editColumn('expire_date', function ($data) use ($filterValue) {
            if ($filterValue == 'soon-to-expire') {
                $end_date = Carbon::createFromFormat('Y-m-d H:i:s', optional($data->subscriptionPackage)->end_date);
                return formatDate($end_date->format('Y-m-d'));
            }
            return '-';
        })


          ->filterColumn('name', function ($query, $keyword) {
            if (!empty($keyword)) {
                $query->where('first_name', 'like', '%' . $keyword . '%')->orWhere('last_name', 'like', '%' . $keyword . '%')->orWhere('email', 'like', '%' . $keyword . '%');
            }
        })

        ->filterColumn('end_date', function($query, $keyword) {
            try {
                // Attempt to parse the keyword using the display format
                $date = Carbon::createFromFormat('jS F Y', $keyword); // Adjust format based on your display format
                $formattedDate = $date->format('Y-m-d'); // Convert to 'Y-m-d' for the query

                // Apply the formatted date to the query for filtering
                $query->whereDate('end_date', '=', $formattedDate);
            } catch (\Exception $e) {
                // Fallback if parsing fails, use a generic LIKE query
                $query->whereRaw("DATE_FORMAT(end_date, '%D %M %Y') like ?", ["%$keyword%"]);
            }
        })


        ->orderColumn('name', function ($query, $order) {
            $query->orderByRaw("CONCAT(first_name, ' ', last_name) " . validate_sort_direction($order));
        }, 1)


        ->editColumn('status', function ($row) {
            $checked = $row->status ? 'checked="checked"' : ''; // Set the checkbox to checked if status is true
            $disabled = $row->trashed() ? 'disabled' : ''; // Disable if the user is soft-deleted

            return '
                <div class="form-check form-switch">
                    <input type="checkbox" data-url="' . route('backend.users.update_status', $row->id) . '"
                           data-token="' . csrf_token() . '" class="switch-status-change form-check-input"
                           id="datatable-row-' . $row->id . '" name="status" value="' . $row->id . '"
                           ' . $checked . ' ' . $disabled . '>
                </div>
            ';
        })

        ->editColumn('mobile', function ($data) {
            return $data->mobile ? ucwords($data->mobile) : '-';
        })



          ->editColumn('updated_at', fn($data) => $this->formatUpdatedAt($data->updated_at))
          ->rawColumns(['action','name', 'status', 'check','gender'])
          ->orderColumns(['id'], '-:column $1')
          ->make(true);


    }

    private function formatUpdatedAt(Carbon $updatedAt)    
      {
          $diff = Carbon::now()->diffInHours($updatedAt);
          return $diff < 25 ? $updatedAt->diffForHumans() : $updatedAt->isoFormat('llll');
      }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */

      public function create()
    {
        $module_title = __('messages.new_user');
        $mediaUrls = getMediaUrls();
        $page_type='users';

      return view('user::backend.users.form',compact('module_title','mediaUrls','page_type'));
    }

    public function store(UserRequest $request)
    {
        $data = $request->except('profile_image');

        $data['password']=Hash::make($data['password']);
        $data['user_type']='user';

        $data['file_url'] = extractFileNameFromUrl($data['file_url'],'users');


        $user = User::create($data);
        $user->assignRole('user');
        $user->createOrUpdateProfileWithAvatar();

        $message = trans('messages.create_form_user');
        return redirect()->route('backend.users.index')->with('success', 'User added successfully!');

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit(int $id)    
    {
        $data = User::find($id);
        $mediaUrls = getMediaUrls();
        $page_type='users';
        $module_title = __('users.lbl_edit_user');
    return view('user::backend.users.form', compact('data','mediaUrls','module_title','page_type'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, User $user)
    {
        $data = $request->all();

        $data['file_url'] = extractFileNameFromUrl($data['file_url'],'users');

        $user->update($data);
        $user->createOrUpdateProfileWithAvatar();

        $message = trans('messages.update_form_user');

        return redirect()->route('backend.users.index')->with('success', $message);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)    
    {
        $data = User::find($id);
        $data->forceDelete();
        $message = trans('messages.delete_form_user');
        return response()->json(['message' =>  $message, 'status' => true], 200);
    }


    public function update_status(Request $request, User $id)
    {
        $id->update(['status' => $request->status]);

        if ($request->status == 0) {
            $id->tokens()->delete();
            DB::table('sessions')->where('user_id', $id->id)->delete();
        }

        return response()->json(['status' => true, 'message' => __('messages.status_updated_user')]);
    }

    public function changepassword(int $id){

        if(getCurrentProfileSession('is_child_profile') == 1){
            return redirect()->route('user.login');
        }

        $id = $id;
        $module_title = __('messages.change_password');
        return view('user::backend.users.changepassword', compact('id', 'module_title'));

    }

    public function updatePassword(PasswordRequest $request,  int $id){

        $user = User::where('id', $id)->first();

        if ($user == "") {
            $message = __('messages.user_not_found');
            return redirect()->route('backend.users.changepassword',['id' => $id])->with('error', $message);
        }

        $hashedPassword = $user->password;

        $match = Hash::check($request->old_password, $hashedPassword);

        $same_exits = Hash::check($request->password, $hashedPassword);
        if ($match) {
            if ($same_exits) {
                $message = __('messages.same_pass');
                return redirect()->route('backend.users.changepassword',['id' => $user->id])->with('error', $message);
            }

            $user->fill([
                'password' => Hash::make($request->password)
            ])->save();
        sendNotification([
            'notification_type' => 'change_password',
            'user_id' => $user->id,
            'user_name' => $user->full_name ?? $user->name ?? $user->username,
        ]);
            $message = __('messages.pass_successfull');
            return redirect()->route('backend.users.index', $user->id)->with('success', $message);
        } else {
            $message = __('messages.check_old_password');
            return redirect()->route('backend.users.changepassword',['id' => $user->id])->with('error', $message);
        }


    }

    // expire user send mail
    public function sendEmail(Request $request)
    {
        // Get user IDs with subscriptions expiring within 7 days
        $expiryThreshold = Carbon::now()->addDays(7);
        $subscriptions = Subscription::with('user')
        ->where('status', 'active')
        ->whereDate('end_date','<=',$expiryThreshold)
        ->get();
        $userIds = $subscriptions->pluck('user_id');

        // Get users with the retrieved user IDs
        $users = User::whereIn('id', $userIds)->get();

        // Send email to each user
        foreach ($users as $user) {
            // Customize email send
            if (isSmtpConfigured()) {
                Mail::to($user->email)->send(new ExpiringSubscriptionEmail($user));
            }else{
            return response()->json(['message' => 'There is an issue with mail service please check configurations.', 'status' => true], 200);

            }
        }

        $message = __('customer.email_sent');
        return response()->json(['message' => $message, 'status' => true], 200);
    }



    public function details(int $id)    
    {
        $data = User::with(['subscriptiondata'])->findOrFail($id);
        $module_title = __('users.title');
        $show_name = $data->first_name . ' ' . $data->last_name;
        $route = 'backend.users.index';

        return view('user::backend.users.details', compact('data', 'module_title','show_name','route'));
    }

    public function subscriptionData(int $id)    
    {
        $user = User::findOrFail($id);
        $subscriptions = $user->subscriptiondata()->with(['subscription_transaction', 'plan'])
            ->reorder()
            ->orderBy('created_at', 'desc')
            ->orderBy('start_date', 'desc')
            ->get();

        return DataTables::of($subscriptions)
            ->addColumn('plan_name', function ($subscription) {
                return $subscription->name ?? $subscription->plan->name ?? '--';
            })
            ->addColumn('start_date', function ($subscription) {
                return $subscription->start_date ? formatDate(date('Y-m-d', strtotime($subscription->start_date))) : '--';
            })
            ->addColumn('amount', function ($subscription) {
                // Show amount after discount is applied
                $discountedAmount = ($subscription->amount ?? 0) -  ($subscription->amount*($subscription->discount_percentage ?? 0)/100);
                return Currency::format($discountedAmount) ?? '--';
            })
            ->addColumn('tax_amount', function ($subscription) {
                return Currency::format($subscription->tax_amount) ?? '--';
            })
            ->addColumn('coupon_discount', function ($subscription) {
                return Currency::format($subscription->coupon_discount) ?? '--';
            })
            ->addColumn('total_amount', function ($subscription) {
                return Currency::format($subscription->total_amount) ?? '--';
            })
            ->addColumn('duration', function ($subscription) {
                $value =  $subscription->duration ?? $subscription->plan->duration_value ;
                $unit =   $subscription->type ?? $subscription->plan->duration;
                $unit = \Illuminate\Support\Str::plural($unit, $value);
                return $value . ' ' . ucfirst($unit);
            })
            ->addColumn('payment_method', function ($subscription) {
                return ucfirst(optional($subscription->subscription_transaction)->payment_type) ?? '--';
            })
            ->addColumn('transaction_id', function ($subscription) {
                return optional($subscription->subscription_transaction)->transaction_id ?? '--';
            })
            ->addColumn('status', function ($subscription) {
                $status = $subscription->status ?? '--';
                if ($status == 'cancel' || $status == 'cancelled') {
                    return __('messages.lbl_canceled');
                } else if ($status == 'active') {
                    return __('messages.active');
                } else if ($status == 'inactive') {
                    return __('messages.inactive');
                } else if ($status == 'expired') {
                    return __('messages.expired');
                }
                return ucfirst($status);
            })
            ->rawColumns(['plan_name', 'start_date_formatted', 'amount_formatted', 'tax_amount_formatted', 'total_amount_formatted', 'duration_formatted', 'payment_method', 'transaction_id', 'status_formatted'])
            ->make(true);
    }

    public function rentData(int $id)    
    {
        $user = User::findOrFail($id);

        // Filter by the specific user
        $query = PayPerView::query()
            ->with(['user','movie','episode','video','PayperviewTransaction'])
            ->orderBy('created_at', 'desc')
            ->where('user_id', $id);

        return DataTables::of($query)
            ->addColumn('name', function ($data) {
                if($data->type == 'video'){
                     return optional($data->video)->name ?? '-';
                }elseif($data->type == 'episode'){
                    return optional($data->episode)->name ?? '-';
                }elseif($data->type == 'movie'){
                  return optional($data->movie)->name ?? '-';
                }
                return '-';
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
            // PayPerView might not have tax_amount logic in the same way, return 0 or formatted if exists.
            // Assuming 0 for now based on SubscriptionController::RentData not having it explicitly or using different logic.
            // Actually SubscriptionController::RentData did NOT return tax_amount.
            // But details.blade.php expects it. I'll pass '--' or 0.
            ->addColumn('tax_amount', function ($data) {
                return '--';
            })
            ->editColumn('total_amount', function ($data) {
                return Currency::format(optional($data->PayperviewTransaction)->amount);
            })
            ->addColumn('duration', function ($data) {
                return ($data->available_for ?? 0) . ' Days';
            })
            ->addColumn('payment_method', function ($data) {
                return ucfirst(optional($data->PayperviewTransaction)->payment_type ?? '-');
            })
            ->addColumn('transaction_id', function ($data) {
                 return optional($data->PayperviewTransaction)->transaction_id ?? '--';
            })
            ->editColumn('status', function ($data) {
                $status = $data->status ?? '--';
                 if ($status == 'cancel' || $status == 'cancelled') {
                    return __('messages.lbl_canceled');
                } else if ($status == 'active') {
                    return __('messages.active');
                } else if ($status == 'inactive') {
                    return __('messages.inactive');
                } else if ($status == 'expired') {
                    return __('messages.expired');
                }
                return ucfirst($status);
            })
            ->rawColumns(['name', 'start_date', 'end_date', 'amount', 'tax_amount', 'total_amount', 'duration', 'payment_method', 'transaction_id', 'status'])
            ->make(true);
    }

}
