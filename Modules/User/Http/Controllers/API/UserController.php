<?php

namespace Modules\User\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\User\Transformers\UserProfileResource;
use App\Models\User;
use App\Models\Device;
use Modules\Subscriptions\Models\Subscription;
use Modules\Entertainment\Models\ContinueWatch;
use Modules\Entertainment\Models\Watchlist;
use Modules\Entertainment\Models\EntertainmentDownload;
use Modules\Entertainment\Models\UserReminder;
use Modules\User\Transformers\AccountSettingResource;
use App\Models\UserMultiProfile;
use App\Models\UserWatchHistory;
use Illuminate\Support\Facades\DB;
use Modules\User\Transformers\UserProfileResourceV2;
use Modules\User\Transformers\UserProfileResourceV3;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
class UserController extends Controller
{
    public function profileDetails(Request $request){
        $userId = $request->user_id ? $request->user_id : auth()->user()->id;

        $user = User::with('subscriptionPackage', 'watchList', 'continueWatch')->where('id', $userId)->first();

        if($user->is_subscribe == 1){
            $user['plan_details'] = $user->subscriptionPackage;
        }

        $responseData = new UserProfileResource($user);

        return ApiResponse::success($responseData, __('users.user_details'), 200);
    }


    public function accountSetting(Request $request)
    {
        $userId = auth()->user()->id;
        $user = User::with('subscriptionPackage')->where('id', $userId)->first();
        $devices = Device::where('user_id', $userId)->get();

        $your_device = null;
        $other_device = [];

        // Prefer explicit device_id from request, then current token name, finally subscription device
        if ($request->filled('device_id')) {
            $currentDeviceId = $request->device_id;
        } elseif ($request->user() && $request->user()->currentAccessToken()) {
            $currentDeviceId = $request->user()->currentAccessToken()->name;
        } else {
            $currentDeviceId = $user->subscriptionPackage->device_id ?? null;
        }

        if ($devices->isNotEmpty()) {
            foreach ($devices as $device) {
                if ($currentDeviceId && $device->device_id === $currentDeviceId) {
                    $your_device = $device;
                } else {
                    $other_device[] = $device;
                }
            }

            // Fallback only when we truly don't know the current device (no id info)
            if (!$your_device && !$request->filled('device_id')) {
                $your_device = $devices->sortByDesc('updated_at')->first();
                $other_device = $devices->where('id', '!=', optional($your_device)->id)->values();
            }
        }

        $user['your_device']= $your_device;
        $user['other_device']= $other_device;

        // $user['page'] =  Page::where('status',1)->get();


        if ($user->is_subscribe == 1) {
            $plan_details = $user->subscriptionPackage;
            $plan_details['start_date'] = formatDate($plan_details['start_date']);
            $plan_details['end_date'] = formatDate($plan_details['end_date']);
            $user->plan_details = $plan_details;
        }

        $responseData = new AccountSettingResource($user);

        return ApiResponse::success($responseData, __('users.account_setting'), 200);
    }
  public function deviceLogout(Request $request)
{

    $userId = auth()->check() ? auth()->user()->id : $request->input('user_id');

    $deviceQuery = Device::where('user_id', $userId);

    if ($request->has('device_id')) {
        $deviceQuery->where('device_id', $request->device_id);
    }

    if ($request->has('id')) {
        $deviceQuery->orWhere('id', $request->id);
    }

    $device = $deviceQuery->first();

    if (!$device) {
        return ApiResponse::error(__('users.device_not_found'), 404);
    }

    $deviceIdToLogout = $device->device_id;

    // Revoke sanctum tokens for this specific device (token name = device_id)
    try {
        $user = \App\Models\User::find($userId);
        if ($user && class_exists('Laravel\\Sanctum\\PersonalAccessToken')) {
            \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $userId)
                ->where('name', $deviceIdToLogout)
                ->delete();
        }
    } catch (\Throwable $e) {
        // best-effort; ignore errors revoking tokens
    }

    $device->delete();

    try {
        DB::table('sessions')
            ->where('user_id', $userId)
            ->where('ip_address', $deviceIdToLogout)
            ->delete();
    } catch (\Throwable $e) {
        // ignore if sessions table not present
    }

