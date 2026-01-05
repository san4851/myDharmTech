<?php

/**
 * Web Hosting Service Monitor Configuration
 * 
 * Configure all settings for the monitoring script
 */

// Load environment variables
require_once __DIR__ . '/../../env_loader.php';
$env = loadEnv(__DIR__ . '/../../.env');

return [
    // Email Configuration
    'email' => [
        'enabled' => true,
        'to' => 'san4851@gmail.com',  // Change this to your email
        'from' => 'admin@udaipurupdate.com',  // Change this to your sender email
        'subject_failure' => '[udaipurupdate.com] Hosting Service Alert - Failure Detected',
        'subject_hourly' => '[udaipurupdate.com] Hosting Service Hourly Report',
        'smtp' => [
            // SMTP settings loaded from .env file
            'host' => $env['SMTP_HOST'] ?? 'mail.mydharm.com',
            'port' => (int)($env['SMTP_PORT'] ?? 587),
            'username' => $env['SMTP_USERNAME'] ?? '',
            'password' => $env['SMTP_PASSWORD'] ?? '',
            'encryption' => $env['SMTP_ENCRYPTION'] ?? 'tls',  // 'tls' or 'ssl' (use 'ssl' for port 465)
        ],
    ],

    // Database Configuration
    'database' => [
        'enabled' => true,
        'host' => $env['DB_HOST'] ?? 'localhost',
        'port' => (int)($env['DB_PORT'] ?? 3306),
        // Database settings loaded from .env file
        'name' => $env['DB_NAME'] ?? 'mydharm_appdb',
        'username' => $env['DB_USERNAME'] ?? 'mydharm_appdb',
        'password' => $env['DB_PASSWORD'] ?? '',
        'type' => $env['DB_TYPE'] ?? 'mysql',  // 'mysql' or 'pgsql'
        'timeout' => (int)($env['DB_TIMEOUT'] ?? 5),  // Connection timeout in seconds
    ],

    // Redis Configuration
    'redis' => [
        'enabled' => true,
        // Use Unix socket for LiteSpeed Redis (recommended)
        // udapurupdate.com Redis
        // 'socket' => '/tmp/redis.sock',  // Unix socket path (leave empty to use TCP/IP)
        // mydharm.com Redis
        'socket' => '/home/mydharm/.redis/redis.sock',  // Unix socket path (leave empty to use TCP/IP)
        // TCP/IP connection (used if socket is empty)
        'host' => 'localhost',
        'port' => 6379,
        'password' => '',  // Leave empty if no password
        'timeout' => 5,  // Connection timeout in seconds
    ],

    // Disk Space Configuration
    'disk' => [
        'enabled' => true,
        'warning_threshold' => 80,  // Percentage - send warning if usage exceeds this
        'critical_threshold' => 90,  // Percentage - send alert if usage exceeds this
        'paths' => [
            '/',  // Root partition
            // Add more paths to monitor if needed
            // '/var/www',
            // '/home',
        ],
    ],

    // Network Speed Test Configuration
    'network' => [
        'enabled' => true,
        'test_url' => 'https://www.google.com',  // URL to test download speed
        'timeout' => 10,  // Timeout in seconds
        // udapurupdate.com network speed test
        // 'min_download_speed' => 0.3,  // Minimum acceptable speed in Mbps
        // mydharm.com network speed test
        'min_download_speed' => 1.0,  // Minimum acceptable speed in Mbps
    ],

    // Server Availability Configuration
    'availability' => [
        'enabled' => true,
        'test_urls' => [
            // 'https://www.udaipurupdate.com',  // Your main website URL
            'https://www.mydharm.com',  // Your main website URL
            // Add more URLs to test
        ],
        'timeout' => 10,  // Timeout in seconds
        'expected_status_code' => 200,
    ],

    // Additional Monitoring
    'system' => [
        'memory' => [
            'enabled' => true,
            'warning_threshold' => 80,  // Percentage
            'critical_threshold' => 90,  // Percentage
        ],
        'cpu' => [
            'enabled' => true,
            'warning_threshold' => 80,  // Percentage
            'critical_threshold' => 90,  // Percentage
        ],
        'php' => [
            'enabled' => true,
            'min_version' => '7.4',
        ],
    ],

    // Logging Configuration
    'logging' => [
        'enabled' => true,
        'log_file' => isset($env['MONITOR_LOG_FILE'])
            ? (strpos($env['MONITOR_LOG_FILE'], '/') === 0
                ? $env['MONITOR_LOG_FILE']
                : __DIR__ . '/../../' . $env['MONITOR_LOG_FILE'])
            : __DIR__ . '/monitor.log',
        'max_log_size' => 10485760,  // 10MB in bytes
    ],
];
