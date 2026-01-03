<?php

/**
 * Web Hosting Service Monitor
 * 
 * Monitors various aspects of web hosting service:
 * - Server availability
 * - Network speed
 * - Redis connectivity
 * - Database connectivity
 * - Disk space
 * - System resources (memory, CPU)
 * - PHP configuration
 * 
 * Usage: php hosting_monitor.php
 * Recommended cron: *\/5 * * * * /usr/bin/php /path/to/hosting_monitor.php
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set timezone
date_default_timezone_set('UTC');

// Load configuration
$configFile = __DIR__ . '/monitor_config.php';
if (!file_exists($configFile)) {
    die("Error: Configuration file not found: $configFile\n");
}

$config = require $configFile;

// Initialize results array
$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => [],
    'failures' => [],
    'warnings' => [],
    'success' => true,
];

/**
 * Log message to file
 */
function logMessage($message, $config)
{
    if (!$config['logging']['enabled']) {
        return;
    }

    $logFile = $config['logging']['log_file'];
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    // Rotate log if too large
    if (file_exists($logFile) && filesize($logFile) > $config['logging']['max_log_size']) {
        @rename($logFile, $logFile . '.' . date('YmdHis'));
    }

    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Add test result
 */
function addTestResult($name, $status, $message, $data = [], $isWarning = false)
{
    global $results;

    $result = [
        'name' => $name,
        'status' => $status,  // 'success', 'warning', 'failure'
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s'),
    ];

    $results['tests'][] = $result;

    if ($status === 'failure') {
        $results['failures'][] = $result;
        $results['success'] = false;
    } elseif ($status === 'warning' || $isWarning) {
        $results['warnings'][] = $result;
    }

    logMessage("Test: $name - Status: $status - $message", $GLOBALS['config']);
}

/**
 * Test server availability
 */
function testServerAvailability($config)
{
    if (!$config['availability']['enabled']) {
        return;
    }

    $testUrls = $config['availability']['test_urls'];
    $timeout = $config['availability']['timeout'];
    $expectedStatus = $config['availability']['expected_status_code'];

    foreach ($testUrls as $url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);  // HEAD request

        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $responseTime = round(($endTime - $startTime) * 1000, 2);  // milliseconds

        curl_close($ch);

        if ($error) {
            addTestResult(
                "Server Availability: $url",
                'failure',
                "Connection failed: $error",
                ['url' => $url, 'error' => $error]
            );
        } elseif ($httpCode != $expectedStatus) {
            addTestResult(
                "Server Availability: $url",
                'failure',
                "Unexpected HTTP status code: $httpCode (expected: $expectedStatus)",
                ['url' => $url, 'http_code' => $httpCode, 'response_time_ms' => $responseTime]
            );
        } else {
            addTestResult(
                "Server Availability: $url",
                'success',
                "Server is available (HTTP $httpCode)",
                ['url' => $url, 'http_code' => $httpCode, 'response_time_ms' => $responseTime]
            );
        }
    }
}

/**
 * Test network speed
 */
