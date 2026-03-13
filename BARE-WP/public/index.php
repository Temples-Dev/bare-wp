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
// define('WP_CONTENT_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/content');

// 3. Load Composer Autoloader for the custom PHP UI engine
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

// 4. Bootstrap WordPress Core (located in the isolated /wp/ directory)
$wp_bootstrap_path = __DIR__ . '/wp/wp-blog-header.php';
if (file_exists($wp_bootstrap_path)) {
    require $wp_bootstrap_path;
} else {
    // Graceful fallback if WP is not yet installed or mapped
    die('WordPress Core not found. Please ensure it is installed in public/wp/.');
}

// 5. Initialize the Custom PHP UI Application
// Here you would instantiate your Router, Controllers, and Views.
// For demonstration, we simply output a confirmation that WP is loaded.
echo "<h1>BARE-WP Initialization</h1>";
echo "<p>WordPress Core is loaded headlessly. Themes are bypassed.</p>";

// Example of directly accessing the WP internal API:
if (function_exists('get_bloginfo')) {
    echo "<p>Connected to backend: <strong>" . esc_html(get_bloginfo('name')) . "</strong></p>";
} else {
    echo "<p>Failed to load WP functions.</p>";
}

// Proceed to route the incoming HTTP request...
// \BareWP\Router::dispatch($_SERVER['REQUEST_URI']);
