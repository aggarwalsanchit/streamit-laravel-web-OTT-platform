<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleBasedRouteAccess
{
    /**
     * Handle an incoming request.
     * Prevents role switching via URL manipulation.
     * - Admin users accessing non-admin routes (without /app/) -> redirect to admin dashboard
     * - User accessing admin routes (with /app/) -> redirect to user home
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $path = $request->path();
        $user = Auth::user();
        $isAdminRoute = str_starts_with($path, 'app/') || $path === 'app';

        if ($path === 'admin/logout' || $path === 'admin/confirm-password') {
            return $next($request);
        }

        // Admin trying to access user routes (non /app/ routes)
        if ($user->hasRole(['admin', 'super-admin', 'demo_admin']) && !$isAdminRoute) {
            return redirect()->route('backend.home');
        }

        // User trying to access admin routes (/app/ routes)
        if ($user->hasRole('user') && $isAdminRoute) {
            return redirect()->route('user.login');
        }

        if ($user->hasRole('user')) {
            $routeName = $request->route() ? $request->route()->getName() : null;
            if ($routeName && in_array($routeName, ['manage-profile', 'profile-management', 'profile.destroy', 'user-logout'])) {
                return $next($request);
            }

            if (str_contains($path, 'manage-profile') || str_contains($path, 'profile-management') || str_contains($path, 'profile/delete')) {
                return $next($request);
            }
            
            $currentProfile = getCurrentProfileSession();
            
            if (!$currentProfile) {
                $profileCount = \App\Models\UserMultiProfile::where('user_id', $user->id)->count();
                
                if ($profileCount > 0) {
                    return redirect()->route('manage-profile');
                } else {
                    return redirect()->route('profile-management');
                }
            }
        }

        return $next($request);
    }
}
