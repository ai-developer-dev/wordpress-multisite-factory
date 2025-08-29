<?php
// Simple WordPress configuration for Railway

// Database configuration from Railway's DATABASE_URL
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

// Security keys - use defaults for now
define('AUTH_KEY',         'put your unique phrase here');
define('SECURE_AUTH_KEY',  'put your unique phrase here');
define('LOGGED_IN_KEY',    'put your unique phrase here');
define('NONCE_KEY',        'put your unique phrase here');
define('AUTH_SALT',        'put your unique phrase here');
define('SECURE_AUTH_SALT', 'put your unique phrase here');
define('LOGGED_IN_SALT',   'put your unique phrase here');
define('NONCE_SALT',       'put your unique phrase here');

$table_prefix = 'wp_';

define('WP_DEBUG', false);

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require_once ABSPATH . 'wp-settings.php';