function testNetworkSpeed($config)
{
    if (!$config['network']['enabled']) {
        return;
    }

    $testUrl = $config['network']['test_url'];
    $timeout = $config['network']['timeout'];
    $minSpeed = $config['network']['min_download_speed'];

    $ch = curl_init($testUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $startTime = microtime(true);
    $data = curl_exec($ch);
    $endTime = microtime(true);

    $error = curl_error($ch);
    $downloadSize = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
    $totalTime = $endTime - $startTime;

    curl_close($ch);

    if ($error) {
        addTestResult(
            'Network Speed Test',
            'failure',
            "Download failed: $error",
            ['url' => $testUrl, 'error' => $error]
        );
        return;
    }

    if ($totalTime > 0 && $downloadSize > 0) {
        $speedMbps = ($downloadSize * 8) / ($totalTime * 1000000);  // Convert to Mbps
        $speedMbps = round($speedMbps, 2);

        if ($speedMbps < $minSpeed) {
            addTestResult(
                'Network Speed Test',
                'warning',
                "Download speed is below threshold: {$speedMbps} Mbps (minimum: {$minSpeed} Mbps)",
                [
                    'url' => $testUrl,
                    'speed_mbps' => $speedMbps,
                    'download_size_bytes' => $downloadSize,
                    'time_seconds' => round($totalTime, 2),
                ]
            );
        } else {
            addTestResult(
                'Network Speed Test',
                'success',
                "Download speed: {$speedMbps} Mbps",
                [
                    'url' => $testUrl,
                    'speed_mbps' => $speedMbps,
                    'download_size_bytes' => $downloadSize,
                    'time_seconds' => round($totalTime, 2),
                ]
            );
        }
    } else {
        addTestResult(
            'Network Speed Test',
            'warning',
            "Could not calculate speed (size: {$downloadSize} bytes, time: {$totalTime}s)",
            ['url' => $testUrl]
        );
    }
}

/**
 * Test Redis connectivity
 * Supports both Unix socket (for LiteSpeed) and TCP/IP connections
 */
function testRedis($config)
{
    if (!$config['redis']['enabled']) {
        return;
    }

    if (!extension_loaded('redis')) {
        addTestResult(
            'Redis Connectivity',
            'failure',
            'Redis extension is not installed',
            []
        );
        return;
    }

    try {
        $redis = new Redis();
        $connectionInfo = [];
        $connected = false;

        // Check if Unix socket is configured (for LiteSpeed Redis)
        if (!empty($config['redis']['socket']) && file_exists($config['redis']['socket'])) {
            // Connect via Unix socket
            $connected = $redis->connect($config['redis']['socket']);
            $connectionInfo = [
                'type' => 'unix_socket',
                'socket' => $config['redis']['socket'],
            ];
        } else {
            // Connect via TCP/IP
            $connected = $redis->connect(
                $config['redis']['host'],
                $config['redis']['port'],
                $config['redis']['timeout']
            );
            $connectionInfo = [
                'type' => 'tcp_ip',
                'host' => $config['redis']['host'],
                'port' => $config['redis']['port'],
            ];
        }

        if (!$connected) {
            addTestResult(
                'Redis Connectivity',
                'failure',
                'Failed to connect to Redis server',
                $connectionInfo
            );
            return;
        }

        // Authenticate if password is set
        if (!empty($config['redis']['password'])) {
            if (!$redis->auth($config['redis']['password'])) {
                addTestResult(
                    'Redis Connectivity',
                    'failure',
                    'Redis authentication failed',
                    $connectionInfo
                );
                $redis->close();
                return;
            }
        }

        // Test ping
        $pingResult = $redis->ping();
        $info = $redis->info('server');

        $redis->close();

        if ($pingResult) {
            $version = isset($info['redis_version']) ? $info['redis_version'] : 'unknown';
            $connectionInfo['version'] = $version;
            addTestResult(
                'Redis Connectivity',
                'success',
                "Redis is connected and responding via " . ($connectionInfo['type'] === 'unix_socket' ? 'Unix socket' : 'TCP/IP'),
                $connectionInfo
            );
        } else {
            addTestResult(
                'Redis Connectivity',
                'failure',
                'Redis ping failed',
                $connectionInfo
            );
        }
    } catch (Exception $e) {
        $connectionInfo = !empty($config['redis']['socket'])
            ? ['type' => 'unix_socket', 'socket' => $config['redis']['socket']]
            : ['type' => 'tcp_ip', 'host' => $config['redis']['host'], 'port' => $config['redis']['port']];
        $connectionInfo['error'] = $e->getMessage();

        addTestResult(
            'Redis Connectivity',
            'failure',
            "Redis connection error: " . $e->getMessage(),
            $connectionInfo
        );
    }
}

/**
 * Test database connectivity
 */
function testDatabase($config)
{
    if (!$config['database']['enabled']) {
        return;
    }

    $host = $config['database']['host'];
    $port = $config['database']['port'];
    $name = $config['database']['name'];
    $username = $config['database']['username'];
    $password = $config['database']['password'];
    $type = $config['database']['type'];
    $timeout = $config['database']['timeout'];

    try {
        if ($type === 'mysql') {
            if (!extension_loaded('mysqli') && !extension_loaded('pdo_mysql')) {
                addTestResult(
                    'Database Connectivity',
                    'failure',
                    'MySQL extension is not installed',
                    []
                );
                return;
            }

            // Try mysqli first
            if (extension_loaded('mysqli')) {
                $mysqli = @new mysqli($host, $username, $password, $name, $port);

                if ($mysqli->connect_error) {
                    addTestResult(
                        'Database Connectivity',
                        'failure',
                        "MySQL connection failed: " . $mysqli->connect_error,
                        [
                            'host' => $host,
                            'port' => $port,
                            'database' => $name,
                            'error' => $mysqli->connect_error,
                        ]
                    );
                    return;
                }

                // Test query
                $result = $mysqli->query("SELECT VERSION() as version");
                if ($result) {
                    $row = $result->fetch_assoc();
                    $version = $row['version'] ?? 'unknown';
                    addTestResult(
                        'Database Connectivity',
                        'success',
                        "MySQL database is connected",
                        [
                            'host' => $host,
                            'port' => $port,
                            'database' => $name,
                            'version' => $version,
                        ]
                    );
                } else {
                    addTestResult(
                        'Database Connectivity',
                        'failure',
                        "MySQL query failed: " . $mysqli->error,
                        ['host' => $host, 'port' => $port, 'database' => $name]
                    );
                }

                $mysqli->close();
            } else {
                // Fallback to PDO
                $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
                $pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_TIMEOUT => $timeout,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);

                $stmt = $pdo->query("SELECT VERSION() as version");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $version = $row['version'] ?? 'unknown';

                addTestResult(
                    'Database Connectivity',
                    'success',
                    "MySQL database is connected (via PDO)",
                    [
                        'host' => $host,
                        'port' => $port,
                        'database' => $name,
                        'version' => $version,
                    ]
                );
            }
        } elseif ($type === 'pgsql') {
            if (!extension_loaded('pgsql') && !extension_loaded('pdo_pgsql')) {
                addTestResult(
                    'Database Connectivity',
                    'failure',
                    'PostgreSQL extension is not installed',
                    []
                );
                return;
            }

            $dsn = "pgsql:host=$host;port=$port;dbname=$name";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_TIMEOUT => $timeout,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $stmt = $pdo->query("SELECT version()");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $version = $row['version'] ?? 'unknown';

            addTestResult(
                'Database Connectivity',
                'success',
                "PostgreSQL database is connected",
                [
                    'host' => $host,
                    'port' => $port,
                    'database' => $name,
                    'version' => $version,
                ]
            );
        }
    } catch (Exception $e) {
        addTestResult(
            'Database Connectivity',
            'failure',
            "Database connection error: " . $e->getMessage(),
            [
                'host' => $host,
                'port' => $port,
                'database' => $name,
                'type' => $type,
                'error' => $e->getMessage(),
            ]
        );
    }
}

