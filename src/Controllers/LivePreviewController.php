<?php

namespace BareWP\Controllers;

use BareWP\RenderingEngine;
use Exception;

class LivePreviewController
{
    /**
     * Shows the live preview sandbox UI with a high-end IDE interface.
     */
    public function index(): void
    {
        // Security checks
        if (!in_array(getenv('APP_ENV'), ['local', 'dev'])) {
            http_response_code(403);
            die('Access Denied: Sandbox only available in local/dev environments.');
        }

        if (!current_user_can('manage_options')) {
            http_response_code(403);
            die('Access Denied: Administrator privileges required.');
        }

        $csrf_nonce = wp_create_nonce('live_preview_render');

        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BARE-WP Code Session</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs/loader.min.js"></script>
    <style>
        #editor-container { height: calc(100vh - 120px); }
        .sidebar { width: 260px; height: calc(100vh - 120px); }
    </style>
</head>
<body class="bg-[#0f172a] text-slate-200 min-h-screen flex flex-col overflow-hidden">
    <!-- Header -->
    <header class="h-16 border-b border-slate-800 flex items-center justify-between px-6 bg-[#1e293b]">
        <div class="flex items-center gap-4">
            <h1 class="text-xl font-bold bg-gradient-to-r from-blue-400 to-teal-300 bg-clip-text text-transparent">BARE-WP Code Session</h1>
            <span id="current-filename" class="text-sm text-slate-400 font-mono italic">untitled.html</span>
        </div>
        <div class="flex items-center gap-4">
            <button onclick="saveTemplate()" class="bg-slate-700 hover:bg-slate-600 text-white text-sm font-semibold py-2 px-4 rounded transition-all">
                Save
            </button>
            <button onclick="deployToLive()" class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold py-2 px-4 rounded transition-all">
                Deploy to Live
            </button>
        </div>
    </header>

    <main class="flex-1 flex overflow-hidden">
        <!-- Sidebar -->
        <aside class="sidebar bg-[#1e293b] border-r border-slate-800 p-4 flex flex-col gap-4">
            <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Templates</h2>
            <div id="template-list" class="flex-1 overflow-y-auto space-y-1">
                <p class="text-xs text-slate-500">Loading templates...</p>
            </div>
            <button onclick="createNew()" class="w-full py-2 border border-slate-700 hover:border-blue-500 text-xs text-slate-400 hover:text-blue-400 transition-all rounded">
                + New Page
            </button>
        </aside>

        <!-- Editor Pane -->
        <div class="flex-1 flex flex-col relative">
            <div id="editor-container" class="w-full"></div>
            <div class="absolute bottom-4 right-4 z-10 flex gap-2">
                 <span id="status-indicator" class="text-[10px] bg-slate-800 px-2 py-1 rounded text-slate-400">Ready</span>
            </div>
        </div>

        <!-- Preview Pane -->
        <div class="flex-1 bg-white border-l border-slate-800 relative">
            <iframe id="preview-iframe" class="w-full h-full border-0"></iframe>
            <form id="preview-form" target="preview-iframe" method="POST" action="/preview/render" class="hidden">
                <textarea name="code" id="hidden-code"></textarea>
                <input type="hidden" name="_csrf" value="{$csrf_nonce}">
            </form>
        </div>
    </main>

    <script>
        require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs' }});
        
        let editor;
        let activeTemplate = null;

        require(['vs/editor/editor.main'], function() {
            monaco.editor.defineTheme('bare-dark', {
                base: 'vs-dark',
                inherit: true,
                rules: [],
                colors: {
                    'editor.background': '#0f172a',
                }
            });

            editor = monaco.editor.create(document.getElementById('editor-container'), {
                value: '<div class="p-12 text-center">\\n  <h1 class="text-5xl font-extrabold text-blue-600">BARE-WP</h1>\\n  <p class="text-xl text-gray-500 mt-4">Start prompting or coding your custom UI.</p>\\n</div>',
                language: 'html',
                theme: 'bare-dark',
                automaticLayout: true,
                fontSize: 14,
                fontFamily: 'JetBrains Mono, Menlo, Monaco, Courier New, monospace',
                minimap: { enabled: false },
                lineNumbersMinChars: 3,
                padding: { top: 20 }
            });

            // Hot-Reload Logic
            let timeout = null;
            editor.onDidChangeModelContent(() => {
                document.getElementById('status-indicator').innerText = 'Typing...';
                clearTimeout(timeout);
                timeout = setTimeout(renderPreview, 800);
            });

            renderPreview();
            loadTemplates();
        });

        function renderPreview() {
            const code = editor.getValue();
            document.getElementById('hidden-code').value = code;
            document.getElementById('preview-form').submit();
            document.getElementById('status-indicator').innerText = 'Synced';
        }

        async function loadTemplates() {
            const resp = await fetch('/api/templates');
            const data = await resp.json();
            if (data.success) {
                const list = document.getElementById('template-list');
                list.innerHTML = '';
                data.data.forEach(tpl => {
                    const btn = document.createElement('button');
                    btn.className = 'w-full text-left p-2 text-xs text-slate-400 hover:text-white hover:bg-slate-800 rounded truncate transition-all';
                    btn.innerText = tpl.name;
                    btn.onclick = () => {
                        editor.setValue(tpl.content);
                        activeTemplate = tpl.name;
                        document.getElementById('current-filename').innerText = tpl.name;
                    };
                    list.appendChild(btn);
                });
            }
        }

        async function saveTemplate() {
            let name = activeTemplate || prompt('Enter layout name (e.g., hero-v1.html):');
            if (!name) return;

            const code = editor.getValue();
            const formData = new FormData();
            formData.append('name', name);
            formData.append('code', code);
            formData.append('_csrf', '{$csrf_nonce}');

            const resp = await fetch('/api/templates/save', {
                method: 'POST',
                body: formData
            });

            const result = await resp.json();
            if (result.success) {
                alert('Saved to environment!');
                loadTemplates();
                activeTemplate = result.data.name;
                document.getElementById('current-filename').innerText = activeTemplate;
            } else {
                alert('Save failed: ' + result.data);
            }
        }

        async function deployToLive() {
            let route = prompt('Enter live route (e.g., /home or /landing):');
            if (!route) return;

            let name = activeTemplate || prompt('Enter layout name for storage:');
            if (!name) return;

            const code = editor.getValue();
            const formData = new FormData();
            formData.append('route', route);
            formData.append('name', name);
            formData.append('code', code);
            formData.append('_csrf', '{$csrf_nonce}');

            document.getElementById('status-indicator').innerText = 'Deploying...';

            const resp = await fetch('/api/templates/deploy', {
                method: 'POST',
                body: formData
            });

            const result = await resp.json();
            if (result.success) {
                alert('Success: ' + result.data.message);
                loadTemplates();
                activeTemplate = result.data.name || name;
                document.getElementById('current-filename').innerText = activeTemplate;
            } else {
                alert('Deployment Failed: ' + result.data);
            }
            document.getElementById('status-indicator').innerText = 'Ready';
        }

        function createNew() {
            activeTemplate = null;
            document.getElementById('current-filename').innerText = 'untitled.html';
            editor.setValue('<div class="p-8">\\n  <h2 class="text-2xl font-bold">New Layout</h2>\\n</div>');
        }
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
        // Security checks
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
            die('Invalid CSRF');
        }

        $code = $_POST['code'] ?? '';
        // Mitigate RCE: Strip PHP tags
        $code = preg_replace('/<\?php.*?\?>/is', '', $code);
        $code = str_ireplace(['<?php', '<?', '?>'], '', $code);

        if (empty(trim($code))) {
            echo "<p style='color:red;'>No code provided to render.</p>";
            return;
        }

        $previewDir = dirname(__DIR__, 2) . '/storage/preview';
        if (!is_dir($previewDir)) {
            mkdir($previewDir, 0755, true);
        }

        $tempFile = $previewDir . '/prev_render.html';

        $wrappedCode = <<<PHP
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Render</title>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body class="bg-white">
    {$code}
</body>
</html>
PHP;

        file_put_contents($tempFile, $wrappedCode, LOCK_EX);

        $engine = new RenderingEngine($previewDir);
        try {
            echo $engine->render(basename($tempFile));
        } catch (\Throwable $e) {
            echo "<div style='color: red; padding: 20px; border: 1px solid red; background: #fee;'>";
            echo "<strong>Rendering Error:</strong> " . htmlspecialchars($e->getMessage());
            echo "</div>";
        }
    }
}
