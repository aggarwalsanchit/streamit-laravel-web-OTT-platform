<?php
use Illuminate\Support\Facades\Route;
use Modules\LiveTV\Http\Controllers\API\LiveTVsController;

Route::get('livetv-category-list', [LiveTVsController::class, 'liveTvCategoryList']);
Route::get('livetv-dashboard', [LiveTVsController::class, 'liveTvDashboard']);
Route::get('livetv-details', [LiveTVsController::class, 'liveTvDetails']);
Route::get('channel-list', [LiveTVsController::class, 'channelList']);

Route::group(['middleware' => 'auth:sanctum'], function () {

});

Route::prefix('v3')->middleware(['throttle:api'])->group(function () {
    Route::get('livetv-details', [LiveTVsController::class, 'liveTvDetailsV3']);
    Route::get('channel-list', [LiveTVsController::class, 'channelListV3']);
});
?>


