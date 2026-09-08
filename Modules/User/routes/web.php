<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\Backend\UsersController;
use Modules\User\Http\Controllers\Backend\SoonToExpireUsersController;



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
Route::group(['prefix' => 'app', 'as' => 'backend.', 'middleware' => ['auth','admin']], function () {
    /*
    * These routes need view-backend permission
    * (good if you want to allow more than one group in the backend,
    * then limit the backend features by different roles or permissions)
    *
    * Note: Administrator has all permissions so you do not have to specify the administrator role everywhere.
    */

    /*
     *
     *  Backend Users Routes
     *
     * ---------------------------------------------------------------------
     */

     Route::get('app/users/{type}', [UsersController::class, 'index'])->name('users.soon-to-exipre');

    // Soon-to-Expire Users Routes
    Route::group(['prefix' => 'soon-to-expire-users', 'as' => 'soon-to-expire-users.'], function () {
        Route::get('/', [SoonToExpireUsersController::class, 'index'])->name('index');
        Route::get('index_data', [SoonToExpireUsersController::class, 'index_data'])->name('index_data');
        Route::get('export', [SoonToExpireUsersController::class, 'export'])->name('export');
        Route::post('send-email', [SoonToExpireUsersController::class, 'sendEmail'])->name('send-email');
    });

    Route::group(['prefix' => 'users', 'as' => 'users.'],function () {
      Route::get("index_list", [UsersController::class, 'index_list'])->name("index_list");
      Route::get("index_data", [UsersController::class, 'index_data'])->name("index_data");
      Route::get('export', [UsersController::class, 'export'])->name('export');
      Route::post('update-status/{id}', [UsersController::class, 'update_status'])->name('update_status');
      Route::post('bulk-action', [UsersController::class, 'bulk_action'])->name('bulk_action');
      Route::get('changepassword/{id}', [UsersController::class, 'changepassword'])->name('changepassword');
      Route::post('update-password/{id}', [UsersController::class, 'updatePassword'])->name('update_password');
      Route::get('details/{id}', [UsersController::class, 'details'])->name('details');
      Route::get('subscription-data/{id}', [UsersController::class, 'subscriptionData'])->name('subscription_data');
      Route::get('rent-data/{id}', [UsersController::class, 'rentData'])->name('rent_data');
      Route::post('import', [UsersController::class, 'import'])->name('import');
      Route::get('download-sample', [UsersController::class, 'downloadSample'])->name('download_sample');

    });
   Route::resource("users", UsersController::class);
    Route::post('send-email', [UsersController::class,'sendEmail'])->name('send.email');
});



