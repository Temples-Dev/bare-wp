<?php

namespace BareWP;

use Exception;

class RenderingEngine
{
    /**
     * @var string The base directory where templates are safely stored.
     */
    protected string $basePath;

    /**
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
    }

    /**
     * Render a template given a relative path and a data array.
     * Extracts data into variables and captures output using output buffering.
     *
     * @param string $templatePath Relative path to the view/template.
     * @param array $data Associative array of data to extract.
     * @return string The rendered HTML/PHP content.
     * @throws Exception If template cannot be found or path is unsafe.
     */
    public function render(string $templatePath, array $data = []): string
    {
        $fullPath = $this->resolvePath($templatePath);

        if ($fullPath === false || !file_exists($fullPath)) {
            throw new Exception("Template not found or invalid path: {$templatePath}");
        }

        // Extract variables into current scope so the template can use them
        extract($data, EXTR_SKIP);

        // Turn on output buffering
        ob_start();

        try {
            // Include the template safely
            require $fullPath;
            $renderedContent = ob_get_clean();
        } catch (\Throwable $e) {
            // Ensure output buffering is closed on exception
            ob_end_clean();
            throw $e;
        }

        return $renderedContent;
    }

    /**
     * Safely resolve the template path to prevent directory traversal attacks.
     *
     * @param string $templatePath
     * @return string|false
     */
    protected function resolvePath(string $templatePath)
    {
        // Strip out any null bytes
        $templatePath = str_replace("\0", '', $templatePath);

        // Remove directory traversal segments
        $templatePath = str_replace(['../', '..\\'], '', $templatePath);

        // Construct the expected full path
        $expectedPath = $this->basePath . DIRECTORY_SEPARATOR . ltrim($templatePath, '/\\');

        // Get the realpath to normalize and check existence
        $realPath = realpath($expectedPath);
        $realBasePath = realpath($this->basePath);

        if ($realPath === false || $realBasePath === false) {
            return false;
        }

        // Ensure the resolved path still starts with our safe base path
        if (strpos($realPath, $realBasePath) !== 0) {
            return false;
        }

        return $realPath;
    }
}
