<?php

use Illuminate\Support\Facades\Route;
use Modules\Video\Http\Controllers\API\VideosController;

Route::get('video-list', [VideosController::class, 'videoList']);
Route::get('video-details', [VideosController::class, 'videoDetails']);
Route::prefix('v3')->group(function () {
Route::get('video-list', [VideosController::class, 'videoListV3']);

});