/**
 * Test disk space
 */
function testDiskSpace($config)
{
    if (!$config['disk']['enabled']) {
        return;
    }

    $paths = $config['disk']['paths'];
    $warningThreshold = $config['disk']['warning_threshold'];
    $criticalThreshold = $config['disk']['critical_threshold'];

    foreach ($paths as $path) {
        if (!file_exists($path)) {
            addTestResult(
                "Disk Space: $path",
                'warning',
                "Path does not exist",
                ['path' => $path]
            );
            continue;
        }

        $totalBytes = disk_total_space($path);
        $freeBytes = disk_free_space($path);
        $usedBytes = $totalBytes - $freeBytes;

        if ($totalBytes === false || $freeBytes === false) {
            addTestResult(
                "Disk Space: $path",
                'failure',
                "Could not read disk space information",
                ['path' => $path]
            );
            continue;
        }

        $usedPercent = round(($usedBytes / $totalBytes) * 100, 2);
        $freePercent = round(($freeBytes / $totalBytes) * 100, 2);

        $totalGB = round($totalBytes / (1024 * 1024 * 1024), 2);
        $freeGB = round($freeBytes / (1024 * 1024 * 1024), 2);
        $usedGB = round($usedBytes / (1024 * 1024 * 1024), 2);

        $status = 'success';
        $message = "Disk usage: {$usedPercent}% (Free: {$freeGB} GB / Total: {$totalGB} GB)";

        if ($usedPercent >= $criticalThreshold) {
            $status = 'failure';
            $message = "CRITICAL: Disk usage is {$usedPercent}% (exceeds {$criticalThreshold}% threshold)";
        } elseif ($usedPercent >= $warningThreshold) {
            $status = 'warning';
            $message = "WARNING: Disk usage is {$usedPercent}% (exceeds {$warningThreshold}% threshold)";
        }

        addTestResult(
            "Disk Space: $path",
            $status,
            $message,
            [
                'path' => $path,
                'total_gb' => $totalGB,
                'free_gb' => $freeGB,
                'used_gb' => $usedGB,
                'used_percent' => $usedPercent,
                'free_percent' => $freePercent,
            ]
        );
    }
}

