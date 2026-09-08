<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * API Locale Middleware
 * 
 * This middleware handles localization specifically for Mobile App API requests.
 * It checks for the 'app-locale' header and sets the application locale accordingly.
 * 
 * - If 'app-locale' header is present → Use that locale (mobile app)
 * - If 'app-locale' header is absent → Don't change locale (frontend/web uses session)
 */
class ApiLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Only process if the 'app-locale' header is present (Mobile App request)
        if ($request->hasHeader('app-locale')) {
            $locale = strtolower(trim($request->header('app-locale')));
            
            // Get available locales from config
            $availableLocales = array_keys(config('app.available_locales', ['en' => 'English (EN)']));
            
            // Validate locale - must be in available locales
            if (in_array($locale, $availableLocales, true)) {
                app()->setLocale($locale);
            } else {
                // Invalid locale - fallback to default 'en'
                app()->setLocale('en');
            }
        }
        // If no 'app-locale' header, don't change the locale
        // This allows frontend/web to use session-based locale from SetLocale middleware

        return $next($request);
    }
}
