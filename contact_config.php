<?php

/**
 * Contact Form Configuration
 * 
 * Configure your email settings here
 */

return [
    // Recipient email address (where form submissions will be sent)
    'recipient_email' => 'san4851@gmail.com',

    // From email address (sender)
    'from_email' => 'tech@mydharm.com',

    // From name
    'from_name' => 'myDharm Technologies Contact Form',

    // Email subject prefix
    'subject_prefix' => '[Contact Form]',

    // Enable email copy to sender (auto-reply)
    'send_copy_to_sender' => false,

    // Auto-reply subject
    'auto_reply_subject' => 'Thank you for contacting myDharm Technologies',

    // Auto-reply message
    'auto_reply_message' => "Dear {name},\n\nThank you for contacting myDharm Technologies. We have received your message and will get back to you within 24-48 hours.\n\nBest regards,\nmyDharm Technologies Team",

    // Enable rate limiting (prevent spam)
    'rate_limit_enabled' => true,

    // Rate limit: max submissions per hour per IP
    'rate_limit_max' => 13,

    // Enable honeypot spam protection
    'honeypot_enabled' => true,

    // Honeypot field name (should be hidden from users)
    'honeypot_field' => 'website',

    // Enable logging
    'enable_logging' => true,

    // Log file path (relative to this file)
    'log_file' => 'contact_logs.txt',

    // Allowed services (for validation)
    'allowed_services' => [
        'web-development',
        'ecommerce',
        'wordpress',
        'mobile-app',
        'training',
        'other'
    ],

    // Service display names
    'service_names' => [
        'web-development' => 'Web Development',
        'ecommerce' => 'E-commerce Website',
        'wordpress' => 'WordPress Development',
        'mobile-app' => 'Mobile Application',
        'training' => 'IT Training',
        'other' => 'Other'
    ],

    // SMTP Configuration (optional - if you want to use SMTP instead of mail())
    'smtp_enabled' => true,
    'smtp_host' => 'mail.mydharm.com',
    'smtp_port' => 587,
    'smtp_username' => 'tech@mydharm.com',
    'smtp_password' => 'New#854ed#za#',
    'smtp_encryption' => 'tls', // 'tls' or 'ssl'

    // Debug mode (set to true to see detailed error messages - disable in production!)
    'debug_mode' => true,
];
