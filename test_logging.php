<?php
/**
 * Test Logging Functionality
 * This will test if error logging is working
 */

require_once __DIR__ . '/contact_config.php';

$config = require __DIR__ . '/contact_config.php';

/**
 * Custom error logging function (same as in contact_handler.php)
 */
function logError($message, $config = null)
{
    error_log($message);
    $logFile = __DIR__ . '/contact_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    return $logFile;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Error Logging</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #e8f5e9; border: 1px solid green; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffebee; border: 1px solid red; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #e3f2fd; border: 1px solid blue; margin: 10px 0; }
        button { padding: 10px 20px; margin: 5px; background: #007bff; color: white; border: none; cursor: pointer; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Error Logging Test</h1>
    
    <?php
    $logFile = __DIR__ . '/contact_errors.log';
    
    if (isset($_GET['test'])) {
        $testMessage = "TEST LOG ENTRY - " . date('Y-m-d H:i:s') . " - This is a test error message";
        $writtenFile = logError($testMessage, $config);
        
        if (file_exists($writtenFile)) {
            echo '<div class="success">✓ Test log entry written successfully!</div>';
            echo '<div class="info">Log file: ' . htmlspecialchars($writtenFile) . '</div>';
        } else {
            echo '<div class="error">✗ Failed to write log file. Check file permissions.</div>';
        }
    }
    
    if (isset($_GET['clear'])) {
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
            echo '<div class="success">Log file cleared!</div>';
        }
    }
    ?>
    
    <div>
        <a href="?test=1"><button>Test Logging</button></a>
        <a href="?clear=1"><button>Clear Log</button></a>
        <a href="view_errors.php"><button>View All Errors</button></a>
    </div>
    
    <h2>Current Log File Contents</h2>
    <div>
        <?php
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            if (!empty($content)) {
                echo '<pre>' . htmlspecialchars($content) . '</pre>';
            } else {
                echo '<div class="info">Log file is empty. Click "Test Logging" to add a test entry.</div>';
            }
        } else {
            echo '<div class="info">Log file does not exist yet. Click "Test Logging" to create it.</div>';
        }
        ?>
    </div>
    
    <h2>File Permissions</h2>
    <div>
        <?php
        $dir = __DIR__;
        $logPath = $logFile;
        
        echo '<div class="info">';
        echo '<strong>Directory:</strong> ' . htmlspecialchars($dir) . '<br>';
        echo '<strong>Writable:</strong> ' . (is_writable($dir) ? '✓ Yes' : '✗ No') . '<br>';
        echo '<strong>Log File Path:</strong> ' . htmlspecialchars($logPath) . '<br>';
        if (file_exists($logPath)) {
            echo '<strong>Log File Exists:</strong> ✓ Yes<br>';
            echo '<strong>Log File Writable:</strong> ' . (is_writable($logPath) ? '✓ Yes' : '✗ No') . '<br>';
            echo '<strong>Log File Size:</strong> ' . filesize($logPath) . ' bytes<br>';
        } else {
            echo '<strong>Log File Exists:</strong> ✗ No (will be created on first write)<br>';
        }
        echo '</div>';
        ?>
    </div>
</body>
</html>
