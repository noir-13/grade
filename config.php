<?php
// Configuration File
// Loads environment variables from .env file

$envFile = __DIR__ . '/.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) continue;
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Remove quotes if present
            $value = trim($value, '"\'');
            
            if (!defined($name)) {
                define($name, $value);
            }
        }
    }
}

// Fallback / Defaults (Optional: can be removed if strict .env usage is desired)
defined('DB_HOST') or define('DB_HOST', 'localhost');
defined('DB_USER') or define('DB_USER', 'root');
defined('DB_PASS') or define('DB_PASS', '');
defined('DB_NAME') or define('DB_NAME', 'kld_grading_system');

defined('SMTP_HOST') or define('SMTP_HOST', 'smtp.gmail.com');
defined('SMTP_PORT') or define('SMTP_PORT', 587);
// No default for sensitive credentials to ensure .env is used
defined('SMTP_USER') or define('SMTP_USER', '');
defined('SMTP_PASS') or define('SMTP_PASS', '');
defined('SMTP_FROM_EMAIL') or define('SMTP_FROM_EMAIL', '');
defined('SMTP_FROM_NAME') or define('SMTP_FROM_NAME', 'KLD Grade System');

