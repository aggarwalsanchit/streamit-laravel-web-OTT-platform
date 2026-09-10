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

Route::get('/debug-mix', function () {
    $manifestPath = public_path('mix-manifest.json');
    $jsPath = public_path('js/backend-custom.js');
    
    return response()->json([
        'manifest_exists' => file_exists($manifestPath),
        'manifest_contents' => file_exists($manifestPath) 
            ? json_decode(file_get_contents($manifestPath), true) 
            : 'NOT FOUND',
        'backend_custom_exists' => file_exists($jsPath),
        'public_js_dir' => file_exists(public_path('js')) 
            ? scandir(public_path('js')) 
            : 'NO JS DIR',
        'public_modules_dir' => file_exists(public_path('modules')) 
            ? scandir(public_path('modules')) 
            : 'NO MODULES DIR',
        'base_path' => base_path(),
        'public_path' => public_path(),
    ]);
});