    return ApiResponse::success(null, __('users.device_logout'), 200);
}

    public function deleteAccount(Request $request){
        $userId = auth()->user()->id;

        User::where('id', $userId)->forceDelete();
        Device::where('user_id', $userId)->delete();
        Subscription::where('user_id', $userId)->update(['status' => 'deactivated']);
        ContinueWatch::where('user_id', $userId)->delete();
        Watchlist::where('user_id', $userId)->delete();
        EntertainmentDownload::where('user_id', $userId)->delete();
        UserReminder::where('user_id', $userId)->delete();
        UserMultiProfile::where('user_id', $userId)->forceDelete();

        return ApiResponse::success(null, __('users.delete_account'), 200);
    }

    public function logoutAll(Request $request)
    {
        $userId = auth()->check() ? auth()->user()->id : $request->input('user_id');
        
        if (!$userId) {
            return ApiResponse::error(__('users.user_not_found'), 404);
        }
    
        // Get all device IDs and session IDs BEFORE deleting devices
        $deviceIds = [];
        $sessionIds = [];
        $deviceCount = 0;
        
        try {
            $devices = Device::where('user_id', $userId)->get();
            $deviceCount = $devices->count(); // Store device count before deletion
            foreach ($devices as $device) {
                $deviceIds[] = $device->device_id;
                if ($device->session_id) {
                    $sessionIds[] = $device->session_id;
                }
            }
            
            // Get user's plan device limit
            $user = User::find($userId);
            $planDeviceLimit = 1; // Default limit
            if ($user && $user->subscriptionPackage && !empty($user->subscriptionPackage->plan_type)) {
                $planLimitations = json_decode($user->subscriptionPackage->plan_type, true);
                if (is_array($planLimitations)) {
                    foreach ($planLimitations as $limitation) {
                        if (isset($limitation['slug']) && $limitation['slug'] === 'device-limit') {
                            if (isset($limitation['limitation_value']) && $limitation['limitation_value'] == 1) {
                                $limitData = $limitation['limit'] ?? null;
                                if (is_array($limitData) && isset($limitData['value'])) {
                                    $planDeviceLimit = (int)$limitData['value'];
                                } elseif (is_string($limitData) || is_numeric($limitData)) {
                                    $planDeviceLimit = (int)$limitData;
                                }
                            }
                            break;
                        }
                    }
                }
            }
            
            // Store device count and plan limit in cache for use in middleware (valid for 10 minutes)
            // Store by user_id for authenticated checks
            Cache::put('logout_device_count_' . $userId, $deviceCount, 600);
            Cache::put('logout_plan_limit_' . $userId, $planDeviceLimit, 600);
            
            // Also store by device IPs for unauthenticated checks (since session is invalidated)
            foreach ($deviceIds as $deviceIp) {
                Cache::put('logout_info_' . $deviceIp, [
                    'user_id' => $userId,
                    'device_count' => $deviceCount,
                    'plan_limit' => $planDeviceLimit
                ], 600);
            }
        } catch (\Throwable $e) {
            \Log::info('Error getting devices: ' . $e->getMessage());
        }
    
        // Delete all devices for this user
        try {
            Device::where('user_id', $userId)->delete();
        } catch (\Throwable $e) {
            \Log::info('Error deleting devices: ' . $e->getMessage());
        }
    
        // Delete all sessions for this user - multiple methods to ensure complete deletion
        try {
            // Delete by user_id
            DB::table('sessions')
                ->where('user_id', $userId)
                ->delete();
            
            // Delete by session_id (from devices)
            if (!empty($sessionIds)) {
                DB::table('sessions')
                    ->whereIn('id', $sessionIds)
                    ->delete();
            }
            
            // Delete by ip_address matching device_id (for web browsers)
            if (!empty($deviceIds)) {
                DB::table('sessions')
                    ->where('user_id', $userId)
                    ->whereIn('ip_address', $deviceIds)
                    ->delete();
            }
        } catch (\Throwable $e) {
            \Log::info('Error deleting sessions: ' . $e->getMessage());
        }
    
        // Revoke all Sanctum tokens for this user - CRITICAL for mobile apps
        try {
            // Method 1: Delete via Eloquent model (if available)
            if (class_exists(\Laravel\Sanctum\PersonalAccessToken::class)) {
                \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $userId)
                    ->where('tokenable_type', 'App\Models\User')
                    ->delete();
            }
            
            // Method 2: Direct database deletion (ensures all tokens are removed)
            DB::table('personal_access_tokens')
                ->where('tokenable_id', $userId)
                ->where('tokenable_type', 'App\Models\User')
                ->delete();
                
            // Method 3: Also delete by token name (device_id) if available (extra safety)
            if (!empty($deviceIds)) {
                DB::table('personal_access_tokens')
                    ->where('tokenable_id', $userId)
                    ->whereIn('name', $deviceIds)
                    ->delete();
            }
            
            // Method 4: Force delete ALL tokens for user regardless of type
            DB::table('personal_access_tokens')
                ->where('tokenable_id', $userId)
                ->delete();
                
            \Log::info('logoutAll: All Sanctum tokens revoked for user_id: ' . $userId);
        } catch (\Throwable $e) {
            \Log::error('Error revoking tokens in logoutAll: ' . $e->getMessage());
        }
    
        // Logout current device if session exists
        if (Auth::check() && $request->hasSession()) {
            try {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            } catch (\Throwable $e) {
                \Log::info('Error logging out current session: ' . $e->getMessage());
            }
        }
    
        return ApiResponse::success([], __('users.device_logout'), 200);
    }

    public function saveWatchHistory(Request $request)
    {
        $user = auth()->user();
        $data = $request->all();

        $profile_id=$request->has('profile_id') && $request->profile_id
        ? $request->profile_id
        : getCurrentProfile($user->id, $request);

        $data['profile_id']=$profile_id;


        $search_data  = [
            'user_id' => $user->id,
            'entertainment_id' =>$data['entertainment_id'],
            'profile_id' => $data['profile_id'],
            'entertainment_type' => $data['entertainment_type']
        ];
        UserWatchHistory::create($search_data);

        ContinueWatch::where('user_id',$user->id)->where('profile_id',$profile_id)->where('entertainment_id',$data['entertainment_id'])->where('entertainment_type', $data['entertainment_type'])->forceDelete();


        return ApiResponse::success(null, __('movie.history_save'), 200);
    }

    public function profileDetailsV2(Request $request)
    {
        $userId = $request->user_id ? $request->user_id : auth()->user()->id;

        $profile_id = isset($request->profile_id) ? $request->profile_id : NULL;

        $user = User::with('subscriptionPackage')
        ->with(['watchList' => function($q) use($userId,$profile_id){
            $q->where('user_id', $userId)
                ->where('profile_id', $profile_id);
        }])
        ->with(['continueWatchnew' => function($q) use($userId,$profile_id){
            $q->where('user_id', $userId)
                ->where('profile_id', $profile_id)
                ->orderBy('created_at', 'desc');
        }])
        ->where('id', $userId)->first();

        if($user->is_subscribe == 1){
            $user['plan_details'] = $user->subscriptionPackage;
        }

        $responseData = new UserProfileResourceV2($user);

        return ApiResponse::success($responseData, __('users.user_details'), 200);
    }

    public function profileDetailsV3(Request $request)
    {
        $userId = $request->user_id ? $request->user_id : auth()->user()->id;
        $profile_id = isset($request->profile_id) ? $request->profile_id : NULL;
        $device_type = getDeviceType($request);
       
        $user = User::with('subscriptionPackage')
            ->with(['watchList' => function($q) use($userId,$profile_id){
                $q->where('user_id', $userId)
                    ->where('profile_id', $profile_id);
            }])
            ->with(['continueWatchnew' => function($q) use($userId,$profile_id){
                $q->where('user_id', $userId)
                    ->where('profile_id', $profile_id)
                    ->orderBy('created_at', 'desc');
            }])
            ->where('id', $userId)->first();
         
        if($user->is_subscribe == 1){
            $user['plan_details'] = $user->subscriptionPackage;
        }

        $user['watching_profiles'] = UserMultiProfile::where('user_id', $userId)->get();

        $responseData = new UserProfileResourceV3($user);

        return ApiResponse::success($responseData, __('users.user_details'), 200);
    }
}
