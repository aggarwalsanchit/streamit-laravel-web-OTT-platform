<?php

use Illuminate\Support\Facades\Route;
use Modules\Onboarding\Http\Controllers\API\OnboardingsController;

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


Route::get('onboarding-data-list', [OnboardingsController::class, 'onboardingDataList']);
