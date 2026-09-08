<?php

use Illuminate\Support\Facades\Route;
use Modules\Entertainment\Http\Controllers\API\EntertainmentsController;
use Modules\Entertainment\Http\Controllers\API\WatchlistController;
use Modules\Entertainment\Http\Controllers\API\ReviewController;
use Modules\Frontend\Http\Controllers\FrontendController;



Route::prefix('v3')->group(function () {
Route::get('movie-list', [EntertainmentsController::class, 'movieListV3']);
Route::get('tvshow-list', [EntertainmentsController::class, 'tvshowListV3']);
Route::get('get-search-data', [FrontendController::class, 'getSearchV3']);
});


Route::get('get-rating', [ReviewController::class, 'getRating']);
Route::get('genre-content-list', [EntertainmentsController::class, 'genreContentList']);
Route::get('movie-details', [EntertainmentsController::class, 'movieDetails']);
Route::get('tvshow-list', [EntertainmentsController::class, 'tvshowList']);
Route::get('v2/tvshow-list', [EntertainmentsController::class, 'tvshowListV2']);
Route::get('tvshow-details', [EntertainmentsController::class, 'tvshowDetails']);
Route::get('episode-list', [EntertainmentsController::class, 'episodeList']);
Route::get('episode-details', [EntertainmentsController::class, 'episodeDetails']);
Route::get('search-list', [EntertainmentsController::class, 'searchList']);
// Route::get('get-search', [EntertainmentsController::class, 'getSearch']);
Route::get('coming-soon', [EntertainmentsController::class, 'comingSoon']);



Route::group(['middleware' => ['auth:sanctum', 'checkApiDevice']], function () {

    Route::get('watch-list', [WatchlistController::class, 'watchList']);
    Route::post('save-watchlist', [WatchlistController::class, 'saveWatchList']);
    Route::post('delete-watchlist', [WatchlistController::class, 'deleteWatchList']);
    Route::delete('watch-list/{entertainment_id}', [WatchlistController::class, 'removeWatchList']);

    Route::post('save-rating', [ReviewController::class, 'saveRating'])->name('save-rating');
    Route::post('delete-rating', [ReviewController::class, 'deleteRating'])->name('delete-rating');
    Route::put('update-rating', [ReviewController::class, 'update'])->name('update-rating');

    Route::post('save-likes', [ReviewController::class, 'saveLikes']);
    Route::post('save-download', [EntertainmentsController::class, 'saveDownload']);
    Route::post('delete-download', [EntertainmentsController::class, 'deleteDownload']);


    Route::get('continuewatch-list', [WatchlistController::class, 'continuewatchList']);
    Route::post('save-continuewatch', [WatchlistController::class, 'saveContinueWatch']);
    Route::post('delete-continuewatch', [WatchlistController::class, 'deleteContinueWatch']);

    Route::post('save-reminder', [EntertainmentsController::class, 'saveReminder']);
    Route::post('delete-reminder', [EntertainmentsController::class, 'deleteReminder']);

    Route::post('save-entertainment-views', [EntertainmentsController::class, 'saveEntertainmentViews']);
});

Route::prefix('v3')->middleware(['throttle:api'])->group(function () {
    Route::get('content-list', [EntertainmentsController::class, 'contentListV3']);
    Route::get('episode-list', [EntertainmentsController::class, 'episodeListV3']);
    Route::get('get-search', [EntertainmentsController::class, 'getSearchV3']);

});
Route::group(['prefix'=>'v3','middleware' => ['auth:sanctum', 'checkApiDevice']], function () {
    Route::get('continuewatch-list', [WatchlistController::class, 'continuewatchListV3']);
    Route::get('watch-list', [WatchlistController::class, 'watchListV3']);

});
