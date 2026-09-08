<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Device;

class CheckUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if (!Auth::check()) {
            // Check if there's logout info in cache for this IP (from logoutAll)
            $currentIp = $request->getClientIp();
            $logoutInfo = Cache::get('logout_info_' . $currentIp);
            
            if ($logoutInfo && isset($logoutInfo['user_id'])) {
                $deviceCount = $logoutInfo['device_count'] ?? 0;
                $planDeviceLimit = $logoutInfo['plan_limit'] ?? 1;
                
                // Construct message with device count and limit
                if ($deviceCount > 0) {
                    $logoutMessage = __('messages.logout_all_text') . ' (' . $deviceCount . ' ' . __('messages.devices_logged_in') . ', ' . __('messages.limit_is') . ' ' . $planDeviceLimit . ')';
                } else {
                    $logoutMessage = __('messages.logout_all_text') . ' (' . __('messages.limit_is') . ' ' . $planDeviceLimit . ')';
                }
                
                // Clear cache after using it
                Cache::forget('logout_info_' . $currentIp);
                if (isset($logoutInfo['user_id'])) {
                    Cache::forget('logout_device_count_' . $logoutInfo['user_id']);
                    Cache::forget('logout_plan_limit_' . $logoutInfo['user_id']);
                }
                
                return redirect()->route('login-page')->with('error', $logoutMessage);
            }
            
            return redirect()->route('login-page');
        }

        if (Auth::user()->status == 0) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login-page')->with('error', __('messages.user_account_inactive'));
        }

        return $next($request);
    }
}
