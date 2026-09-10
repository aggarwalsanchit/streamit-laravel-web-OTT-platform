<?php
/**
 * server-entry.php
 * Fast-entry wrapper for Render.
 * Responds to health checks WITHOUT booting Laravel.
 */

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);

// 🔥 ONLY these paths get the instant health response
if (in_array($path, ['/health', '/up', '/healthz', '/debug-mix'], true)) {
    if ($path === '/debug-mix') {
        $manifest = public_path('mix-manifest.json');
        $js = public_path('js/backend-custom.js');
        header('Content-Type: application/json');
        echo json_encode([
            'manifest_exists' => file_exists($manifest),
            'manifest_contents' => file_exists($manifest) ? json_decode(file_get_contents($manifest), true) : null,
            'backend_custom_exists' => file_exists($js),
            'js_dir' => is_dir(public_path('js')) ? scandir(public_path('js')) : 'NO',
            'modules_dir' => is_dir(public_path('modules')) ? scandir(public_path('modules')) : 'NO',
            'public_path' => public_path(),
        ], JSON_PRETTY_PRINT);
        exit;
    }
}

// 🔧 Everything else goes to real Laravel
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');

require __DIR__ . '/public/index.php';
