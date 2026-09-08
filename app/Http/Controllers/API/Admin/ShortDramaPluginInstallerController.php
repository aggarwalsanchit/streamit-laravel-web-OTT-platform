<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShortDramaPluginInstallerRequest;
use App\Http\Responses\ApiResponse;
use App\Services\ShortDrama\ShortDramaPluginInstaller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ShortDramaPluginInstallerController extends Controller
{
    public function __construct(
        private readonly ShortDramaPluginInstaller $installer,
    ) {}

    public function status(): JsonResponse
    {
        $this->authorize('manage-short-drama-admin');

        $modulesFile = base_path('modules_statuses.json');
        $statuses = file_exists($modulesFile)
            ? json_decode(file_get_contents($modulesFile), true) ?? []
            : [];

        $installed = isset($statuses['ShortDrama']) && $statuses['ShortDrama'];
        $moduleDir = base_path('Modules/ShortDrama');
        $hasFiles = is_dir($moduleDir) && file_exists($moduleDir.'/module.json');

        return ApiResponse::success([
            'installed' => $installed && $hasFiles,
            'registered' => $installed,
            'module_dir_exists' => $hasFiles,
        ], 'Installer status');
    }

    public function install(ShortDramaPluginInstallerRequest $request): JsonResponse
    {
        $this->authorize('manage-short-drama-admin');

        try {
            $result = $this->installer->install($request->file('addon_zip'));
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422, $this->installer->getLog());
        }

        return ApiResponse::success($result->toArray(), $result->message, 200);
    }

    public function uninstall(Request $request): JsonResponse
    {
        $this->authorize('manage-short-drama-admin');

        if ($request->input('confirm') !== 'REMOVE_SHORT_DRAMA') {
            return ApiResponse::error(
                'Pass confirm=REMOVE_SHORT_DRAMA in the request body to proceed.',
                422
            );
        }

        try {
            $result = $this->installer->uninstall();
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }

        return ApiResponse::success($result->toArray(), $result->message);
    }
}
