<?php

use Illuminate\Support\Facades\Route;

/*
| Ultra-fast health check for Render.
*/

Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'time' => now()->toIso8601String(),
    ]);
});

Route::head('/health', function () {
    return response()->noContent(200);
});
