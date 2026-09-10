<?php

use Illuminate\Support\Facades\Route;

Route::match(['get', 'head'], '/health', function () {
    return response()->json([
        'status' => 'healthy',
        'time' => now()->toIso8601String(),
    ]);
});

Route::match(['get', 'head'], '/up', function () {
    return response()->json([
        'status' => 'healthy',
        'time' => now()->toIso8601String(),
    ]);
});
