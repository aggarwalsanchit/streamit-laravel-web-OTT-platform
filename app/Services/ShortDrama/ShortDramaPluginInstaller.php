<?php

namespace App\Services\ShortDrama;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Console\Output\BufferedOutput;
use ZipArchive;

/**
 * Lives in the main application so the Short Drama add-on can be installed
 * before Modules/ShortDrama is present or registered.
 */
class ShortDramaPluginInstaller
{
    private const MODULE_NAME = 'ShortDrama';

    private const MODULES_PATH = 'Modules';

    private const STATUSES_FILE = 'modules_statuses.json';

    /** Must match the real directory on disk (Linux is case-sensitive). */
    private const MIGRATION_PATH = 'Modules/ShortDrama/database/migrations';

    /** Built bundles shipped inside the add-on ZIP (see short-drama:package). */
    private const DIST_DIR = 'Modules/ShortDrama/Resources/dist';

    private const PUBLIC_BUNDLES = ShortDramaMixManifest::SHORT_DRAMA_BUNDLES;

    /** @var array<string, mixed> */
    private array $log = [];

    public function install(UploadedFile $zip): InstallResult
    {
        $this->log = [];

        $this->step('validate_zip', fn () => $this->validateZip($zip));
        $this->step('extract_zip', fn () => $this->extractZipToModules($zip));
        $this->step('run_migrations', fn () => $this->runMigrations());
        $this->step('publish_assets', fn () => $this->publishAssets());
        $this->step('seed_default_settings', fn () => $this->seedDefaultSettings());
        $this->step('register_module', fn () => $this->registerModule());
        $this->step('consolidate_media_folders', fn () => $this->consolidateMediaFolders());

        return new InstallResult(true, 'ShortDrama add-on installed successfully.', $this->log);
    }

    public function uninstall(): InstallResult
    {
        $this->log = [];

        $this->step('deregister_module', fn () => $this->deregisterModule());
        $this->step('rollback_migrations', fn () => $this->rollbackMigrations());
        $this->step('remove_module_dir', fn () => $this->removeModuleDirectory());

        return new InstallResult(true, 'ShortDrama add-on removed.', $this->log);
    }

