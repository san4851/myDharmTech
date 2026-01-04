<?php

/**
 * Contact Form Handler
 * 
 * Handles form submissions and sends emails
 * Make sure to configure contact_config.php before using
 */

// Set error handler to catch all errors
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    $logFile = __DIR__ . '/contact_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] PHP Error [$errno]: $errstr in $errfile on line $errline\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    error_log($logEntry);
    return false; // Let PHP handle the error normally
});

// Set exception handler
set_exception_handler(function ($exception) {
    $logFile = __DIR__ . '/contact_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    error_log($logEntry);
});

// Set JSON header
header('Content-Type: application/json');

// Load configuration
$config = require_once __DIR__ . '/contact_config.php';

/**
 * Custom error logging function that writes to a file in the same directory
 */
function logError($message, $config = null)
{
    // Log to PHP error log (default location)
    error_log($message);

    // Also log to a custom file in the same directory for easy access
    $logFile = __DIR__ . '/contact_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Response array
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

/**
 * Sanitize input data
 */
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email
 */
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Get client IP address
 */
function getClientIP()
{
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if (isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if (isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if (isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

/**
 * Check rate limit
 */
function checkRateLimit($ip, $config)
{
    if (!$config['rate_limit_enabled']) {
        return true;
    }

    $rateLimitFile = __DIR__ . '/rate_limit_' . md5($ip) . '.json';
    $currentTime = time();
    $oneHourAgo = $currentTime - 3600;

    // Read existing data
    $submissions = [];
    if (file_exists($rateLimitFile)) {
        $data = json_decode(file_get_contents($rateLimitFile), true);
        if ($data && isset($data['submissions'])) {
            // Filter out submissions older than 1 hour
            $submissions = array_filter($data['submissions'], function ($timestamp) use ($oneHourAgo) {
                return $timestamp > $oneHourAgo;
            });
        }
    }

    // Check if limit exceeded
    if (count($submissions) >= $config['rate_limit_max']) {
        return false;
    }

    // Add current submission
    $submissions[] = $currentTime;
    file_put_contents($rateLimitFile, json_encode(['submissions' => array_values($submissions)]));

    return true;
}

/**
 * Log submission
 */
function logSubmission($data, $config)
{
    if (!$config['enable_logging']) {
        return;
    }

    $logFile = __DIR__ . '/' . $config['log_file'];
    $timestamp = date('Y-m-d H:i:s');
    $ip = getClientIP();
    $logEntry = "[$timestamp] IP: $ip | Name: {$data['name']} | Email: {$data['email']} | Service: {$data['service']}\n";

    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Generate HTML email content
 */
function generateHTMLEmail($data)
{
    $name = htmlspecialchars($data['name']);
    $email = htmlspecialchars($data['email']);
    $phone = htmlspecialchars($data['phone'] ?? 'Not provided');
    $service = htmlspecialchars($data['service']);
    $message = nl2br(htmlspecialchars($data['message']));
    $submitted = htmlspecialchars($data['submitted']);
    $ip = htmlspecialchars($data['ip']);

    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Form Submission</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f4f4f4;">
        <tr>
            <td style="padding: 20px 0;">
                <table role="presentation" style="width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">New Contact Form Submission</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px;">
                            <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb;">
                                        <strong style="color: #374151; font-size: 14px; display: inline-block; width: 140px;">Name:</strong>
                                        <span style="color: #111827; font-size: 14px;">' . $name . '</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb;">
                                        <strong style="color: #374151; font-size: 14px; display: inline-block; width: 140px;">Email:</strong>
                                        <a href="mailto:' . $email . '" style="color: #6366f1; text-decoration: none; font-size: 14px;">' . $email . '</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb;">
                                        <strong style="color: #374151; font-size: 14px; display: inline-block; width: 140px;">Phone:</strong>
                                        <span style="color: #111827; font-size: 14px;">' . $phone . '</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb;">
                                        <strong style="color: #374151; font-size: 14px; display: inline-block; width: 140px;">Service:</strong>
                                        <span style="color: #111827; font-size: 14px; background-color: #eef2ff; padding: 4px 12px; border-radius: 4px; display: inline-block;">' . $service . '</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 20px 0;">
                                        <strong style="color: #374151; font-size: 14px; display: block; margin-bottom: 10px;">Message:</strong>
                                        <div style="background-color: #f9fafb; padding: 15px; border-radius: 6px; border-left: 4px solid #6366f1; color: #111827; font-size: 14px; line-height: 1.6;">' . $message . '</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px 30px; border-radius: 0 0 8px 8px; border-top: 1px solid #e5e7eb;">
                            <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 5px 0; color: #6b7280; font-size: 12px;">
                                        <strong>Submitted:</strong> ' . $submitted . '
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px 0; color: #6b7280; font-size: 12px;">
                                        <strong>IP Address:</strong> ' . $ip . '
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                
                <!-- Footer Text -->
                <table role="presentation" style="width: 600px; margin: 20px auto 0;">
                    <tr>
                        <td style="text-align: center; color: #9ca3af; font-size: 12px; padding: 20px;">
                            <p style="margin: 0;">This email was sent from the contact form on myDharm Technologies website.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

    return $html;
}

/**
 * Generate plain text email content (fallback)
 */
function generatePlainTextEmail($data)
{
    $text = "New Contact Form Submission\n";
    $text .= str_repeat("=", 50) . "\n\n";
    $text .= "Name: " . $data['name'] . "\n";
    $text .= "Email: " . $data['email'] . "\n";
    $text .= "Phone: " . ($data['phone'] ?? 'Not provided') . "\n";
    $text .= "Service: " . $data['service'] . "\n\n";
    $text .= "Message:\n";
    $text .= str_repeat("-", 50) . "\n";
    $text .= $data['message'] . "\n";
    $text .= str_repeat("-", 50) . "\n\n";
    $text .= "Submitted: " . $data['submitted'] . "\n";
    $text .= "IP Address: " . $data['ip'] . "\n";

    return $text;
}

/**
 * Send email using PHP mail() function with HTML support
 */
function sendEmail($to, $subject, $htmlMessage, $fromEmail, $fromName, $replyTo = null, $plainTextMessage = null)
{
    // Generate boundary for multipart email
    $boundary = md5(uniqid(time()));

    // Create plain text version if not provided
    if (!$plainTextMessage) {
        // Simple plain text extraction from HTML
        $plainTextMessage = strip_tags($htmlMessage);
        $plainTextMessage = html_entity_decode($plainTextMessage, ENT_QUOTES, 'UTF-8');
    }

    // Build headers
    $headers = [];
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "From: $fromName <$fromEmail>";

    if ($replyTo) {
        $headers[] = "Reply-To: $replyTo";
    }

    $headers[] = "X-Mailer: PHP/" . phpversion();
    $headers[] = "X-Priority: 3";
    $headers[] = "Message-ID: <" . time() . "." . md5($to . $subject) . "@" . parse_url($fromEmail, PHP_URL_HOST) . ">";
    $headers[] = "Date: " . date('r');
    $headers[] = "Content-Type: multipart/alternative; boundary=\"$boundary\"";

    // Build email body with multipart
    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $plainTextMessage . "\r\n\r\n";

    $body .= "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $htmlMessage . "\r\n\r\n";

    $body .= "--$boundary--";

    return mail($to, $subject, $body, implode("\r\n", $headers));
}

/**
 * Read multi-line SMTP response
 */
function readSMTPResponse($socket)
{
    $lastLine = '';

    // Read lines until we get the final response (no hyphen after code)
    while ($line = @fgets($socket, 515)) {
        $lastLine = trim($line);

        // Check if this is the last line (doesn't start with code followed by hyphen)
        if (strlen($lastLine) >= 3) {
            // If line is exactly 3 chars or doesn't have hyphen after code, it's the final line
            if (strlen($lastLine) == 3 || substr($lastLine, 3, 1) != '-') {
                break;
            }
        } else {
            // If line is shorter than 3 chars, it might be the end
            break;
        }
    }

    return $lastLine;
}

/**
 * Send email using SMTP
 */
function sendEmailSMTP($to, $subject, $message, $config, $replyTo = null)
{
    try {
        // Create socket connection
        $host = $config['smtp_host'];
        $port = $config['smtp_port'];
        $timeout = 30;
        $encryption = strtolower($config['smtp_encryption']);

        // Build connection string
        $connectionString = $host;
        if ($encryption == 'ssl') {
            $connectionString = "ssl://{$host}";
            $socket = @fsockopen($connectionString, $port, $errno, $errstr, $timeout);
        } else {
            $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        }

        if (!$socket) {
            $errorMsg = "SMTP Connection failed to {$host}:{$port} - $errstr ($errno)";
            logError($errorMsg, $config);
            return false;
        }

        // Set timeout
        stream_set_timeout($socket, $timeout);

        // Read server greeting
        $response = readSMTPResponse($socket);
        if (empty($response) || strlen($response) < 3 || substr($response, 0, 3) != '220') {
            logError("SMTP Greeting Error: " . ($response ?: 'No response'), $config);
            fclose($socket);
            return false;
        }

        // Send EHLO
        fputs($socket, "EHLO " . $host . "\r\n");
        $response = readSMTPResponse($socket);
        if (empty($response)) {
            logError("SMTP EHLO Error: No response", $config);
            fclose($socket);
            return false;
        }

        // Start TLS if encryption is TLS (not SSL)
        if ($encryption == 'tls') {
            fputs($socket, "STARTTLS\r\n");
            $response = readSMTPResponse($socket);
            if (empty($response) || strlen($response) < 3 || substr($response, 0, 3) != '220') {
                logError("SMTP STARTTLS Error: " . ($response ?: 'No response'), $config);
                fclose($socket);
                return false;
            }

            if (substr($response, 0, 3) == '220') {
                $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                    $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                }
                if (!@stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                    logError("SMTP TLS handshake failed", $config);
                    fclose($socket);
                    return false;
                }
                fputs($socket, "EHLO " . $host . "\r\n");
                $response = readSMTPResponse($socket);
            } else {
                logError("SMTP STARTTLS Error: $response", $config);
                fclose($socket);
                return false;
            }
        }

        // Authenticate
        fputs($socket, "AUTH LOGIN\r\n");
        $response = readSMTPResponse($socket);
        if (substr($response, 0, 3) != '334') {
            logError("SMTP Auth Error: $response", $config);
            fclose($socket);
            return false;
        }

        fputs($socket, base64_encode($config['smtp_username']) . "\r\n");
        $response = readSMTPResponse($socket);
        if (substr($response, 0, 3) != '334') {
            logError("SMTP Auth Username Error: $response", $config);
            fclose($socket);
            return false;
        }

        fputs($socket, base64_encode($config['smtp_password']) . "\r\n");
        $response = readSMTPResponse($socket);
        if (substr($response, 0, 3) != '235') {
            logError("SMTP Auth Password Error: $response", $config);
            fclose($socket);
            return false;
        }

        // Set sender
        fputs($socket, "MAIL FROM: <" . $config['from_email'] . ">\r\n");
        $response = readSMTPResponse($socket);
        if (substr($response, 0, 3) != '250') {
            logError("SMTP MAIL FROM Error: $response", $config);
            fclose($socket);
            return false;
        }

        // Set recipient
        fputs($socket, "RCPT TO: <" . $to . ">\r\n");
        $response = readSMTPResponse($socket);
        if (substr($response, 0, 3) != '250') {
            logError("SMTP RCPT TO Error: $response", $config);
            fclose($socket);
            return false;
        }

        // Send data
        fputs($socket, "DATA\r\n");
        $response = readSMTPResponse($socket);
        if (substr($response, 0, 3) != '354') {
            logError("SMTP DATA Error: $response", $config);
            fclose($socket);
            return false;
        }

        // Generate boundary for multipart email
        $boundary = md5(uniqid(time()));

        // Create plain text version
        $plainTextMessage = strip_tags($message);
        $plainTextMessage = html_entity_decode($plainTextMessage, ENT_QUOTES, 'UTF-8');

        // Build email headers
        $headers = "From: " . $config['from_name'] . " <" . $config['from_email'] . ">\r\n";
        $headers .= "To: <" . $to . ">\r\n";
        if ($replyTo) {
            $headers .= "Reply-To: <" . $replyTo . ">\r\n";
        }
        $headers .= "Subject: " . $subject . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers .= "X-Priority: 3\r\n";
        $headers .= "Message-ID: <" . time() . "." . md5($to . $subject) . "@" . parse_url($config['from_email'], PHP_URL_HOST) . ">\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
        $headers .= "\r\n";

        // Build multipart email body
        $emailBody = "--$boundary\r\n";
        $emailBody .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $emailBody .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $emailBody .= $plainTextMessage . "\r\n\r\n";

        $emailBody .= "--$boundary\r\n";
        $emailBody .= "Content-Type: text/html; charset=UTF-8\r\n";
        $emailBody .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $emailBody .= $message . "\r\n\r\n";

        $emailBody .= "--$boundary--";

        // Send email content
        fputs($socket, $headers . $emailBody . "\r\n.\r\n");
        $response = readSMTPResponse($socket);
        if (substr($response, 0, 3) != '250') {
            logError("SMTP Send Error: $response", $config);
            fclose($socket);
            return false;
        }

        // Quit
        fputs($socket, "QUIT\r\n");
        fclose($socket);

        return true;
    } catch (Exception $e) {
        $errorMsg = "SMTP Exception: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine();
        logError($errorMsg, $config);
        if (isset($socket) && is_resource($socket)) {
            @fclose($socket);
        }
        return false;
    } catch (Error $e) {
        $errorMsg = "SMTP Error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine();
        logError($errorMsg, $config);
        if (isset($socket) && is_resource($socket)) {
            @fclose($socket);
        }
        return false;
    }
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

// Get and sanitize form data
$name = isset($_POST['name']) ? sanitizeInput($_POST['name']) : '';
$email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
$phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';
$service = isset($_POST['service']) ? sanitizeInput($_POST['service']) : '';
$message = isset($_POST['message']) ? sanitizeInput($_POST['message']) : '';
$honeypot = isset($_POST[$config['honeypot_field']]) ? $_POST[$config['honeypot_field']] : '';

// Validation
$errors = [];

// Check honeypot (spam protection)
if ($config['honeypot_enabled'] && !empty($honeypot)) {
    $response['message'] = 'Spam detected';
    echo json_encode($response);
    exit;
}

// Validate name
if (empty($name)) {
    $errors['name'] = 'Name is required';
} elseif (strlen($name) < 2) {
    $errors['name'] = 'Name must be at least 2 characters';
} elseif (strlen($name) > 100) {
    $errors['name'] = 'Name is too long';
}

// Validate email
if (empty($email)) {
    $errors['email'] = 'Email is required';
} elseif (!validateEmail($email)) {
    $errors['email'] = 'Invalid email address';
}

// Validate phone (optional but if provided, should be valid)
if (!empty($phone) && strlen($phone) > 20) {
    $errors['phone'] = 'Phone number is too long';
}

// Validate service
if (!empty($service) && !in_array($service, $config['allowed_services'])) {
    $errors['service'] = 'Invalid service selected';
}

// Validate message
if (empty($message)) {
    $errors['message'] = 'Message is required';
} elseif (strlen($message) < 10) {
    $errors['message'] = 'Message must be at least 10 characters';
} elseif (strlen($message) > 2000) {
    $errors['message'] = 'Message is too long (max 2000 characters)';
}

// If there are validation errors
if (!empty($errors)) {
    $response['errors'] = $errors;
    $response['message'] = 'Please correct the errors below';
    echo json_encode($response);
    exit;
}

// Check rate limit
$clientIP = getClientIP();
if (!checkRateLimit($clientIP, $config)) {
    $response['message'] = 'Too many submissions. Please try again later.';
    echo json_encode($response);
    exit;
}

// Prepare email content
$serviceName = isset($config['service_names'][$service]) ? $config['service_names'][$service] : ucfirst($service);
$subject = $config['subject_prefix'] . ' ' . $serviceName . ' - ' . $name;

// Prepare data for email
$emailData = [
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'service' => $serviceName,
    'message' => $message,
    'submitted' => date('Y-m-d H:i:s'),
    'ip' => $clientIP
];

// Generate HTML email
$htmlEmailBody = generateHTMLEmail($emailData);
$plainTextEmailBody = generatePlainTextEmail($emailData);

// Send email to recipient
$emailSent = false;
$errorMessage = '';

if ($config['smtp_enabled']) {
    $emailSent = sendEmailSMTP($config['recipient_email'], $subject, $htmlEmailBody, $config, $email);
    if (!$emailSent) {
        $errorMessage = 'SMTP sending failed. Check server logs for details.';
        logError("Contact form SMTP error: Failed to send email to {$config['recipient_email']}", $config);

        // Fallback to mail() if SMTP fails
        logError("Attempting fallback to mail() function", $config);
        $emailSent = sendEmail(
            $config['recipient_email'],
            $subject,
            $htmlEmailBody,
            $config['from_email'],
            $config['from_name'],
            $email,
            $plainTextEmailBody
        );
        if ($emailSent) {
            logError("Fallback mail() succeeded", $config);
        } else {
            logError("Fallback mail() also failed", $config);
        }
    }
} else {
    $emailSent = sendEmail(
        $config['recipient_email'],
        $subject,
        $htmlEmailBody,
        $config['from_email'],
        $config['from_name'],
        $email,
        $plainTextEmailBody
    );
    if (!$emailSent) {
        $errorMessage = 'Email sending failed. Check server configuration.';
        logError("Contact form mail() error: Failed to send email to {$config['recipient_email']}", $config);
    }
}

// Send auto-reply to sender if enabled
if ($config['send_copy_to_sender'] && $emailSent) {
    $autoReplyText = str_replace('{name}', $name, $config['auto_reply_message']);

    // Generate HTML auto-reply
    $autoReplyHTML = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f4f4f4;">
        <tr>
            <td style="padding: 20px 0;">
                <table role="presentation" style="width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">Thank You for Contacting Us</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 30px;">
                            <p style="color: #111827; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">Dear ' . htmlspecialchars($name) . ',</p>
                            <div style="color: #374151; font-size: 15px; line-height: 1.8;">
                                ' . nl2br(htmlspecialchars($autoReplyText)) . '
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px 30px; border-radius: 0 0 8px 8px; border-top: 1px solid #e5e7eb; text-align: center;">
                            <p style="margin: 0; color: #6b7280; font-size: 12px;">myDharm Technologies</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

    sendEmail(
        $email,
        $config['auto_reply_subject'],
        $autoReplyHTML,
        $config['from_email'],
        $config['from_name'],
        null,
        $autoReplyText
    );
}

if ($emailSent) {
    // Log successful submission
    logSubmission([
        'name' => $name,
        'email' => $email,
        'service' => $serviceName
    ], $config);

    $response['success'] = true;
    $response['message'] = 'Thank you! Your message has been sent successfully. We\'ll get back to you soon.';
} else {
    // Log the error for debugging (but don't expose sensitive info to user)
    $logError = "Failed to send email. ";
    if ($config['smtp_enabled']) {
        $logError .= "SMTP: {$config['smtp_host']}:{$config['smtp_port']}";
    }
    logError($logError, $config);

    $response['message'] = 'Sorry, there was an error sending your message. Please try again later or contact us directly at ' . $config['recipient_email'];

    // Add debug info if debug mode is enabled
    if (isset($config['debug_mode']) && $config['debug_mode']) {
        $response['debug'] = [
            'smtp_enabled' => $config['smtp_enabled'],
            'smtp_host' => $config['smtp_host'],
            'smtp_port' => $config['smtp_port'],
            'error_message' => $errorMessage ?? 'Unknown error'
        ];
    }
}

// Wrap output in try-catch to catch any final errors
try {
    echo json_encode($response);
} catch (Exception $e) {
    logError("JSON encode error: " . $e->getMessage(), $config);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred processing your request.'
    ]);
} catch (Error $e) {
    logError("Fatal error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine(), $config);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred processing your request.'
    ]);
}
