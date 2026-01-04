<?php

/**
 * SMTP Test Script
 * Use this to test your SMTP configuration
 */

/**
 * Read multi-line SMTP response
 */
function readSMTPResponse($socket)
{
    $response = '';
    $lastLine = '';

    while ($line = fgets($socket, 515)) {
        $response .= $line;
        $lastLine = trim($line);

        // Check if this is the last line (doesn't start with code followed by hyphen)
        if (strlen($lastLine) >= 3) {
            $code = substr($lastLine, 0, 3);
            // If line doesn't have hyphen after code, it's the final line
            if (strlen($lastLine) == 3 || substr($lastLine, 3, 1) != '-') {
                break;
            }
        }
    }

    return ['full' => $response, 'last' => $lastLine];
}

$config = require __DIR__ . '/contact_config.php';

echo "<h2>SMTP Configuration Test</h2>";
echo "<pre>";
echo "Host: " . $config['smtp_host'] . "\n";
echo "Port: " . $config['smtp_port'] . "\n";
echo "Encryption: " . $config['smtp_encryption'] . "\n";
echo "Username: " . $config['smtp_username'] . "\n";
echo "Password: " . (strlen($config['smtp_password']) > 0 ? str_repeat('*', strlen($config['smtp_password'])) : 'NOT SET') . "\n";
echo "From Email: " . $config['from_email'] . "\n";
echo "Recipient Email: " . $config['recipient_email'] . "\n";
echo "\n";

// Test connection
$host = $config['smtp_host'];
$port = $config['smtp_port'];
$encryption = strtolower($config['smtp_encryption']);

echo "Testing connection...\n";
echo "----------------------------------------\n";

if ($encryption == 'ssl') {
    $connectionString = "ssl://{$host}";
    $socket = @fsockopen($connectionString, $port, $errno, $errstr, 10);
} else {
    $socket = @fsockopen($host, $port, $errno, $errstr, 10);
}

if (!$socket) {
    echo "ERROR: Connection failed!\n";
    echo "Error: $errstr ($errno)\n";
    echo "\nPossible issues:\n";
    echo "- Check if host and port are correct\n";
    echo "- Check firewall settings\n";
    echo "- Verify SSL/TLS settings\n";
    exit;
}

echo "✓ Connection successful!\n";

// Read greeting
$responseData = readSMTPResponse($socket);
$response = $responseData['last'];
echo "Server greeting: " . trim($responseData['full']) . "\n";

if (substr($response, 0, 3) != '220') {
    echo "ERROR: Invalid server greeting\n";
    fclose($socket);
    exit;
}

// Send EHLO
fputs($socket, "EHLO " . $host . "\r\n");
$responseData = readSMTPResponse($socket);
$response = $responseData['last'];
echo "EHLO response:\n" . trim($responseData['full']) . "\n";

// Test TLS if needed
if ($encryption == 'tls') {
    echo "\nTesting STARTTLS...\n";
    fputs($socket, "STARTTLS\r\n");
    $responseData = readSMTPResponse($socket);
    $response = $responseData['last'];
    echo "STARTTLS response:\n" . trim($responseData['full']) . "\n";

    if (substr($response, 0, 3) == '220') {
        $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        }
        if (@stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
            echo "✓ TLS handshake successful!\n";
            fputs($socket, "EHLO " . $host . "\r\n");
            $responseData = readSMTPResponse($socket);
            $response = $responseData['last'];
            echo "EHLO after TLS:\n" . trim($responseData['full']) . "\n";
        } else {
            echo "ERROR: TLS handshake failed\n";
            fclose($socket);
            exit;
        }
    } else {
        echo "ERROR: STARTTLS not supported\n";
        fclose($socket);
        exit;
    }
}

// Test authentication
echo "\nTesting authentication...\n";
fputs($socket, "AUTH LOGIN\r\n");
$responseData = readSMTPResponse($socket);
$response = $responseData['last'];
echo "AUTH LOGIN response: " . trim($response) . "\n";

if (substr($response, 0, 3) != '334') {
    echo "ERROR: AUTH LOGIN not accepted\n";
    fclose($socket);
    exit;
}

fputs($socket, base64_encode($config['smtp_username']) . "\r\n");
$responseData = readSMTPResponse($socket);
$response = $responseData['last'];
echo "Username response: " . trim($response) . "\n";

if (substr($response, 0, 3) != '334') {
    echo "ERROR: Username not accepted\n";
    fclose($socket);
    exit;
}

fputs($socket, base64_encode($config['smtp_password']) . "\r\n");
$responseData = readSMTPResponse($socket);
$response = $responseData['last'];
echo "Password response: " . trim($response) . "\n";

if (substr($response, 0, 3) != '235') {
    echo "ERROR: Authentication failed!\n";
    echo "Check your username and password\n";
    fclose($socket);
    exit;
}

echo "✓ Authentication successful!\n";

// Quit
fputs($socket, "QUIT\r\n");
fclose($socket);

echo "\n----------------------------------------\n";
echo "✓ All tests passed! SMTP configuration is working.\n";
echo "</pre>";
