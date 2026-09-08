<?php

namespace App\Http\Middleware;

use Closure;

class localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    {
        // Get session locale or default to 'en'
        $sessionLocal = session()->get('locale') ? session()->get('locale') : 'en';

        // Default locale
        $local = 'en';

        // Priority: global-localization → session
        if ($request->hasHeader('global-localization')) {
            $local = strtolower($request->header('global-localization'));
        } else {
            $local = strtolower($sessionLocal);
        }

        // Validate available locales (fallback to 'en')
        $availableLocales = array_keys(config('app.available_locales', ['en' => 'English (EN)']));
        if (!in_array($local, $availableLocales, true)) {
            $local = 'en';
        }

        // Set Laravel localization
        app()->setLocale($local);

        // Continue request
        return $next($request);
    }
}
