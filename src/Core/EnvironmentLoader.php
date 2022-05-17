<?php

namespace SilverStripe\Core;

use M1\Env\Parser;

/**
 * Loads environment variables from .env files
 * Loosely based on https://github.com/vlucas/phpdotenv/blob/master/src/Loader.php
 */
class EnvironmentLoader
{
    /**
     * Load environment variables from .env file
     *
     * @param string $path Path to the file
     * @param bool $overload Set to true to allow vars to overload. Recommended to leave false.
     * @return array|null List of values parsed as an associative array, or null if not loaded
     * If overloading, this list will reflect the final state for all variables
     */
    public function loadFile($path, $overload = false)
    {
        // Not readable
        if (!file_exists($path ?? '') || !is_readable($path ?? '')) {
            return null;
        }

        // Parse and cleanup content
        $result = [];
        $variables = Parser::parse(file_get_contents($path ?? ''));
        foreach ($variables as $name => $value) {
            // Conditionally prevent overloading
            if (!$overload) {
                $existing = Environment::getEnv($name);
                if ($existing !== false) {
                    $result[$name] = $existing;
                    continue;
                }
            }

            // Overload or create var
            Environment::setEnv($name, $value);
            $result[$name] = $value;
        }
        return $result;
    }
}
