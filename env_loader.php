<?php

/**
 * Simple .env file loader
 * 
 * Loads environment variables from .env file
 * 
 * @param string $envPath Path to .env file
 * @return array Associative array of environment variables
 */
function loadEnv($envPath = null) {
    if ($envPath === null) {
        $envPath = __DIR__ . '/.env';
    }
    
    $env = [];
    
    if (!file_exists($envPath)) {
        return $env;
    }
    
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE format
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            $env[$key] = $value;
        }
    }
    
    return $env;
}
