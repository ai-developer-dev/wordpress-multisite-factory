<?php
// WordPress configuration for Railway with PostgreSQL

// Database configuration from Railway's DATABASE_URL (PostgreSQL)
$database_url = getenv('DATABASE_URL');
if ($database_url) {
    $db_parts = parse_url($database_url);
    
    // Auto-detect database type from URL scheme
    $scheme = $db_parts['scheme'] ?? '';
    
    if ($scheme === 'postgres' || $scheme === 'postgresql') {
        // PostgreSQL configuration
        define('DB_NAME', ltrim($db_parts['path'], '/'));
        define('DB_USER', $db_parts['user']);
        define('DB_PASSWORD', $db_parts['pass']);
        define('DB_HOST', $db_parts['host'] . ':' . ($db_parts['port'] ?: 5432));
        
        // Use PostgreSQL for WordPress (requires pg4wp plugin or custom handling)
        define('DB_TYPE', 'postgresql');
    } else {
        // MySQL configuration (fallback)
        define('DB_NAME', ltrim($db_parts['path'], '/'));
        define('DB_USER', $db_parts['user']);
        define('DB_PASSWORD', $db_parts['pass']);
        define('DB_HOST', $db_parts['host'] . ':' . ($db_parts['port'] ?: 3306));
        define('DB_TYPE', 'mysql');
    }
} else {
    // Local development fallback
    define('DB_NAME', 'wordpress');
    define('DB_USER', 'postgres');
    define('DB_PASSWORD', '');
    define('DB_HOST', 'localhost:5432');
    define('DB_TYPE', 'postgresql');
}

define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

// Security keys
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