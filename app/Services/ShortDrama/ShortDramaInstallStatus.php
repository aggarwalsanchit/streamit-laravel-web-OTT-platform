<?php

namespace App\Services\ShortDrama;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Client-facing installer status checklist (CodeCanyon upload flow).
 */
final class ShortDramaInstallStatus
{
    /** @var list<string> */
    public const TABLES = [
        'short_dramas',
        'short_drama_episodes',
        'short_drama_content_sections',
        'short_drama_section_items',
        'short_drama_user_preferences',
        'short_drama_watch_history',
        'short_drama_drm_tokens',
        'short_drama_banners',
    ];

    /**
     * @return array<string, bool>
     */
    public static function checks(): array
    {
        return [
            __('messages.short_drama_installer_status_files') => self::hasModuleFiles(),
            __('messages.short_drama_installer_status_module') => self::isRegistered(),
            __('messages.short_drama_installer_status_database') => self::allTablesPresent(),
            __('messages.short_drama_installer_status_assets') => self::hasFrontendAssets(),
            __('messages.short_drama_installer_status_settings') => self::hasFeatureSettings(),
        ];
    }

    public static function hasFrontendAssets(): bool
    {
        return self::hasPublicBundles() && self::hasMixManifestEntries();
    }

    public static function isInstalled(): bool
    {
        return ShortDramaAddonStatus::isPresent();
    }

    public static function allChecksPass(): bool
    {
        foreach (self::checks() as $ok) {
            if (! $ok) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<array{label: string, ok: bool}>
     */
    public static function checksForApi(): array
    {
        $rows = [];

        foreach (self::checks() as $label => $ok) {
            $rows[] = ['label' => $label, 'ok' => (bool) $ok];
        }

        return $rows;
    }

    public static function hasModuleFiles(): bool
    {
        $moduleDir = base_path('Modules/ShortDrama');

        return is_dir($moduleDir) && File::exists($moduleDir.'/module.json');
    }

    public static function isRegistered(): bool
    {
        $statusFile = base_path('modules_statuses.json');
        $statuses = File::exists($statusFile)
            ? json_decode(File::get($statusFile), true) ?? []
            : [];

        return isset($statuses['ShortDrama']) && $statuses['ShortDrama'];
    }

    public static function allTablesPresent(): bool
    {
        foreach (self::TABLES as $table) {
            if (! self::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    public static function hasPublicBundles(): bool
    {
        $destRoot = public_path('modules/shortdrama');

        foreach (ShortDramaMixManifest::SHORT_DRAMA_BUNDLES as $name) {
            if (! File::isFile($destRoot.DIRECTORY_SEPARATOR.$name)) {
                return false;
            }
        }

        return true;
    }

    public static function hasMixManifestEntries(): bool
    {
        $manifestPath = public_path('mix-manifest.json');

        if (! File::isFile($manifestPath)) {
            return false;
        }

        $decoded = json_decode(File::get($manifestPath), true);
        if (! is_array($decoded)) {
            return false;
        }

        foreach (ShortDramaMixManifest::SHORT_DRAMA_BUNDLES as $name) {
            $key = '/modules/shortdrama/'.$name;
            if (! isset($decoded[$key]) || ! is_string($decoded[$key]) || $decoded[$key] === '') {
                return false;
            }
        }

        return true;
    }

    public static function hasFeatureSettings(): bool
    {
        if (! self::hasTable('settings')) {
            return false;
        }

        try {
            return DB::table('settings')->where('name', 'short_drama_feature_name')->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    private static function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
