<?php

namespace App\Services\ShortDrama;

/**
 * Whether the Short Drama add-on is installed and registered (modules_statuses + module on disk).
 */
final class ShortDramaAddonStatus
{
    public static function isPresent(): bool
    {
        $modulesFile = base_path('modules_statuses.json');
        $statuses = file_exists($modulesFile)
            ? json_decode(file_get_contents($modulesFile), true) ?? []
            : [];

        $registered = isset($statuses['ShortDrama']) && $statuses['ShortDrama'];
        $moduleDir = base_path('Modules/ShortDrama');
        $hasFiles = is_dir($moduleDir) && file_exists($moduleDir.'/module.json');

        return $registered && $hasFiles;
    }
}
