<?php

namespace App\Providers;

use Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * ⚠️  Do NOT query the database here — it runs on EVERY request,
     *     including Render's health checks, and will cause 502 timeouts.
     */
    public function register()
    {
        // Intentionally left empty.
        // Settings are loaded lazily in boot() below.
    }

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // Skip DB work in console (build, migrations, queue workers)
        if ($this->app->runningInConsole()) {
            return;
        }

        // Skip for Render health checks & asset requests
        if ($this->isHealthCheck()) {
            return;
        }

        // Skip if a previous request already loaded the settings
        if (Config::get('app._settings_loaded')) {
            return;
        }

        try {
            if (!Schema::hasTable('settings')) {
                return;
            }

            $settings = DB::table('settings')
                ->whereIn('type', ['mail_config', 'bussiness', 'misc'])
                ->get();

            if ($settings->count() > 0) {
                $this->applySettings($settings);
            }

            // Mark as loaded so we don't repeat this in the same request
            Config::set('app._settings_loaded', true);

        } catch (\Throwable $e) {
            // Never let settings crash the app
            Log::warning('ConfigServiceProvider: ' . $e->getMessage());
        }
    }

    /**
     * Apply the settings to the Config repository.
     */
    protected function applySettings($settings): void
    {
        $map = [
            'app_name'          => ['app.name',          null],
            'mail_driver'       => ['mail.driver',       null],
            'mail_host'         => ['mail.host',         null],
            'mail_port'         => ['mail.port',         null],
            'mail_from'         => ['mail.from.address', null],
            'from_name'         => ['mail.from.name',    null],
            'mail_encryption'   => ['mail.encryption',   null],
            'mail_username'     => ['mail.username',     null],
            'mail_password'     => ['mail.password',     null],
            'default_language'  => ['app.locale',        null],
        ];

        foreach ($map as $settingName => [$configKey, $default]) {
            $value = $this->getSetting($settings, $settingName);
            if ($value !== null) {
                Config::set($configKey, $value);
            }
        }
    }

    /**
     * Look up a single setting value from the collection.
     */
    public function getSetting($model, $name)
    {
        $query = $model->where('name', $name)->first();
        return $query->val ?? null;
    }

    /**
     * Skip DB queries for Render's health checks and static assets.
     */
    protected function isHealthCheck(): bool
    {
        if (!app()->bound('request')) {
            return false;
        }

        $path = request()->path();

        // Render health checks + static assets
        return in_array($path, ['up', 'health', 'healthz', 'favicon.ico', 'robots.txt'], true)
            || str_starts_with($path, '_')
            || str_starts_with($path, 'build/');
    }
}
