<?php
use Illuminate\Support\Facades\Route;
use Modules\Subscriptions\Http\Controllers\Backend\API\PlanController;
use Modules\Subscriptions\Http\Controllers\Backend\API\PlanLimitationController;
use Modules\Subscriptions\Http\Controllers\Backend\API\SubscriptionController;

Route::apiResource('planlimitation', PlanLimitationController::class);
Route::apiResource('plans', PlanController::class);

Route::get('plan-list', [PlanController::class, 'planList']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/save-subscription-details', [SubscriptionController::class, 'saveSubscriptionDetails']);
    Route::get('/user-subscription_histroy', [SubscriptionController::class, 'getUserSubscriptionHistroy'])->name('api.user-subscription_histroy');
    Route::post('/cancle-subscription', [SubscriptionController::class, 'cancelSubscription']);
});


