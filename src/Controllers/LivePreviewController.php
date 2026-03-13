<?php

namespace BareWP\Controllers;

use BareWP\RenderingEngine;
use Exception;

class LivePreviewController
{
    /**
     * Shows the live preview sandbox UI with an editor and an iframe.
     */
    public function index(): void
    {
        // Security checks: Environment and Authentication
        if (!in_array(getenv('APP_ENV'), ['local', 'dev'])) {
            http_response_code(403);
            die('Access Denied: Sandbox only available in local/dev environments.');
        }

        if (!current_user_can('manage_options')) {
            http_response_code(403);
            die('Access Denied: Administrator privileges required.');
        }

        // Serve a simple HTML page with an editor and iframe
        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BARE-WP Live Preview Sandbox</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col md:flex-row p-4 gap-4">
    <div class="flex-1 flex flex-col bg-white p-4 rounded shadow">
        <h2 class="text-xl font-bold mb-2">Code Editor (HTML/Tailwind)</h2>
        <form id="preview-form" target="preview-iframe" method="POST" action="/preview/render" class="flex-1 flex flex-col">
            <textarea name="code" id="code-editor" class="flex-1 p-2 border rounded font-mono text-sm resize-none mb-4" placeholder="Enter your HTML/Tailwind code here...">
&lt;div class="p-8 bg-blue-500 text-white rounded shadow-lg"&gt;
    &lt;h1 class="text-2xl font-bold"&gt;Hello from BARE-WP Sandbox!&lt;/h1&gt;
    &lt;p class="mt-4"&gt;Dynamic UI rendering works.&lt;/p&gt;
&lt;/div&gt;</textarea>
            <input type="hidden" name="_csrf" value="<?php echo esc_attr(wp_create_nonce('live_preview_render')); ?>">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Render Preview
            </button>
        </form>
    </div>

    <div class="flex-1 flex flex-col bg-white p-4 rounded shadow">
        <h2 class="text-xl font-bold mb-2">Live Preview</h2>
        <div class="flex-1 border rounded relative">
            <iframe name="preview-iframe" id="preview-iframe" class="absolute inset-0 w-full h-full border-0"></iframe>
        </div>
    </div>

    <script>
        // Optional: Auto-submit form when code changes (debounced)
        const form = document.getElementById('preview-form');
        const editor = document.getElementById('code-editor');
        let timeout = null;

        editor.addEventListener('input', () => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                form.submit();
            }, 1000); // 1-second debounce
        });

        // Initial render
        window.onload = () => form.submit();
    </script>
</body>
</html>
HTML;
    }

    /**
     * Renders the submitted code inside an isolated environment (the iframe source).
     */
    public function render(): void
    {
        // Security checks: Environment and Authentication
        if (!in_array(getenv('APP_ENV'), ['local', 'dev'])) {
            http_response_code(403);
            die('Access Denied');
        }

        if (!current_user_can('manage_options')) {
            http_response_code(403);
            die('Access Denied');
        }

        $nonce = $_POST['_csrf'] ?? '';
        if (!wp_verify_nonce($nonce, 'live_preview_render')) {
            http_response_code(403);
            echo "<h1>403 Forbidden</h1>";
            echo "<p>Invalid CSRF token.</p>";
            return;
        }

        $code = $_POST['code'] ?? '';
        // Mitigate RCE: Strip PHP tags to prevent server-side execution
        $code = preg_replace('/<\?php.*?\?>/is', '', $code);
        $code = str_ireplace(['<?php', '<?', '?>'], '', $code);

        if (empty(trim($code))) {
            echo "<p style='color:red;'>No code provided to render.</p>";
            return;
        }

        // Setup storage directory for temporary preview files
        $previewDir = dirname(__DIR__, 2) . '/storage/preview';
        if (!is_dir($previewDir)) {
            mkdir($previewDir, 0755, true);
        }

        // Create a temporary file to hold the code
        // Use .html extension to further discourage execution
        $tempFile = $previewDir . '/prev_' . bin2hex(random_bytes(16)) . '.html';

        // Strip out any potentially dangerous headers or includes if necessary,
        // but since this is a local sandbox for an agent, we assume some level of trust.
        // The script is written atomically to avoid race conditions.

        // Wrap the code to include Tailwind in the preview output head
        $wrappedCode = <<<PHP
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Render</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    {$code}
</body>
</html>
PHP;

        // Write the code to the temporary file
        file_put_contents($tempFile, $wrappedCode, LOCK_EX);

        // Render it using our Rendering Engine
        $engine = new RenderingEngine($previewDir);
        $fileName = basename($tempFile);

        try {
            // Render the temp file safely using our engine
            $output = $engine->render($fileName);
            echo $output;
        } catch (\Throwable $e) {
            echo "<div style='color: red; padding: 20px; border: 1px solid red; background: #fee;'>";
            echo "<strong>Rendering Error:</strong> " . htmlspecialchars($e->getMessage());
            echo "</div>";
        } finally {
            // Always clean up the temporary file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}
