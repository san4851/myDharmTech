<?php
/**
 * Error Log Viewer
 * View contact form errors easily
 * 
 * SECURITY: Remove or password protect this file in production!
 */

session_start();

// Simple password protection (change this password!)
$password = 'admin123'; // Change this!
$isAuthenticated = false;

if (isset($_POST['password'])) {
    if ($_POST['password'] === $password) {
        $isAuthenticated = true;
        $_SESSION['authenticated'] = true;
    }
}

if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']) {
    $isAuthenticated = true;
}

if (!$isAuthenticated) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error Log Viewer - Login</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 400px; margin: 100px auto; padding: 20px; }
            input { width: 100%; padding: 10px; margin: 10px 0; }
            button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; cursor: pointer; }
        </style>
    </head>
    <body>
        <h2>Error Log Viewer</h2>
        <form method="POST">
            <input type="password" name="password" placeholder="Enter password" required>
            <button type="submit">Login</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// Show errors
$errorLogFile = __DIR__ . '/contact_errors.log';
$phpErrorLog = ini_get('error_log');
$contactLogFile = __DIR__ . '/contact_logs.txt';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Error Log Viewer</title>
    <style>
        body { font-family: 'Courier New', monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #4ec9b0; }
        h2 { color: #569cd6; margin-top: 30px; }
        .log-section { background: #252526; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .log-content { background: #1e1e1e; padding: 10px; border: 1px solid #3e3e42; border-radius: 3px; max-height: 400px; overflow-y: auto; }
        pre { margin: 0; white-space: pre-wrap; word-wrap: break-word; }
        .error { color: #f48771; }
        .success { color: #4ec9b0; }
        .info { color: #569cd6; }
        .refresh-btn { padding: 10px 20px; background: #007acc; color: white; border: none; cursor: pointer; margin: 10px 5px; }
        .clear-btn { padding: 10px 20px; background: #d32f2f; color: white; border: none; cursor: pointer; margin: 10px 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Error Log Viewer</h1>
        
        <div>
            <button class="refresh-btn" onclick="location.reload()">Refresh</button>
            <a href="?clear=errors" class="clear-btn" style="text-decoration: none; display: inline-block;">Clear Error Log</a>
            <a href="?clear=contact" class="clear-btn" style="text-decoration: none; display: inline-block;">Clear Contact Log</a>
        </div>

        <?php
        // Handle clear requests
        if (isset($_GET['clear'])) {
            if ($_GET['clear'] === 'errors' && file_exists($errorLogFile)) {
                file_put_contents($errorLogFile, '');
                echo '<div class="success">Error log cleared!</div>';
            }
            if ($_GET['clear'] === 'contact' && file_exists($contactLogFile)) {
                file_put_contents($contactLogFile, '');
                echo '<div class="success">Contact log cleared!</div>';
            }
        }
        ?>

        <h2>1. Custom Error Log (contact_errors.log)</h2>
        <div class="log-section">
            <?php
            if (file_exists($errorLogFile)) {
                $errors = file_get_contents($errorLogFile);
                if (!empty($errors)) {
                    echo '<div class="log-content"><pre class="error">' . htmlspecialchars($errors) . '</pre></div>';
                } else {
                    echo '<div class="info">No errors logged yet.</div>';
                }
            } else {
                echo '<div class="info">Error log file does not exist yet. Errors will appear here when they occur.</div>';
            }
            ?>
        </div>

        <h2>2. Contact Form Submissions Log (contact_logs.txt)</h2>
        <div class="log-section">
            <?php
            if (file_exists($contactLogFile)) {
                $logs = file_get_contents($contactLogFile);
                if (!empty($logs)) {
                    echo '<div class="log-content"><pre>' . htmlspecialchars($logs) . '</pre></div>';
                } else {
                    echo '<div class="info">No submissions logged yet.</div>';
                }
            } else {
                echo '<div class="info">Contact log file does not exist yet.</div>';
            }
            ?>
        </div>

        <h2>3. PHP Error Log Location</h2>
        <div class="log-section">
            <div class="info">
                <strong>PHP Error Log Location:</strong><br>
                <?php
                if ($phpErrorLog) {
                    echo htmlspecialchars($phpErrorLog);
                } else {
                    echo 'Using default PHP error log location.';
                }
                ?>
                <br><br>
                <strong>Common locations:</strong><br>
                • Linux: /var/log/php_errors.log or /var/log/apache2/error.log<br>
                • Check PHP config: Run <code>php -i | grep error_log</code> in terminal<br>
                • Or check php.ini: <code>error_log</code> directive
            </div>
        </div>

        <h2>4. Server Information</h2>
        <div class="log-section">
            <div class="info">
                <strong>PHP Version:</strong> <?php echo phpversion(); ?><br>
                <strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?><br>
                <strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?><br>
                <strong>Script Location:</strong> <?php echo __DIR__; ?>
            </div>
        </div>
    </div>
</body>
</html>
