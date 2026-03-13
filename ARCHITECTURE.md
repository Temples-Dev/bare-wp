# BARE-WP Architectural Blueprint

## Overview

**BARE-WP** is an agentic platform that leverages WordPress Core strictly as a headless backend data engine (for posts, users, media, authentication, taxonomy, etc.) while completely bypassing the traditional WordPress UI, themes, and plugin ecosystems.

This architecture allows us to use WordPress's robust data management and admin panel without being constrained by its conventional frontend rendering lifecycle. Instead, a custom PHP-based UI development engine sits on top of WordPress Core.

## System Architecture

The system is decoupled into two primary layers:

1. **Backend Engine (WordPress Core):**
   - Installed in a dedicated, isolated directory (`/public/wp-core/`).
   - Handles the administrative interface (`/wp-admin`), user authentication, content creation, and media management.
   - Provides a rich set of data access APIs (PHP functions, hooks, WP_Query, REST API).
   - Bypasses the traditional frontend rendering (no active "theme" is loaded by our custom frontend).

2. **Custom PHP UI Engine:**
   - Lives outside the web document root to enhance security (`/src/`).
   - Handles all public-facing routing, templating, and logic.
   - Bootstraps WordPress in "short" mode (bypassing the theme layer) to gain direct access to WP's internal functions, or communicates via the WP REST API depending on the module.
   - Uses a modern MVC (Model-View-Controller) or similar architectural pattern.

## File Structure

To ensure that WordPress Core updates remain seamless and core files are never tampered with, the file structure separates the custom application logic from the WordPress installation.

```text
BARE-WP/
├── config/                 # Application configuration (DB credentials, environment variables)
│   └── wp-config.php       # Custom wp-config.php located outside the WP core folder
├── public/                 # The web document root
│   ├── index.php           # Custom frontend entry point (bypasses WP themes)
│   └── wp-core/            # Unmodified WordPress Core files (updated via WP CLI or dashboard)
│       ├── wp-admin/
│       ├── wp-includes/
│       └── wp-content/     # Contains MU-plugins or headless-specific plugins if absolutely necessary
├── src/                    # Custom PHP UI Development Engine
│   ├── Controllers/        # Request handlers
│   ├── Models/             # Data wrappers interacting with WP Core
│   └── Views/              # Templates (e.g., Twig, Blade, or pure PHP)
├── storage/                # Caching, logs, and custom uploads (if decoupled from WP)
├── vendor/                 # Composer dependencies
└── composer.json           # Autoloader and dependencies for the custom PHP engine
```

### Protection of Core Files
- WordPress is kept entirely within `public/wp-core/`. We do not edit any file inside this directory.
- `wp-config.php` is moved outside of the `public/wp-core/` directory (typically one level up, or in `config/`) so updates to the core folder don't affect configuration.
- The `index.php` in `public/` is our custom entry point, *not* the default WordPress index.php.

## Interfacing with the Backend

The custom PHP frontend interfaces with the WordPress backend through two primary methods:

### 1. Internal PHP Bootstrapping (Direct Function Access)

For optimal performance when the frontend and backend run on the same server, we bootstrap WordPress directly within our custom `public/index.php` without loading the theme engine.

```php
// Define WP_USE_THEMES to false to skip the theme template loader
define('WP_USE_THEMES', false);

// Bootstrap WordPress Core
require __DIR__ . '/wp-core/wp-blog-header.php';

// Now, all WP functions are available to our custom routing and controllers!
// e.g., get_posts(), get_user_by(), wp_insert_post()
```

**Benefits:**
- Ultra-low latency compared to HTTP requests.
- Access to the entire WordPress internal PHP API, including custom post types, taxonomy functions, and user capability checks.
- Easy to wrap WP data objects inside our custom Models (`src/Models/`).

### 2. The WordPress REST API

For heavily decoupled agents, microservices, or client-side interactions (like AJAX or single-page applications), we use the WP REST API.

**Benefits:**
- Allows entirely separate services (or frontend Javascript) to interact with the backend securely.
- Standardized JSON responses for posts, users, and custom endpoints.
- Authentication can be handled via Application Passwords or JWT.

### Handling Hooks (Actions and Filters)

Since we are bypassing the theme, traditional theme hooks (like `wp_head` or `wp_footer`) are obsolete for the custom UI. However, internal data hooks (e.g., `save_post`, `user_register`) remain fully functional.

If we need to modify WP Core behavior, we can inject logic via a Must-Use Plugin (`wp-content/mu-plugins/`) which runs early in the WordPress lifecycle, ensuring our headless settings (e.g., disabling default frontend feeds, redirecting theme requests) are strictly enforced.
