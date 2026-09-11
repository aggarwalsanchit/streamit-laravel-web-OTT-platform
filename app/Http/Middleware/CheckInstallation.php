<?php

namespace App\Http\Middleware;

use App\Helpers\Classes\Helper;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class CheckInstallation
{
    public function handle(Request $request, Closure $next): Response
{
    // 🔥 Skip health checks, static assets, AND install pages
    $path = $request->path();
    if (in_array($path, ['up', 'health', 'healthz', 'favicon.ico', 'robots.txt'], true)
        || str_starts_with($path, '_')
        || str_starts_with($path, 'build/')
        || str_starts_with($path, 'storage/')
        || str_starts_with($path, 'install')) {  // ← ADDED
        return $next($request);
    }

    try {
        $dbConnectionStatus = dbConnectionStatus();

        if ($dbConnectionStatus && Schema::hasTable('users') && file_exists(storage_path('installed'))) {
            $activeStorage = DB::table('settings')->where('name', 'disc_type')->value('val') ?? 'local';
            Config::set('filesystems.default', $activeStorage);
            return $next($request);
        }

        // ⚠️ Only redirect if we're NOT already on install page
        if (!$request->is('install*') && !$request->is('api/*')) {
            return redirect()->route('install.index');
        }

        return $next($request);

    } catch (QueryException $e) {
        if (str_contains($e->getMessage(), 'Access denied for user')) {
            if (!$request->is('install*')) {
                return redirect()->route('install.index');
            }
        }
        throw $e;
    }
}
}
