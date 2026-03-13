<?php

namespace BareWP\Controllers;

use BareWP\Deployment\Deployer;

class TemplateController
{
    private string $templateDir;

    public function __construct()
    {
        $this->templateDir = dirname(__DIR__) . '/Views/Templates';
        if (!is_dir($this->templateDir)) {
            mkdir($this->templateDir, 0755, true);
        }
    }

    /**
     * List all saved templates.
     */
    public function list(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $files = glob($this->templateDir . '/*.{html,php}', GLOB_BRACE);
        $templates = array_map(function($file) {
            return [
                'name' => basename($file),
                'path' => $file,
                'content' => file_get_contents($file)
            ];
        }, $files);

        wp_send_json_success($templates);
    }

    /**
     * Save a template to the environment.
     */
    public function save(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $nonce = $_POST['_csrf'] ?? '';
        if (!wp_verify_nonce($nonce, 'live_preview_render')) {
            wp_send_json_error('Invalid CSRF token', 403);
        }

        $name = $_POST['name'] ?? '';
        $content = $_POST['code'] ?? '';

        if (empty($name) || empty($content)) {
            wp_send_json_error('Missing name or content');
        }

        // Sanitize name: remove directory traversal attempts and unwanted characters
        $name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $name);
        if (!str_ends_with($name, '.html') && !str_ends_with($name, '.php')) {
            $name .= '.html';
        }

        $filePath = $this->templateDir . '/' . $name;

        if (file_put_contents($filePath, $content, LOCK_EX)) {
            wp_send_json_success(['name' => $name, 'message' => 'Template saved successfully']);
        } else {
            wp_send_json_error('Failed to save template');
        }
    }

    /**
     * Deploy a template to a live route.
     */
    public function deploy(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $nonce = $_POST['_csrf'] ?? '';
        if (!wp_verify_nonce($nonce, 'live_preview_render')) {
            wp_send_json_error('Invalid CSRF token', 403);
        }

        $route = $_POST['route'] ?? '';
        $templateName = $_POST['name'] ?? '';
        $code = $_POST['code'] ?? '';

        if (empty($route) || empty($templateName) || empty($code)) {
            wp_send_json_error('Missing required fields for deployment');
        }

        $deployer = new Deployer();
        $result = $deployer->deploy($route, $templateName, $code);

        if ($result[0]) {
            wp_send_json_success(['message' => $result[1]]);
        } else {
            wp_send_json_error($result[1]);
        }
    }
}
