<?php

use Illuminate\Support\Facades\Route;
use Modules\Onboarding\Http\Controllers\Backend\OnboardingsController;



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
/*
*
* Backend Routes
*
* --------------------------------------------------------------------
*/
Route::group(['prefix' => 'app', 'as' => 'backend.', 'middleware' => ['auth']], function () {
    /*
    * These routes need view-backend permission
    * (good if you want to allow more than one group in the backend,
    * then limit the backend features by different roles or permissions)
    *
    * Note: Administrator has all permissions so you do not have to specify the administrator role everywhere.
    */

    /*
     *
     *  Backend Onboardings Routes
     *
     * ---------------------------------------------------------------------
     */

    Route::group(['prefix' => 'onboardings', 'as' => 'onboardings.'],function () {
      Route::get("index_list", [OnboardingsController::class, 'index_list'])->name("index_list");
      Route::get("index_data", [OnboardingsController::class, 'index_data'])->name("index_data");
      Route::get('export', [OnboardingsController::class, 'export'])->name('export');
      Route::post('bulk-action', [OnboardingsController::class, 'bulk_action'])->name('bulk_action');
      Route::post('restore/{id}', [OnboardingsController::class, 'restore'])->name('restore');
      Route::delete('force-delete/{id}', [OnboardingsController::class, 'forceDelete'])->name('force_delete');
      Route::post('update-status/{id}', [OnboardingsController::class, 'update_status'])->name('update_status');
    });
    Route::resource("onboardings", OnboardingsController::class);
});



