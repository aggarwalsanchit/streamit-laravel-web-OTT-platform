<?php

namespace App\Providers;

use Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Intentionally empty — never query DB here
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        if ($this->shouldSkip()) {
            return;
        }

        if (Config::get('app._settings_loaded')) {
            return;
        }

        try {
            if (!Schema::hasTable('settings')) {
                Config::set('app._settings_loaded', true);
                return;
            }

            $settings = DB::table('settings')
                ->whereIn('type', ['mail_config', 'bussiness', 'misc'])
                ->get();

            if ($settings->count() > 0) {
                $this->applySettings($settings);
            }

            Config::set('app._settings_loaded', true);

        } catch (\Throwable $e) {
            Log::warning('ConfigServiceProvider: ' . $e->getMessage());
            Config::set('app._settings_loaded', true);
        }
    }

    protected function shouldSkip(): bool
    {
        if (!app()->bound('request')) {
            return true;
        }

        $path = request()->path();

        return in_array($path, ['up', 'health', 'healthz', 'favicon.ico', 'robots.txt'], true)
            || str_starts_with($path, '_')
            || str_starts_with($path, 'build/')
            || str_starts_with($path, 'storage/');
    }

    protected function applySettings($settings): void
    {
        $map = [
            'app_name'         => 'app.name',
            'mail_driver'      => 'mail.driver',
            'mail_host'        => 'mail.host',
            'mail_port'        => 'mail.port',
            'mail_from'        => 'mail.from.address',
            'from_name'        => 'mail.from.name',
            'mail_encryption'  => 'mail.encryption',
            'mail_username'    => 'mail.username',
            'mail_password'    => 'mail.password',
            'default_language' => 'app.locale',
        ];

        foreach ($map as $settingName => $configKey) {
            $value = $this->getSetting($settings, $settingName);
            if ($value !== null) {
                Config::set($configKey, $value);
            }
        }
    }

    public function getSetting($model, $name)
    {
        $query = $model->where('name', $name)->first();
        return $query->val ?? null;
    }
}
