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
        // 🔥 Skip health checks and static assets entirely
        $path = $request->path();
        if (in_array($path, ['up', 'health', 'healthz', 'favicon.ico', 'robots.txt'], true)
            || str_starts_with($path, '_')
            || str_starts_with($path, 'build/')
            || str_starts_with($path, 'storage/')) {
            return $next($request);
        }

        try {
            $dbConnectionStatus = dbConnectionStatus();

            if ($dbConnectionStatus && Schema::hasTable('users') && file_exists(storage_path('installed'))) {

                $activeStorage = DB::table('settings')->where('name', 'disc_type')->value('val') ?? 'local';
                Config::set('filesystems.default', $activeStorage);

                return $next($request);
            } else {
                return redirect()->route('install.index');
            }
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'Access denied for user')) {
                return redirect()->route('install.index');
            }
            throw $e;
        }
    }
}
