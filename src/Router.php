<?php

namespace BareWP;

class Router
{
    /**
     * @var array Registered routes mapped by HTTP method.
     */
    protected array $routes = [
        'GET'  => [],
        'POST' => [],
        'PUT'  => [],
        'DELETE' => []
    ];

    /**
     * Register a GET route.
     *
     * @param string $uri The URI to match.
     * @param mixed $action The action to execute (closure or array [Controller, method]).
     */
    public function get(string $uri, $action): void
    {
        $this->addRoute('GET', $uri, $action);
    }

    /**
     * Register a POST route.
     *
     * @param string $uri The URI to match.
     * @param mixed $action The action to execute.
     */
    public function post(string $uri, $action): void
    {
        $this->addRoute('POST', $uri, $action);
    }

    /**
     * Add a route to the collection.
     *
     * @param string $method
     * @param string $uri
     * @param mixed $action
     */
    protected function addRoute(string $method, string $uri, $action): void
    {
        $this->routes[$method][$uri] = $action;
    }

    /**
     * Dispatch the incoming request to the appropriate route action.
     *
     * @param string $uri
     * @param string $method
     */
    public function dispatch(string $uri, string $method): void
    {
        $parsedUri = parse_url($uri, PHP_URL_PATH);

        if ($parsedUri === null || $parsedUri === false) {
            $this->abort(400, 'Bad Request - Malformed URI');
            return;
        }

        $method = strtoupper($method);

        if (!isset($this->routes[$method])) {
            $this->abort(405, 'Method Not Allowed');
            return;
        }

        foreach ($this->routes[$method] as $routeUri => $action) {
            if ($routeUri === $parsedUri) {
                $this->executeAction($action);
                return;
            }
        }

        // Try to match dynamic template routes from config/routes.json
        if ($this->dispatchDynamicRoute($parsedUri, $method)) {
            return;
        }

        // If no route matched, return a 404
        $this->abort(404, 'Page Not Found');
    }

    /**
     * Attempts to dispatch a dynamic template route.
     * 
     * @param string $uri
     * @param string $method
     * @return bool
     */
    protected function dispatchDynamicRoute(string $uri, string $method): bool
    {
        if ($method !== 'GET') return false;

        $routesPath = dirname(__DIR__) . '/config/routes.json';
        if (!file_exists($routesPath)) return false;

        $config = json_decode(file_get_contents($routesPath), true);
        if (!isset($config['routes'][$uri])) return false;

        $template = $config['routes'][$uri]['template'];
        $templateDir = dirname(__DIR__) . '/Views/Templates';
        
        try {
            $engine = new RenderingEngine($templateDir);
            echo $engine->render($template);
            return true;
        } catch (\Throwable $e) {
            $this->abort(500, "Template Rendering Error: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Execute the matched action.
     *
     * @param mixed $action
     */
    protected function executeAction($action): void
    {
        if (is_callable($action)) {
            call_user_func($action);
        } elseif (is_array($action) && count($action) === 2) {
            [$class, $method] = $action;
            if (class_exists($class)) {
                $instance = new $class();
                if (method_exists($instance, $method)) {
                    $instance->$method();
                } else {
                    $this->abort(500, "Method {$method} not found in {$class}");
                }
            } else {
                $this->abort(500, "Class {$class} not found");
            }
        } else {
            $this->abort(500, 'Invalid route action format');
        }
    }

    /**
     * Abort the request with an HTTP status code.
     *
     * @param int $code
     * @param string $message
     */
    protected function abort(int $code, string $message): void
    {
        http_response_code($code);
        echo "<h1>{$code} - {$message}</h1>";
        exit;
    }
}
