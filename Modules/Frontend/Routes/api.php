<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Frontend\Http\Controllers\DashboardController;
use Modules\Frontend\Http\Controllers\PerviewPaymentController;
use Modules\Frontend\Http\Controllers\API\TransactionController;
use Modules\Frontend\Http\Controllers\TvShowController;
use Modules\Frontend\Http\Controllers\FrontendController;

/*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | is assigned the "api" middleware group. Enjoy building your API!
    |
*/



Route::get('pay-per-view-list', [PerviewPaymentController::class, 'PayPerViewList']);
Route::get('movies-pay-per-view', [DashboardController::class, 'moviePayperview']);
Route::get('videos-pay-per-view', [DashboardController::class, 'videosPayperview']);
Route::get('episodes-pay-per-view', [DashboardController::class, 'getEpisodesPayPerView']);


Route::get('ppv/movies', [PerviewPaymentController::class, 'moviePayPerViewList']);
Route::get('ppv/videos', [PerviewPaymentController::class, 'videoPayPerViewList']);
Route::get('ppv/episodes', [PerviewPaymentController::class, 'episodePayPerViewList']);

Route::get('content-list/{type}', [FrontendController::class, 'getContentListData'])->middleware('checkModule');


Route::get('web-continuewatch-list', [DashboardController::class, 'ContinuewatchList']);

Route::get('get-pinpopup/{id}', [DashboardController::class, 'getPinpopup']);

Route::get('v2/web-continuewatch-list', [DashboardController::class, 'ContinuewatchListV2']);
Route::get('v2/top-10-movie', [DashboardController::class, 'Top10MoviesV2']);

Route::post('save-payment-pay-per-view', [PerviewPaymentController::class, 'savePaymentPayperview']);
Route::post('start-date', [PerviewPaymentController::class, 'setStartDate']);
Route::get('/transaction-history', [TransactionController::class, 'transactionHistory'])->name('api.transaction-history');

Route::get('/check-episode-purchase', [TvShowController::class, 'checkEpisodePurchase'])->name('check.episode.purchase');
Route::get('/check-movie-purchase', [TvShowController::class, 'checkMoviePurchase'])->name('check.movie.purchase');