/**
 * Test system memory
 */
function testMemory($config)
{
    if (!$config['system']['memory']['enabled']) {
        return;
    }

    $warningThreshold = $config['system']['memory']['warning_threshold'];
    $criticalThreshold = $config['system']['memory']['critical_threshold'];

    // Get memory usage
    $memInfo = @file_get_contents('/proc/meminfo');
    if ($memInfo === false) {
        // Fallback to PHP memory functions
        $memoryLimit = ini_get('memory_limit');
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);

        addTestResult(
            'System Memory',
            'warning',
            "Could not read system memory info (using PHP memory info)",
            [
                'memory_limit' => $memoryLimit,
                'memory_usage_bytes' => $memoryUsage,
                'memory_peak_bytes' => $memoryPeak,
            ]
        );
        return;
    }

    preg_match('/MemTotal:\s+(\d+)\s+kB/', $memInfo, $totalMatch);
    preg_match('/MemAvailable:\s+(\d+)\s+kB/', $memInfo, $availMatch);
    preg_match('/MemFree:\s+(\d+)\s+kB/', $memInfo, $freeMatch);

    if (empty($totalMatch) || empty($availMatch)) {
        addTestResult(
            'System Memory',
            'warning',
            "Could not parse memory information",
            []
        );
        return;
    }

    $totalKB = (int)$totalMatch[1];
    $availableKB = (int)$availMatch[1];
    $usedKB = $totalKB - $availableKB;
    $usedPercent = round(($usedKB / $totalKB) * 100, 2);

    $totalGB = round($totalKB / (1024 * 1024), 2);
    $availableGB = round($availableKB / (1024 * 1024), 2);
    $usedGB = round($usedKB / (1024 * 1024), 2);

    $status = 'success';
    $message = "Memory usage: {$usedPercent}% (Used: {$usedGB} GB / Total: {$totalGB} GB)";

    if ($usedPercent >= $criticalThreshold) {
        $status = 'failure';
        $message = "CRITICAL: Memory usage is {$usedPercent}% (exceeds {$criticalThreshold}% threshold)";
    } elseif ($usedPercent >= $warningThreshold) {
        $status = 'warning';
        $message = "WARNING: Memory usage is {$usedPercent}% (exceeds {$warningThreshold}% threshold)";
    }

    addTestResult(
        'System Memory',
        $status,
        $message,
        [
            'total_gb' => $totalGB,
            'used_gb' => $usedGB,
            'available_gb' => $availableGB,
            'used_percent' => $usedPercent,
        ]
    );
}

/**
 * Get CPU count without using shell_exec
 */
function getCPUCount()
{
    // Try reading from /proc/cpuinfo (most reliable)
    $cpuInfo = @file_get_contents('/proc/cpuinfo');
    if ($cpuInfo !== false) {
        $cpuCount = substr_count($cpuInfo, 'processor');
        if ($cpuCount > 0) {
            return $cpuCount;
        }
    }

    // Try reading from /proc/stat
    $stat = @file_get_contents('/proc/stat');
    if ($stat !== false) {
        preg_match_all('/^cpu\d+/m', $stat, $matches);
        $cpuCount = count($matches[0]);
        if ($cpuCount > 0) {
            return $cpuCount;
        }
    }

    // Try shell_exec only if available (for compatibility)
    if (function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
        $cpuCount = (int)@shell_exec('nproc');
        if ($cpuCount > 0) {
            return $cpuCount;
        }
    }

    // Fallback: assume 1 CPU if we can't determine
    return 1;
}

/**
 * Test CPU load
 */
