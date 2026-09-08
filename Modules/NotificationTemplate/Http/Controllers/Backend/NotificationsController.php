<?php
namespace Modules\NotificationTemplate\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laracasts\Flash\Flash;
use Yajra\DataTables\DataTables;
use App\Models\User;
use App\Traits\ModuleTrait;
use Modules\Constant\Models\Constant;

class NotificationsController extends Controller
{
     use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
        }
    public function __construct()
    {
        // Page Title
        $this->module_title = 'messages.title_list';

        // module name
        $this->module_name = 'notifications';

        // directory path of the module
        $this->module_path = 'notifications';

        // module icon
        $this->module_icon = 'c-icon fas fa-bell';

        // module model name, path
        $this->module_model = "App\Models\User";

        $this->middleware(['permission:view_notification'])->only('index');
        $this->middleware(['permission:edit_notification'])->only('edit', 'update');
        $this->middleware(['permission:add_notification'])->only('store');
        $this->middleware(['permission:delete_notification'])->only('destroy');
    }
    /**
     * Display a listing of the resource.
     */
   public function index()
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'List';

        $user = auth()->user();

        if (count($user->unreadNotifications) > 0) {
            $user->unreadNotifications->markAsRead();
        }

        $$module_name = auth()->user()->notifications()->paginate();
        $unread_notifications_count = auth()->user()->unreadNotifications()->count();

        $notifications_count = 0;

        return view(
            "notificationtemplate::backend.$module_path.index",
            compact('module_title', 'module_name', "$module_name", 'module_path', 'module_icon', 'module_action', 'module_name_singular', 'unread_notifications_count', 'notifications_count')
        );
    }
    public function index_data(Datatables $datatable, Request $request)
    {
        // $user = auth()->user();

        // $query = Notification::where('notifiable_id',$user->id)->value('id');
      // Fetch ALL notifications for Super Admin (not just the logged-in admin's notifications)
        $query = Notification::query();

        // Apply filters
        $filter = $request->get('filter', []);
        
        // Filter by notification type
        if (!empty($filter['type'])) {
            $query->where(function($q) use ($filter) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.data.notification_type')) = ?", [$filter['type']]);
            });
        }

        // Filter by user name and/or notification type (search in user's name, email, or notification type)
        if (!empty($filter['search_user'])) {
            $searchTerm = $filter['search_user'];
            $query->where(function($q) use ($searchTerm) {
                // Search by notification type (in JSON data and constant names)
                $q->where(function($typeQ) use ($searchTerm) {
                    // Search in notification_type value in JSON
                    $typeQ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.data.notification_type')) LIKE ?", ['%' . $searchTerm . '%']);
                    
                    // Also search in constant names (notification type display names)
                    $matchingTypes = Constant::where('type', 'notification_type')
                        ->where(function($constQuery) use ($searchTerm) {
                            $constQuery->where('name', 'like', '%' . $searchTerm . '%')
                                      ->orWhere('value', 'like', '%' . $searchTerm . '%');
                        })
                        ->pluck('value')
                        ->toArray();
                    
                    if (!empty($matchingTypes)) {
                        foreach ($matchingTypes as $typeValue) {
                            $typeQ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.data.notification_type')) = ?", [$typeValue]);
                        }
                    }
                });
                
                // Search by user name (in user's name or email)
                $q->orWhere(function($userQ) use ($searchTerm) {
                    // Get user IDs that match the search term
                    $userIds = User::where(function($userQuery) use ($searchTerm) {
                        $userQuery->where('first_name', 'like', '%' . $searchTerm . '%')
                                  ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                                  ->orWhere('email', 'like', '%' . $searchTerm . '%')
                                  ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", ['%' . $searchTerm . '%']);
                    })->pluck('id')->toArray();

                    if (!empty($userIds)) {
                        // Search in JSON data for user_id
                        foreach ($userIds as $userId) {
                            $userQ->orWhereRaw("JSON_EXTRACT(data, '$.data.user_id') = ?", [$userId])
                                 ->orWhereRaw("JSON_EXTRACT(data, '$.data.user_id') = ?", [(string)$userId]);
                        }
                    }
                });
            });
        }

        return $datatable->eloquent($query)
        ->addColumn('check', function ($data) {
            return '<input type="checkbox" class="form-check-input select-table-row"
                id="datatable-row-' . e($data->id) . '"
                name="datatable_ids[]"
                value="' . e($data->id) . '"
                data-type="notifications"
                onclick="dataTableRowCheck(\'' . e($data->id) . '\',this)">';
        })
            ->addColumn('action', function ($data) {
                return view('notificationtemplate::backend.notifications.action_column', compact('data'));
            })
            ->editColumn('updated_at', function ($row) {
                return formatDateTimeWithTimezone($row->updated_at);
            })
            ->addColumn('id', function ($row) {
                $data = $this->decodeData($row->data);
                return $data['data']['id'] ?? '-';
            })
            ->addColumn('type', function ($row) {
                $data = $this->decodeData($row->data);

                $notificationGroup = $data['data']['notification_group'] ?? null;
                $notificationType = $data['data']['notification_type'] ?? null;

                $label = '-';

                if ($notificationType) {
                    $typeConst = Constant::where('type', 'notification_type')->where('value', $notificationType)->first();
                    if ($typeConst && $typeConst->name) {
                        $label = $typeConst->name;
                    } else {
                        $label = ucwords(str_replace('_', ' ', $notificationType));
                    }
                }
                // Make subscription and pay_per_view notifications clickable
                if ($notificationGroup && ($notificationGroup == 'subscription' || $notificationGroup == 'pay_per_view')) {
                    $userId = $data['data']['user_id'] ?? null;
                    if ($userId) {
                        return '<a href="' . route('backend.users.details', ['id' => $userId]) . '">' . e($label) . '</a>';
                    }
                }
                return e($label);
            })
            ->addColumn('text', function ($data) {
                // $data = $this->decodeData($row->data);
                // return $data['subject'] ?? '';

                return view('notificationtemplate::backend.notifications.type', compact('data'));
            })
            ->addColumn('customer', function ($row) {
                $data = $this->decodeData($row->data);
                $user_id = $data['data']['user_id'] ?? '';
                $user = User::find($user_id);

                return view('components.user-detail-card', [
                    'image' => setBaseUrlWithFileName(optional($user)->file_url,'image','users') ?? default_user_avatar(),
                    'name' => optional($user)->full_name ?? '-',
                    'email' => optional($user)->email ?? '-'
                ])->render();
            })
            ->orderColumn('type', function ($query, $order) {
                $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(notifications.data, '$.data.notification_type')) " . validate_sort_direction($order));
            })
            ->orderColumn('customer', function ($query, $order) {
                // Order by user_id in JSON data
                $direction = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
                $query->orderByRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(notifications.data, '$.data.user_id')) AS UNSIGNED) $direction");
            })
            ->orderColumn('updated_at', function ($query, $order) {
                $query->orderBy('notifications.updated_at', $order);
            })
            ->rawColumns(['action', 'check', 'customer','type'])
            ->orderColumns(['updated_at'], '-:column $1')
            ->make(true);
    }
    /**
     * Decode notification data - handles both JSON string and array formats
     * Since Notification model casts 'data' to array, this handles both cases
     * 
     * @param string|array $data
     * @return array
     */
    private function decodeData($data): array {    
        if (is_array($data)) {
            return $data;
        }
        
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        return [];
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('notificationtemplate::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id)
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'Show';

        $$module_name_singular = Notification::where('id', '=', $id)->where('notifiable_id', '=', auth()->user()->id)->first();

        if ($$module_name_singular) {
            if ($$module_name_singular->read_at == '') {
                $$module_name_singular->read_at = Carbon::now();
                $$module_name_singular->save();
            }
        }

        return view(
            "notificationtemplate::backend.$module_name.show",
            compact('module_title', 'module_name', 'module_icon', 'module_name_singular', 'module_action', "$module_name_singular")
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        return view('notificationtemplate::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        //
    }

    public function deleteAll()
    {
        $user = auth()->user();

        $user->notifications()->delete();

        Flash::success("<i class='fas fa-check'></i> All Notifications Deleted")->important();

        return back();
    }

    public function markAllAsRead()
    {
        $user = auth()->user();

        $user->unreadNotifications()->update(['read_at' => now()]);

        Flash::success("<i class='fas fa-check'></i> All Notifications Marked As Read")->important();

        return back();
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $moduleName = __('sidebar.notification_list');
        return $this->performBulkAction(Notification::class, $ids, $actionType, $moduleName);
    }

    public function notificationList(Request $request)
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'Show';
        $user = auth()->user();
        $user->last_notification_seen = now();
        $user->save();

        $type = isset($request->type) ? $request->type : null;
        if ($type == 'markas_read') {
            if (count($user->unreadNotifications) > 0) {
                $user->unreadNotifications->markAsRead();
            }
            $notifications = $user->notifications->take(5);
        } elseif ($type == null) {
            $notifications = $user->notifications->take(5);
        } else {
            $notifications = $user->notifications->where('data.type', $type)->take(5);
        }
        $all_unread_count = isset($user->unreadNotifications) ? $user->unreadNotifications->count() : 0;


        return response()->json([
            'status' => true,
            'type' => $type,
            'data' => view("notificationtemplate::backend.$module_name.list", compact('notifications', 'all_unread_count', 'user'))->render(),
        ]);
    }

    public function notificationRemove(string $id)
    {
        try {
            $notification = Notification::find($id);

            if (!$notification) {
                return response()->json([
                    'message' => __('messages.notification_not_found'),
                    'status' => false
                ], 404);
            }

            $notification->delete();

            return response()->json([
                'message' => __('notification.notification_deleted'),
                'status' => true
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Notification delete failed: ' . $e->getMessage());

            return response()->json([
                'message' => __('messages.something_went_wrong'),
                'status' => false
            ], 500);
        }
    }

    public function notificationCounts(Request $request)
    {
        $user = auth()->user();
        $unread_count = 0;
        $unread_total_count = 0;

        if (isset($user->unreadNotifications)) {
            $unread_count = $user->unreadNotifications->where('created_at', '>', $user->last_notification_seen)->count();
            $unread_total_count = $user->unreadNotifications->count();
        }

        return response()->json([
            'status' => true,
            'counts' => $unread_count,
            'unread_total_count' => $unread_total_count,
        ]);
    }
}
