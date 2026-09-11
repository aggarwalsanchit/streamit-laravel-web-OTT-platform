<?php
/**
 * server-entry.php
 * Fast-entry wrapper for Render.
 * Serves static files directly (including symlinked storage); boots Laravel otherwise.
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

// 🔧 SERVE STATIC FILES DIRECTLY
$mimeTypes = [
    'css'   => 'text/css',
    'js'    => 'application/javascript',
    'mjs'   => 'application/javascript',
    'json'  => 'application/json',
    'png'   => 'image/png',
    'jpg'   => 'image/jpeg',
    'jpeg'  => 'image/jpeg',
    'gif'   => 'image/gif',
    'svg'   => 'image/svg+xml',
    'ico'   => 'image/x-icon',
    'bmp'   => 'image/bmp',
    'webp'  => 'image/webp',
    'woff'  => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf'   => 'font/ttf',
    'otf'   => 'font/otf',
    'eot'   => 'application/vnd.ms-fontobject',
    'map'   => 'application/json',
    'txt'   => 'text/plain',
    'xml'   => 'application/xml',
    'pdf'   => 'application/pdf',
    'mp4'   => 'video/mp4',
    'webm'  => 'video/webm',
    'mp3'   => 'audio/mpeg',
];

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

// Only handle requests with a known static-file extension
if (isset($mimeTypes[$ext])) {
    // Build the candidate paths:
    // 1. Direct file inside public/
    // 2. Symlink resolution (public/storage/... → storage/app/public/...)
    $candidates = [
        $publicPath . $path,                        // public/movie/...
        $publicPath . '/storage' . preg_replace('#^/storage#', '', $path), // public/storage/...
    ];

    foreach ($candidates as $candidate) {
        // realpath() resolves symlinks — this is what we want
        $real = realpath($candidate);

        if ($real && is_file($real) && is_readable($real)) {
            // 🔒 Security: only serve files under the project root
            $projectRoot = realpath(__DIR__);
            if ($projectRoot && str_starts_with($real, $projectRoot)) {
                header('Content-Type: ' . $mimeTypes[$ext]);
                header('Content-Length: ' . filesize($real));
                header('Cache-Control: public, max-age=86400');
                header('X-Served-By: static-handler');
                readfile($real);
                exit;
            }
        }
    }
    // If extension is static but file not found, still fall through to Laravel
    // so that Laravel's own 404 handler or route can respond
}

// 🔧 Normal Laravel boot for everything else
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');

require __DIR__ . '/public/index.php';
