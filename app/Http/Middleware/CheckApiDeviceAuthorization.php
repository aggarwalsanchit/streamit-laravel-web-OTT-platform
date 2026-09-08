<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Device;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;

class CheckApiDeviceAuthorization
{
    /**
     * Handle an incoming request.
     * Check if user's device exists - if logoutAll was called, device should not exist
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        
        // Check if user is authenticated via Sanctum (Bearer token)
        if ($request->user()) {

            $user = $request->user();
            
            // Skip check for admin users
            if ($user && ($user->hasRole(['admin', 'super-admin', 'demo_admin']))) {
                return $next($request);
            }
            
            // Get device_id from token name or request
            $deviceId = null;
            $token = $request->user()->currentAccessToken();
            
            // // Only PersonalAccessToken has name property (not TransientToken for web requests)
            // if ($token && $token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            //     // Token name is typically the device_id for mobile apps
            //     $deviceId = $token->name;
            // }
            
            // Fallback: try to get from request
            if (!$deviceId && $request->filled('device_id')) {
                $deviceId = $request->input('device_id');
            }
            
            // If no device_id, skip check (might be web request)
            if (!$deviceId) {
                return $next($request);
            }
            $deviceExists = Device::where('user_id', $user->id)
                ->where('device_id', $deviceId)
                ->exists();
           
            // If device doesn't exist, it means logoutAll was called OR device was deleted
            // For authenticated users with valid tokens, the device MUST exist
            // Return 401 to force mobile app to logout
            if (!$deviceExists) {
                \Log::info("Device token not found ");
               
                // Revoke token and return 401
                // Only delete PersonalAccessToken (not TransientToken)
                if ($token && $token instanceof \Laravel\Sanctum\PersonalAccessToken) {
                    $token->delete();
                }
                return response()->json([
                    'status' => false,
                    'message' => __('messages.logout_all_text'),
                    'code' => 401
                ], 401);
            }
        }
    
        return $next($request);
    }
}
