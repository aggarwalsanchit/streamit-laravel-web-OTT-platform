<?php
// server-entry.php - Safe debug entry point for Render

// Basic output buffering so we can print errors even if headers are sent
ob_start();

echo "<!DOCTYPE html><html><head><title>Debug Boot</title></head><body>";
echo "<h1>Debug Mode: Booting Laravel</h1>";

// 1. Check for autoloader
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die("<h2 style='color:red;'>FATAL: vendor/autoload.php not found. Composer install failed.</h2>");
}
require $autoloadPath;
echo "<p>✅ Autoloader loaded.</p>";

// 2. Load environment
$appPath = __DIR__ . '/bootstrap/app.php';
if (!file_exists($appPath)) {
    die("<h2 style='color:red;'>FATAL: bootstrap/app.php not found.</h2>");
}

try {
    echo "<p>Booting application...</p>";
    $app = require_once $appPath;
    echo "<p>✅ Application instance created.</p>";

    // 3. Create Kernel
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "<p>✅ HTTP Kernel resolved.</p>";

    // 4. Capture request
    $request = Illuminate\Http\Request::capture();
    echo "<p>✅ Request captured for URL: " . htmlspecialchars($request->fullUrl()) . "</p>";

    // 5. Handle request
    echo "<p>Handling request...</p>";
    $response = $kernel->handle($request);

    // 6. Send response (clear debug output first)
    ob_end_clean();
    $response->send();

    // 7. Terminate
    $kernel->terminate($request, $response);

} catch (Throwable $e) {
    // Clear any partial output
    ob_end_clean();
    header('Content-Type: text/html; charset=utf-8', true, 500);

    echo "<!DOCTYPE html><html><head><title>Fatal Error</title></head><body>";
    echo "<h1 style='color:red;'>FATAL ERROR CAUGHT</h1>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . " on line " . $e->getLine() . "</p>";
    echo "<h2>Stack Trace:</h2><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</body></html>";
    exit;
}