    /**
     * Validate archive paths and ShortDrama layout without extracting. Exposed for tests and tooling.
     *
     * @throws RuntimeException
     */
    public function assertAddonZipSafeAtPath(string $absolutePath): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ext-zip is not available.');
        }

        $archive = new ZipArchive();

        if ($archive->open($absolutePath) !== true) {
            throw new RuntimeException('Cannot open the uploaded ZIP file.');
        }

        try {
            $this->assertArchiveSafeForInstall($archive);
        } finally {
            $archive->close();
        }
    }

    private function validateZip(UploadedFile $zip): void
    {
        $path = $zip->getRealPath();

        if ($path === false || $path === '') {
            throw new RuntimeException('Cannot resolve uploaded file path.');
        }

        $this->assertAddonZipSafeAtPath($path);
    }

    /**
     * Full safety + add-on layout checks for every ZIP entry (no file writes).
     *
     * @throws RuntimeException
     */
    private function assertArchiveSafeForInstall(ZipArchive $archive): void
    {
        $moduleJsonPath = self::MODULE_NAME.'/module.json';
        $hasModuleJson  = false;

        for ($i = 0; $i < $archive->numFiles; $i++) {
            $raw = $archive->getNameIndex($i);

            if (! is_string($raw)) {
                continue;
            }

            $normalized = InstallZipEntryPath::normalize($raw);

            if ($normalized === '') {
                continue;
            }

            InstallZipEntryPath::assertUnderModuleFolder($normalized, self::MODULE_NAME);

            if ($normalized === $moduleJsonPath) {
                $hasModuleJson = true;
            }
        }

        if (! $hasModuleJson) {
            throw new RuntimeException(
                'Invalid add-on package: '.self::MODULE_NAME.'/module.json not found in ZIP.'
            );
        }
    }

    private function extractZipToModules(UploadedFile $zip): void
    {
        $path = $zip->getRealPath();

        if ($path === false || $path === '') {
            throw new RuntimeException('Cannot resolve uploaded file path.');
        }

        $modulesBase = base_path(self::MODULES_PATH);
        $moduleRoot  = InstallZipEntryPath::joinUnderModulesBase($modulesBase, self::MODULE_NAME);

        $archive = new ZipArchive();

        if ($archive->open($path) !== true) {
            throw new RuntimeException('Failed to open ZIP for extraction.');
        }

        try {
            $this->assertArchiveSafeForInstall($archive);

            for ($i = 0; $i < $archive->numFiles; $i++) {
                $raw = $archive->getNameIndex($i);

                if (! is_string($raw)) {
                    continue;
                }

                $slashRaw = str_replace('\\', '/', $raw);
                $isDir    = str_ends_with($slashRaw, '/');

                $normalized = InstallZipEntryPath::normalize($raw);

                if ($normalized === '') {
                    continue;
                }

                $fullDest = InstallZipEntryPath::joinUnderModulesBase($modulesBase, $normalized);

                if (! InstallZipEntryPath::isPathInsideModuleRoot($fullDest, $moduleRoot)) {
                    throw new RuntimeException('ZIP extraction path left the module directory: '.$raw);
                }

                if ($isDir) {
                    File::ensureDirectoryExists($fullDest);

                    continue;
                }

                File::ensureDirectoryExists(dirname($fullDest));

                $contents = $archive->getFromIndex($i);

                if ($contents === false) {
                    throw new RuntimeException('Failed to read ZIP entry: '.$raw);
                }

                file_put_contents($fullDest, $contents);
            }
        } finally {
            $archive->close();
        }
    }

    private function runMigrations(): void
    {
        $absolute = base_path(self::MIGRATION_PATH);
        if (! File::isDirectory($absolute)) {
            throw new RuntimeException(
                'ShortDrama migration directory not found at '.$absolute.'. Fix MIGRATION_PATH or add-on layout.'
            );
        }

        $output = new BufferedOutput();

        $exitCode = Artisan::call('migrate', [
            '--path' => self::MIGRATION_PATH,
            '--force' => true,
        ], $output);

        $this->log['migrate_output'] = $output->fetch();

        if ($exitCode !== 0) {
            throw new RuntimeException('Migration failed. Check migrate_output in the install log.');
        }
    }

    private function publishAssets(): void
    {
        $distRoot = base_path(self::DIST_DIR);
        $destRoot = public_path('modules/shortdrama');

        foreach (self::PUBLIC_BUNDLES as $name) {
            $source = $distRoot.DIRECTORY_SEPARATOR.$name;
            if (! File::isFile($source)) {
                throw new RuntimeException(
                    'Invalid or incomplete ShortDrama add-on ZIP: required build file is missing ('.$name.'). '
                    .'Expected location: '.self::DIST_DIR.'. '
                    .'Please rebuild the add-on package and upload the new ZIP (php artisan short-drama:package).'
                );
            }
        }

        File::ensureDirectoryExists($destRoot);

        foreach (self::PUBLIC_BUNDLES as $name) {
            File::copy($distRoot.DIRECTORY_SEPARATOR.$name, $destRoot.DIRECTORY_SEPARATOR.$name);
        }

        (new ShortDramaMixManifest())->mergeShortDramaBundles();
    }

    private function seedDefaultSettings(): void
    {
        $defaults = [
            ['name' => 'short_drama_feature_name', 'val' => 'Short Drama', 'type' => 'string'],
            ['name' => 'short_drama_feature_icon', 'val' => '', 'type' => 'string'],
            ['name' => 'short_drama_is_active', 'val' => '1', 'type' => 'string'],
        ];

        foreach ($defaults as $row) {
            DB::table('settings')->insertOrIgnore([
                'name' => $row['name'],
                'val' => $row['val'],
                'type' => $row['type'],
                'datatype' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (class_exists(\Modules\Setting\Models\Setting::class)) {
            \Modules\Setting\Models\Setting::flushCache();
        }
    }

    private function consolidateMediaFolders(): void
    {
        if (! class_exists(\Modules\ShortDrama\Support\ShortDramaMediaStorage::class)) {
            return;
        }

        \Modules\ShortDrama\Support\ShortDramaMediaStorage::consolidateLegacyFolders();
    }

    private function registerModule(): void
    {
        $statusFile = base_path(self::STATUSES_FILE);
        $statuses = File::exists($statusFile)
            ? json_decode(File::get($statusFile), true) ?? []
            : [];

        $statuses[self::MODULE_NAME] = true;

        File::put(
            $statusFile,
            json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL
        );
    }

    private function deregisterModule(): void
    {
        $statusFile = base_path(self::STATUSES_FILE);
        if (! File::exists($statusFile)) {
            return;
        }

        $statuses = json_decode(File::get($statusFile), true) ?? [];
        unset($statuses[self::MODULE_NAME]);

        File::put(
            $statusFile,
            json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL
        );
    }

    private function rollbackMigrations(): void
    {
        $output = new BufferedOutput();
        Artisan::call('migrate:rollback', [
            '--path' => self::MIGRATION_PATH,
            '--force' => true,
        ], $output);
        $this->log['rollback_output'] = $output->fetch();
    }

    private function removeModuleDirectory(): void
    {
        $moduleDir = base_path(self::MODULES_PATH.'/'.self::MODULE_NAME);
        if (File::isDirectory($moduleDir)) {
            File::deleteDirectory($moduleDir);
        }
    }

    public function getLog(): array
    {
        return $this->log;
    }

    private function step(string $name, callable $fn): void
    {
        try {
            $fn();
            $this->log[$name] = ['status' => 'ok'];
        } catch (\Throwable $e) {
            $this->log[$name] = ['status' => 'error', 'message' => $e->getMessage()];
            Log::error("[ShortDramaPluginInstaller] Step '{$name}' failed: ".$e->getMessage());
            throw new RuntimeException("Installation failed at step [{$name}]: ".$e->getMessage(), 0, $e);
        }
    }
}
