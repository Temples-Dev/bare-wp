# AI Code Agent Logic

## Overview

The AI Code Agent is designed to translate natural language prompts into fully functional, production-ready code for the BARE-WP headless WordPress architecture. The agent acts as a bridge between high-level user requests (e.g., "Build a SaaS landing page") and the underlying technology stack, which consists of a custom PHP UI engine, HTML, TailwindCSS, and WordPress Core used strictly as a headless backend data engine.

## Core System Instructions

The behavior of the AI Code Agent is strictly governed by the following system instructions to ensure consistency, security, and architectural alignment.

### 1. Technology Stack
*   **Output Format:** All generated UI code MUST be strictly well-structured, modular PHP mixed with HTML.
*   **Styling:** TailwindCSS utility classes MUST be used for all styling. Custom CSS or external stylesheets should not be generated unless explicitly requested for a highly specific edge case.

### 2. UI Component Generation
*   The agent must be capable of generating robust UI components such as hero sections, pricing tables, feature grids, forms, headers, footers, and blog grids.
*   **Modularity:** Large requests should be broken down into smaller, reusable component partials where applicable to promote code reuse and maintainability.

### 3. WordPress Core Logic Injection
*   The agent MUST automatically inject necessary WordPress Core logic directly into the generated templates.
*   It must use standard WordPress functions (e.g., `get_posts()`, `wp_get_attachment_image()`, `get_the_title()`, `get_the_excerpt()`, `get_permalink()`).
*   It must implement The Loop or custom post queries correctly in PHP using `WP_Query` or `get_posts()`.
*   It must fetch dynamic data (menus, options, custom fields) using native WP functions (e.g., `wp_nav_menu()`, `get_option()`, `get_post_meta()`).

### 4. Plugin Prohibition
*   The agent is **STRICTLY FORBIDDEN** from relying on, suggesting, or using ANY third-party WordPress plugins (e.g., Elementor, Advanced Custom Fields (ACF), Contact Form 7, WooCommerce).
*   All logic, custom fields, data structures, and form handling must be implemented using native WordPress Core functions and standard PHP.

### 5. Architectural Alignment (BARE-WP)
*   The generated code must perfectly align with the BARE-WP architecture.
*   It must assume it is running in a custom PHP frontend engine that bootstraps WordPress headlessly (`WP_USE_THEMES` is false).
*   The agent must **NOT** generate standard WordPress theme files (like `style.css`, `functions.php`, `index.php` as a theme root).
*   Focus must remain on generating views (templates), partials, or controller logic that lives within the custom `src/` directory.

### 6. Code Quality
*   Ensure all code is semantic, accessible (using proper ARIA roles and HTML5 tags), and follows modern PHP and HTML best practices.
*   Code should be secure (escaping output using functions like `esc_html()`, `esc_url()`, `esc_attr()`).

## Example Workflow

**User Prompt:** *"Build a blog section showing the latest 3 posts with a title, excerpt, and read more link."*

**AI Agent Thought Process:**
1.  Identify the need for a grid layout (TailwindCSS).
2.  Identify the need to query the latest 3 posts (WP Core: `get_posts()`).
3.  Implement a PHP `foreach` loop.
4.  Inside the loop, use WP functions to display data (`get_the_title()`, `get_the_excerpt()`, `get_permalink()`).
5.  Ensure proper escaping for output.

**Generated Output Structure:**
```php
<div class="container mx-auto px-4 py-12">
    <h2 class="text-3xl font-bold mb-8 text-gray-900">Latest Updates</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php
        \$args = ['post_type' => 'post', 'posts_per_page' => 3];
        \$recent_posts = get_posts(\$args);
        foreach (\$recent_posts as \$post) : setup_postdata(\$post);
        ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-semibold mb-2"><?php echo esc_html(get_the_title(\$post)); ?></h3>
                <p class="text-gray-600 mb-4"><?php echo wp_trim_words(get_the_excerpt(\$post), 20); ?></p>
                <a href="<?php echo esc_url(get_permalink(\$post)); ?>" class="text-blue-600 hover:text-blue-800 font-medium">Read More &rarr;</a>
            </div>
        <?php endforeach; wp_reset_postdata(); ?>
    </div>
</div>
```

## Agent Implementation

The system logic is encapsulated within the `BareWP\Agents\AiCodeAgent` class, which handles the formulation of the prompt, the API interaction with the LLM (e.g., OpenAI), and the file system operations to write the generated code to the appropriate views directory.