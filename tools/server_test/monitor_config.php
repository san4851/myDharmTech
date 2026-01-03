<?php

/**
 * Web Hosting Service Monitor Configuration
 * 
 * Configure all settings for the monitoring script
 */

return [
    // Email Configuration
    'email' => [
        'enabled' => true,
        'to' => 'san4851@gmail.com',  // Change this to your email
        'from' => 'admin@udaipurupdate.com',  // Change this to your sender email
        'subject_failure' => '[udaipurupdate.com] Hosting Service Alert - Failure Detected',
        'subject_hourly' => '[udaipurupdate.com] Hosting Service Hourly Report',
        'smtp' => [
            // Udaipurupdate.com SMTP server
            // 'host' => 'mail.udaipurupdate.com',  // SMTP server
            // 'port' => 465,
            // 'username' => 'admin@udaipurupdate.com',  // SMTP username
            // 'password' => 'ENpQG4a0S@xs!*s',  // SMTP password or app password
            // 'encryption' => 'ssl',  // 'tls' or 'ssl' (use 'ssl' for port 465)
            // myDharm.com SMTP server
            'host' => 'mail.mydharm.com',  // SMTP server
            'port' => 587,
            'username' => 'mydharm@mydharm.com',  // SMTP username
            'password' => 'New#854ed#za#',  // SMTP password or app password
            'encryption' => 'tls',  // 'tls' or 'ssl' (use 'ssl' for port 465)
        ],
    ],

    // Database Configuration
    'database' => [
        'enabled' => true,
        'host' => 'localhost',
        'port' => 3306,
        // udapurupdate.com database
        // 'name' => 'udaipuru_wp305',
        // 'username' => 'udaipuru_wp305',
        // 'password' => '0s.S(597pG',
        // mydharm.com database
        'name' => 'mydharm_appdb',
        'username' => 'mydharm_appdb',
        'password' => 'tuPzX7hcYhaFRpEj2rph',
        'type' => 'mysql',  // 'mysql' or 'pgsql'
        'timeout' => 5,  // Connection timeout in seconds
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
        'log_file' => __DIR__ . '/monitor.log',
        'max_log_size' => 10485760,  // 10MB in bytes
    ],
];