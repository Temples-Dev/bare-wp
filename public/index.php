<?php

/**
 * BARE-WP Custom Frontend Entry Point
 *
 * This file replaces the default WordPress index.php. It bootstraps WordPress
 * strictly as a headless backend data engine, bypassing the theme layer entirely.
 */

// 1. Tell WordPress NOT to load the theme template engine
define('WP_USE_THEMES', false);

// 2. Define custom content directory if needed, to point away from the WP core
// define('WP_CONTENT_DIR', dirname(__DIR__) . '/public/content');
// define('WP_CONTENT_URL', WP_HOME . '/content'); // Use WP_HOME or a trusted constant to prevent host header injection

// 3. Load Composer Autoloader for the custom PHP UI engine
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

// 4. Bootstrap WordPress Core (located in the isolated /wp-core/ directory)
$wp_bootstrap_path = __DIR__ . '/wp-core/wp-blog-header.php';
if (file_exists($wp_bootstrap_path)) {
    require $wp_bootstrap_path;
} else {
    // Graceful fallback if WP is not yet installed or mapped
    error_log('WordPress Core bootstrap file not found at ' . $wp_bootstrap_path);
    http_response_code(503);
    die('Service Unavailable.');
}

// 5. Initialize the Custom PHP UI Application
// Set up the custom routing engine.
$router = new \BareWP\Router();

// Register the preview endpoints
$router->get('/preview', [\BareWP\Controllers\LivePreviewController::class, 'index']);
$router->post('/preview/render', [\BareWP\Controllers\LivePreviewController::class, 'render']);

// Template Management API
$router->get('/api/templates', [\BareWP\Controllers\TemplateController::class, 'list']);
$router->post('/api/templates/save', [\BareWP\Controllers\TemplateController::class, 'save']);

// A basic home route for testing
$router->get('/', function() {
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><title>BARE-WP</title><link rel='stylesheet' href='/assets/css/main.css'></head><body class='bg-gray-100 p-8'>";
    echo "<h1 class='text-3xl font-bold mb-4'>BARE-WP Frontend Engine</h1>";
    echo "<p class='mb-2'>WordPress Core is loaded headlessly. Themes are bypassed.</p>";
    if (function_exists('get_bloginfo')) {
        echo "<p>Connected to backend: <strong>" . esc_html(get_bloginfo('name')) . "</strong></p>";
    }
    echo "<p><a href='/preview'>Go to Live Preview Sandbox</a></p>";
});

// Proceed to route the incoming HTTP request...
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($requestUri, $requestMethod);
