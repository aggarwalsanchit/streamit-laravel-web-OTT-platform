<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\ShortDrama\ShortDramaAddonStatus;
use Illuminate\Http\JsonResponse;
use Modules\Setting\Models\Setting;

class ShortDramaConfigController extends Controller
{
    private const KEY_NAME = 'short_drama_feature_name';

    private const KEY_ICON = 'short_drama_feature_icon';

    private const KEY_ACTIVE = 'short_drama_is_active';

    /**
     * Public feature flags for the mobile app. Lives in the main app so it works when
     * Modules/ShortDrama is not installed; {@see ShortDramaAddonStatus::isPresent()}
     * drives {@code data.is_active} together with admin feature settings.
     *
     * GET /api/short-drama-config
     */
    public function index(): JsonResponse
    {
        if (! ShortDramaAddonStatus::isPresent()) {
            return ApiResponse::success([
                'feature_name' => 'Shorts',
                'feature_icon' => null,
                'is_active'    => false,
            ], 'Short Drama config loaded');
        }

        $iconFile = Setting::get(self::KEY_ICON);

        return ApiResponse::success([
            'feature_name' => Setting::get(self::KEY_NAME, 'Short Drama'),
            'feature_icon' => $iconFile
                ? setBaseUrlWithFileName($iconFile, 'image', 'short-drama')
                : null,
            'is_active' => (bool) Setting::get(self::KEY_ACTIVE, '1'),
        ], 'Short Drama config loaded');
    }
}
