<?php

namespace BareWP\Deployment;

use BareWP\Security\Validator;

class Deployer
{
    private string $configPath;
    private Validator $validator;

    public function __construct()
    {
        $this->configPath = dirname(__DIR__, 2) . '/config/routes.json';
        $this->validator = new Validator();

        if (!is_dir(dirname($this->configPath))) {
            mkdir(dirname($this->configPath), 0755, true);
        }

        if (!file_exists($this->configPath)) {
            file_put_contents($this->configPath, json_encode(['routes' => []], JSON_PRETTY_PRINT));
        }
    }

    /**
     * Deploys a template to a specific live route.
     * 
     * @param string $route
     * @param string $templateName
     * @param string $code
     * @return array [bool success, string message]
     */
    public function deploy(string $route, string $templateName, string $code): array
    {
        // 1. Validate the code
        $result = $this->validator->validate($code);
        if (!$result[0]) {
            return $result;
        }

        // 2. Update the routes configuration
        $config = json_decode(file_get_contents($this->configPath), true);
        
        // Ensure route starts with /
        $route = '/' . ltrim($route, '/');
        
        $config['routes'][$route] = [
            'template' => $templateName,
            'deployed_at' => date('Y-m-d H:i:s')
        ];

        if (file_put_contents($this->configPath, json_encode($config, JSON_PRETTY_PRINT), LOCK_EX)) {
            return [true, "Template '{$templateName}' deployed successfully to '{$route}'"];
        }

        return [false, "Failed to update routes configuration"];
    }
}
