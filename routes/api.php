<?php

use App\Http\Controllers\Auth\API\AuthController;
use App\Http\Controllers\Backend\API\DashboardController;
use App\Http\Controllers\Backend\API\NotificationsController;
use App\Http\Controllers\Backend\API\InvoiceController;

use App\Http\Controllers\Backend\API\SettingController as APISettingController;
use Modules\Frontend\Http\Controllers\PerviewPaymentController;
use Modules\Frontend\Http\Controllers\QueryOptimizeController;

use Modules\User\Http\Controllers\API\UserController;
use Modules\Entertainment\Http\Controllers\API\EntertainmentsController;
use Modules\LiveTV\Http\Controllers\API\LiveTVsController;
use App\Http\Controllers\TvAuthController;
use App\Http\Controllers\Backend\SettingController;
use App\Http\Controllers\Auth\WebQrLoginController;
use Modules\CastCrew\Http\Controllers\API\CastCrewController;
use Modules\Frontend\Http\Controllers\Auth\OTPController;
use App\Http\Controllers\API\ShortDramaConfigController;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Short Drama feature config (no auth) — core route; works without Modules/ShortDrama
Route::get('/short-drama-config', [ShortDramaConfigController::class, 'index']);


Route::get('user-detail', [AuthController::class, 'userDetails']);

Route::get('/optimize', [QueryOptimizeController::class, 'optimize'])->name('optimize');

Route::middleware(['auth:sanctum', 'checkApiDevice'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('social-login', 'socialLogin');
    Route::post('forgot-password', 'forgotPassword');
    Route::get('logout', 'logout');
    Route::get('csrf-token', 'getCsrfToken');
});

Route::post('check-mobile-exists', [OTPController::class, 'checkMobileExists'])->name('api.check.mobile.exists');
Route::post('/store-access-token', [SettingController::class, 'storeToken']);
Route::post('/token-revoke', [SettingController::class, 'revokeToken']);
Route::get('get-tranding-data', [DashboardController::class, 'getTrandingData']);

Route::get('v2/dashboard-detail-data', [DashboardController::class, 'DashboardDetailDataV2']);
Route::get('v2/dashboard-detail', [DashboardController::class, 'DashboardDetailV2']);
Route::get('v2/episode-details', [EntertainmentsController::class, 'episodeDetailsV2']);
Route::get('v2/livetv-dashboard', [LiveTVsController::class, 'liveTvDashboardV2']);
Route::get('v2/tvshow-details', [EntertainmentsController::class, 'tvshowDetailsV2']);
Route::get('v2/movie-details', [EntertainmentsController::class, 'movieDetailsV2']);

Route::get('v2/pay-per-view-list', [DashboardController::class, 'getPayPerViewUnlockedContent']);

// Episode / TV Show helpers
Route::get('tvshows/{tvshow}/plan-level', function ($tvshowId) {
    $tvshow = \Modules\Entertainment\Models\Entertainment::with('plan')->find($tvshowId);
    if (!$tvshow || !$tvshow->plan) {
        return response()->json(['level' => null]);
    }
    return response()->json(['level' => (int) $tvshow->plan->level]);
});

Route::middleware(['auth:sanctum', 'checkApiDevice', 'throttle:api'])->group(function () {
    Route::post('/web-qr-scan', [WebQrLoginController::class, 'scan'])->name('api.web-qr.scan');

    Route::apiResource('setting', SettingController::class);
    Route::apiResource('notification', NotificationsController::class);

    Route::get('notification-list', [NotificationsController::class, 'notificationList']);
    Route::get('notification-count', [NotificationsController::class, 'notificationCount']);


    Route::get('gallery-list', [DashboardController::class, 'globalGallery']);
    Route::get('search-list', [DashboardController::class, 'searchList']);
    Route::post('update-profile', [AuthController::class, 'updateProfile']);

    Route::post('change-password', [AuthController::class, 'changePassword']);
    Route::post('delete-account', [AuthController::class, 'deleteAccount']);

    Route::get('unlocked-content', [PerviewPaymentController::class, 'allUnlockVideos']);

    Route::get('download-invoice/{id}', [InvoiceController::class, 'download']);
    Route::get('pay-per-view-invoice/{id}', [InvoiceController::class, 'downloadPayPerViewInvoice']);



    ### v2 api`s

    Route::get('v2/profile-details', [UserController::class, 'profileDetailsV2']);

    Route::post('/change-pin', [AuthController::class, 'changePin'])->name('change-pin');

    Route::get('/send-otp', [AuthController::class, 'sendOtp'])->name('send-otp')->middleware('throttle:1,1'); // 1 request per minute
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('verify-otp')->middleware('throttle:5,1'); // 5 attempts per minute
    
    Route::post('/verify-pin', [AuthController::class, 'verifyPin'])->name('verify-pin');

    Route::post('/update-parental-lock', [AuthController::class, 'changeParentalLock'])->name('update-parental-lock');
    Route::post('/tv/confrim-session', [TvAuthController::class, 'confirmSession'])->name('confirmSession');

});

Route::prefix('v3')->middleware(['throttle:api'])->group(function () {
    Route::get('/payment-methods', [APISettingController::class, 'getPaymentMethods'])->name('payment.methods');
    Route::get('app-configuration', [APISettingController::class, 'appConfiguratonV3']);
    Route::get('content-details', [EntertainmentsController::class, 'contentDetailsV3']);
    Route::get('dashboard-detail', [DashboardController::class, 'DashboardDetailV3']);
    Route::get('dashboard-detail-data', [DashboardController::class, 'DashboardDetailDataV3']);
    Route::get('livetv-dashboard', [LiveTVsController::class, 'liveTvDashboardV3']);
    Route::get('pay-per-view-list', [DashboardController::class, 'getPayPerViewUnlockedContentV3']);
    Route::get('banner-data', [DashboardController::class, 'getEntertainmentDataV3']);
    Route::get('cast-details', [CastCrewController::class, 'castCrewDetailsV3'])->name('api.cast_crew_details_v3');

});
Route::prefix('v3')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('profile-details', [UserController::class, 'profileDetailsV3'])->name('api.v3.profile-details');
    Route::get('rented-content-list', [PerviewPaymentController::class, 'allUnlockVideosV3']);
    Route::post('delete-notification', [NotificationsController::class, 'deleteNotification']);

});


Route::get('app-configuration', [APISettingController::class, 'appConfiguraton']);

Route::prefix('tv')->group(function () {
    Route::get('/initiate-session', [TvAuthController::class, 'initiateSession']);
    Route::post('/check-session', [TvAuthController::class, 'checkSession']);
});


/*
| Short Drama add-on installer — registered in the main app so it works when
| the ShortDrama module is not yet installed or enabled.
*/
Route::prefix('admin/short-drama/installer')->middleware(['auth:sanctum', 'throttle:api', 'can:manage-short-drama-admin'])->group(function () {
    Route::get('status', [App\Http\Controllers\API\Admin\ShortDramaPluginInstallerController::class, 'status']);
    Route::post('install', [App\Http\Controllers\API\Admin\ShortDramaPluginInstallerController::class, 'install']);
    Route::delete('uninstall', [App\Http\Controllers\API\Admin\ShortDramaPluginInstallerController::class, 'uninstall']);
});
