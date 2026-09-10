<?php
/**
 * server-entry.php
 * Fast-entry wrapper for Render.
 * Responds to health checks WITHOUT booting Laravel.
 */

// Get the request path
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);

// 🔥 ULTRA-FAST HEALTH CHECK (no Laravel boot!)
// Responds with a 200 OK to anything hitting these paths.
if (in_array($path, ['/', '/health', '/up', '/healthz'])) {
    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode(['status' => 'healthy', 'time' => date('c')]);
    exit;
}

// 🔧 Normal Laravel boot for everything else (real users)
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');

require __DIR__ . '/public/index.php';
