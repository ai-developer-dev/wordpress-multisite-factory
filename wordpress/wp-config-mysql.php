<?php
// Simple MySQL WordPress configuration for Railway

// Database from Railway DATABASE_URL (MYSQL ONLY)
$database_url = getenv('DATABASE_URL');
if ($database_url) {
    $db_parts = parse_url($database_url);
    define('DB_NAME', ltrim($db_parts['path'], '/'));
    define('DB_USER', $db_parts['user']);
    define('DB_PASSWORD', $db_parts['pass']);
    define('DB_HOST', $db_parts['host'] . ':' . ($db_parts['port'] ?: 3306));
} else {
    // Fallback
    define('DB_NAME', 'wordpress');
    define('DB_USER', 'root');  
    define('DB_PASSWORD', '');
    define('DB_HOST', 'localhost');
}

define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

// WordPress security keys
define('AUTH_KEY',         'your-unique-phrase-here');
define('SECURE_AUTH_KEY',  'your-unique-phrase-here');
define('LOGGED_IN_KEY',    'your-unique-phrase-here');
define('NONCE_KEY',        'your-unique-phrase-here');
define('AUTH_SALT',        'your-unique-phrase-here');
define('SECURE_AUTH_SALT', 'your-unique-phrase-here');
define('LOGGED_IN_SALT',   'your-unique-phrase-here');
define('NONCE_SALT',       'your-unique-phrase-here');

$table_prefix = 'wp_';

define('WP_DEBUG', false);

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require_once ABSPATH . 'wp-settings.php';