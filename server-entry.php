<?php
/**
 * server-entry.php
 * Fast-entry wrapper for Render.
 * Responds to health checks WITHOUT booting Laravel.
 */

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);

// 🔥 ONLY these paths get the instant health response
if (in_array($path, ['/health', '/up', '/healthz'], true)) {
    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode(['status' => 'healthy', 'time' => date('c')]);
    exit;
}

// 🔧 Everything else goes to real Laravel
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');

require __DIR__ . '/public/index.php';
