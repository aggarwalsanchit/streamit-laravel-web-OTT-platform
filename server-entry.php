<?php
/**
 * server-entry.php
 * Fast-entry wrapper for Render.
 * Serves static files directly; boots Laravel for everything else.
 */

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);
$publicPath = __DIR__ . '/public';

// 🔥 ULTRA-FAST HEALTH CHECK (no Laravel boot!)
if (in_array($path, ['/health', '/up', '/healthz'], true)) {
    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode(['status' => 'healthy', 'time' => date('c')]);
    exit;
}

// 🔧 SERVE STATIC FILES DIRECTLY (CSS, JS, images, fonts, etc.)
$filePath = realpath($publicPath . $path);
if ($filePath && str_starts_with($filePath, realpath($publicPath)) && is_file($filePath)) {
    // Determine MIME type
    $mimeTypes = [
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'json'  => 'application/json',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'eot'   => 'application/vnd.ms-fontobject',
        'map'   => 'application/json',
        'txt'   => 'text/plain',
        'xml'   => 'application/xml',
        'webp'  => 'image/webp',
    ];
    
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
    
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: public, max-age=86400');
    readfile($filePath);
    exit;
}

// 🔧 Normal Laravel boot for everything else
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');

require __DIR__ . '/public/index.php';
