<?php

use Illuminate\Support\Facades\Route;
use Modules\SEO\Http\Controllers\SEOController;

Route::group(['prefix' => 'app', 'middleware' => ['auth', 'admin']], function () {
    Route::get('setting/seo-settings', [SEOController::class, 'index'])->name('Seo.seo-settings');
    Route::post('seo/store', [SEOController::class, 'store'])->name('seo.store');
    Route::get('seo/edit/{id}', [SEOController::class, 'edit'])->name('seo.edit');
    Route::post('seo/update/{id}', [SEOController::class, 'update'])->name('seo.update');
    Route::delete('seo/{id}', [SEOController::class, 'destroy'])->name('seo.destroy');
});