function testCPU($config)
{
    if (!$config['system']['cpu']['enabled']) {
        return;
    }

    $warningThreshold = $config['system']['cpu']['warning_threshold'];
    $criticalThreshold = $config['system']['cpu']['critical_threshold'];

    $loadAvg = @file_get_contents('/proc/loadavg');
    if ($loadAvg === false) {
        // Try sys_getloadavg() if available
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if ($load !== false) {
                $load1min = $load[0];
                $load5min = $load[1];
                $load15min = $load[2];

                // Get CPU count (without shell_exec)
                $cpuCount = getCPUCount();
                $loadPercent = round(($load1min / $cpuCount) * 100, 2);

                $status = 'success';
                $message = "CPU Load: {$load1min} (1min) / {$load5min} (5min) / {$load15min} (15min)";

                if ($loadPercent >= $criticalThreshold) {
                    $status = 'failure';
                    $message = "CRITICAL: CPU load is {$loadPercent}% (exceeds {$criticalThreshold}% threshold)";
                } elseif ($loadPercent >= $warningThreshold) {
                    $status = 'warning';
                    $message = "WARNING: CPU load is {$loadPercent}% (exceeds {$warningThreshold}% threshold)";
                }

                addTestResult(
                    'CPU Load',
                    $status,
                    $message,
                    [
                        'load_1min' => $load1min,
                        'load_5min' => $load5min,
                        'load_15min' => $load15min,
                        'cpu_count' => $cpuCount,
                        'load_percent' => $loadPercent,
                    ]
                );
                return;
            }
        }

        addTestResult(
            'CPU Load',
            'warning',
            "Could not read CPU load information",
            []
        );
        return;
    }

    $load = explode(' ', trim($loadAvg));
    $load1min = (float)$load[0];
    $load5min = (float)$load[1];
    $load15min = (float)$load[2];

    // Get CPU count (without shell_exec)
    $cpuCount = getCPUCount();
    $loadPercent = round(($load1min / $cpuCount) * 100, 2);

    $status = 'success';
    $message = "CPU Load: {$load1min} (1min) / {$load5min} (5min) / {$load15min} (15min)";

    if ($loadPercent >= $criticalThreshold) {
        $status = 'failure';
        $message = "CRITICAL: CPU load is {$loadPercent}% (exceeds {$criticalThreshold}% threshold)";
    } elseif ($loadPercent >= $warningThreshold) {
        $status = 'warning';
        $message = "WARNING: CPU load is {$loadPercent}% (exceeds {$warningThreshold}% threshold)";
    }

    addTestResult(
        'CPU Load',
        $status,
        $message,
        [
            'load_1min' => $load1min,
            'load_5min' => $load5min,
            'load_15min' => $load15min,
            'cpu_count' => $cpuCount,
            'load_percent' => $loadPercent,
        ]
    );
}

/**
 * Test PHP configuration
 */
function testPHP($config)
{
    if (!$config['system']['php']['enabled']) {
        return;
    }

    $minVersion = $config['system']['php']['min_version'];
    $currentVersion = PHP_VERSION;

    if (version_compare($currentVersion, $minVersion, '<')) {
        addTestResult(
            'PHP Version',
            'warning',
            "PHP version {$currentVersion} is below recommended minimum {$minVersion}",
            [
                'current_version' => $currentVersion,
                'min_version' => $minVersion,
            ]
        );
    } else {
        addTestResult(
            'PHP Version',
            'success',
            "PHP version: {$currentVersion}",
            [
                'current_version' => $currentVersion,
                'min_version' => $minVersion,
            ]
        );
    }

    // Check important PHP settings
    $importantSettings = [
        'max_execution_time' => ini_get('max_execution_time'),
        'memory_limit' => ini_get('memory_limit'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
    ];

    addTestResult(
        'PHP Configuration',
        'success',
        "PHP configuration check",
        $importantSettings
    );
}

/**
 * Send email notification
 */
function sendEmail($config, $results, $isHourlyReport = false)
{
    if (!$config['email']['enabled']) {
        return false;
    }

    $to = $config['email']['to'];
    $from = $config['email']['from'];

    if ($isHourlyReport) {
        $subject = $config['email']['subject_hourly'];
    } else {
        $subject = $config['email']['subject_failure'];
    }

    // Build email body
    $body = "Web Hosting Service Monitor Report\n";
    $body .= "===================================\n\n";
    $body .= "Timestamp: " . $results['timestamp'] . "\n";
    $body .= "Total Tests: " . count($results['tests']) . "\n";
    $body .= "Failures: " . count($results['failures']) . "\n";
    $body .= "Warnings: " . count($results['warnings']) . "\n\n";

    if (!empty($results['failures'])) {
        $body .= "FAILURES:\n";
        $body .= "---------\n";
        foreach ($results['failures'] as $failure) {
            $body .= "✗ {$failure['name']}: {$failure['message']}\n";
            if (!empty($failure['data'])) {
                foreach ($failure['data'] as $key => $value) {
                    $body .= "  - $key: " . (is_array($value) ? json_encode($value) : $value) . "\n";
                }
            }
            $body .= "\n";
        }
    }

    if (!empty($results['warnings'])) {
        $body .= "WARNINGS:\n";
        $body .= "---------\n";
        foreach ($results['warnings'] as $warning) {
            $body .= "⚠ {$warning['name']}: {$warning['message']}\n";
            if (!empty($warning['data'])) {
                foreach ($warning['data'] as $key => $value) {
                    $body .= "  - $key: " . (is_array($value) ? json_encode($value) : $value) . "\n";
                }
            }
            $body .= "\n";
        }
    }

    $body .= "\nALL TEST RESULTS:\n";
    $body .= "-----------------\n";
    foreach ($results['tests'] as $test) {
        $statusIcon = $test['status'] === 'success' ? '✓' : ($test['status'] === 'warning' ? '⚠' : '✗');
        $body .= "$statusIcon {$test['name']}: {$test['message']}\n";
    }

    // Use PHPMailer if available, otherwise use mail() function
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $config['email']['smtp']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['email']['smtp']['username'];
            $mail->Password = $config['email']['smtp']['password'];
            $mail->SMTPSecure = $config['email']['smtp']['encryption'];
            $mail->Port = $config['email']['smtp']['port'];

            $mail->setFrom($from);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();
            logMessage("Email sent successfully to $to", $config);
            return true;
        } catch (Exception $e) {
            logMessage("Failed to send email via PHPMailer: " . $e->getMessage(), $config);
            // Fallback to mail() function
        }
    }

    // Fallback to mail() function
    $headers = "From: $from\r\n";
    $headers .= "Reply-To: $from\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    if (@mail($to, $subject, $body, $headers)) {
        logMessage("Email sent successfully to $to (using mail() function)", $config);
        return true;
    } else {
        logMessage("Failed to send email to $to", $config);
        return false;
    }
}

