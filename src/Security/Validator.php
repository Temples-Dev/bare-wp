<?php

namespace BareWP\Security;

class Validator
{
    /**
     * Dangerous PHP functions that are strictly prohibited in the sandbox.
     */
    private const DANGEROUS_FUNCTIONS = [
        'eval', 'exec', 'system', 'passthru', 'shell_exec', 'proc_open', 'popen', 'base64_decode',
        'assert', 'include', 'include_once', 'require', 'require_once', 'create_function'
    ];

    /**
     * Validates a given block of code for syntax and security.
     * 
     * @param string $code
     * @return array [bool success, string message]
     */
    public function validate(string $code): array
    {
        // 1. Syntax Check using PHP Linter
        $tempFile = sys_get_temp_dir() . '/val_' . bin2hex(random_bytes(8)) . '.php';
        file_put_contents($tempFile, "<?php\n" . $code);
        
        $output = [];
        $returnVar = 0;
        exec("php -l " . escapeshellarg($tempFile) . " 2>&1", $output, $returnVar);
        unlink($tempFile);

        if ($returnVar !== 0) {
            return [false, "PHP Syntax Error: " . implode("\n", $output)];
        }

        // 2. Security Check (Static Analysis)
        // Check for prohibited functions
        foreach (self::DANGEROUS_FUNCTIONS as $func) {
            if (preg_match("/\b{$func}\s*\(/i", $code)) {
                return [false, "Security Violation: Use of prohibited function '{$func}'"];
            }
        }

        // Check for direct access to superglobals (discouraged, should use WP sanitization)
        if (preg_match('/\$(?:_GET|_POST|_REQUEST|_SERVER|_SESSION|_COOKIE|_ENV)\b/', $code)) {
            return [false, "Security Policy: Direct access to PHP superglobals is prohibited. Use WordPress sanitization and request functions instead."];
        }

        return [true, "Code validated successfully"];
    }
}