// Main execution
try {
    logMessage("Starting monitoring checks...", $config);

    // Run all tests
    testServerAvailability($config);
    testNetworkSpeed($config);
    testRedis($config);
    testDatabase($config);
    testDiskSpace($config);
    testMemory($config);
    testCPU($config);
    testPHP($config);

    // Determine if we should send email
    $currentHour = (int)date('H');
    $currentMinute = (int)date('i');
    $isHourlyReport = ($currentMinute == 0);  // At HH:00

    $shouldSendEmail = false;
    $isFailureEmail = false;

    if (!$results['success']) {
        // Send failure email immediately
        $shouldSendEmail = true;
        $isFailureEmail = true;
    } elseif ($isHourlyReport) {
        // Send hourly report
        $shouldSendEmail = true;
        $isFailureEmail = false;
    }

    if ($shouldSendEmail) {
        sendEmail($config, $results, !$isFailureEmail);
    }

    // Log comprehensive summary for this run
    logMessage("=== Monitoring Run Summary ===", $config);
    logMessage("Timestamp: " . $results['timestamp'], $config);
    logMessage("Total Tests: " . count($results['tests']), $config);
    logMessage("Success: " . ($results['success'] ? 'Yes' : 'No'), $config);
    logMessage("Failures: " . count($results['failures']), $config);
    logMessage("Warnings: " . count($results['warnings']), $config);

    if (!empty($results['failures'])) {
        logMessage("--- Failures ---", $config);
        foreach ($results['failures'] as $failure) {
            logMessage("  ✗ {$failure['name']}: {$failure['message']}", $config);
        }
    }

    if (!empty($results['warnings'])) {
        logMessage("--- Warnings ---", $config);
        foreach ($results['warnings'] as $warning) {
            logMessage("  ⚠ {$warning['name']}: {$warning['message']}", $config);
        }
    }

    logMessage("--- All Test Results ---", $config);
    foreach ($results['tests'] as $test) {
        $statusIcon = $test['status'] === 'success' ? '✓' : ($test['status'] === 'warning' ? '⚠' : '✗');
        logMessage("  $statusIcon {$test['name']}: {$test['message']}", $config);
    }

    logMessage("=== End of Run ===", $config);
    logMessage("", $config); // Empty line for readability

    // Exit with appropriate code
    exit($results['success'] ? 0 : 1);
} catch (Exception $e) {
    logMessage("Fatal error: " . $e->getMessage(), $config);
    addTestResult('System', 'failure', "Fatal error: " . $e->getMessage(), []);
    sendEmail($config, $results, false);
    exit(1);
}