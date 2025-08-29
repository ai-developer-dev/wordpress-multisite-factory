<?php
/**
 * Railway-optimized WordPress Configuration File
 * 
 * This file contains Railway-specific configurations for WordPress
 * multisite deployment with automatic database connection handling.
 */

// Railway database configuration from DATABASE_URL environment variable
$database_url = getenv('DATABASE_URL');
if ($database_url) {
    $db_parts = parse_url($database_url);
    
    define('DB_NAME', ltrim($db_parts['path'], '/') ?: 'wordpress');
    define('DB_USER', $db_parts['user']);
    define('DB_PASSWORD', $db_parts['pass']);
    define('DB_HOST', $db_parts['host'] . ':' . ($db_parts['port'] ?: 3306));
} else {
    // Fallback for local development
    define('DB_NAME', getenv('WORDPRESS_DB_NAME') ?: 'wordpress');
    define('DB_USER', getenv('WORDPRESS_DB_USER') ?: 'root');
    define('DB_PASSWORD', getenv('WORDPRESS_DB_PASSWORD') ?: '');
    define('DB_HOST', getenv('WORDPRESS_DB_HOST') ?: 'localhost');
}

define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

// WordPress multisite configuration
define('WP_ALLOW_MULTISITE', true);
define('MULTISITE', true);
define('SUBDOMAIN_INSTALL', false); // Use subdirectory install for Railway
define('DOMAIN_CURRENT_SITE', $_SERVER['HTTP_HOST'] ?? getenv('RAILWAY_PUBLIC_DOMAIN') ?? 'localhost');
define('PATH_CURRENT_SITE', '/');
define('SITE_ID_CURRENT_SITE', 1);
define('BLOG_ID_CURRENT_SITE', 1);

// Security keys from environment variables
define('AUTH_KEY',         getenv('WORDPRESS_AUTH_KEY') ?: 'put your unique phrase here');
define('SECURE_AUTH_KEY',  getenv('WORDPRESS_SECURE_AUTH_KEY') ?: 'put your unique phrase here');
define('LOGGED_IN_KEY',    getenv('WORDPRESS_LOGGED_IN_KEY') ?: 'put your unique phrase here');
define('NONCE_KEY',        getenv('WORDPRESS_NONCE_KEY') ?: 'put your unique phrase here');
define('AUTH_SALT',        getenv('WORDPRESS_AUTH_SALT') ?: 'put your unique phrase here');
define('SECURE_AUTH_SALT', getenv('WORDPRESS_SECURE_AUTH_SALT') ?: 'put your unique phrase here');
define('LOGGED_IN_SALT',   getenv('WORDPRESS_LOGGED_IN_SALT') ?: 'put your unique phrase here');
define('NONCE_SALT',       getenv('WORDPRESS_NONCE_SALT') ?: 'put your unique phrase here');

// WordPress database table prefix
$table_prefix = getenv('WORDPRESS_TABLE_PREFIX') ?: 'wp_';

// File system permissions for Railway
define('FS_METHOD', 'direct');
define('FS_CHMOD_DIR', (0755 & ~ umask()));
define('FS_CHMOD_FILE', (0644 & ~ umask()));

// WordPress debugging
define('WP_DEBUG', getenv('WORDPRESS_DEBUG') === 'true');
define('WP_DEBUG_LOG', getenv('WORDPRESS_DEBUG_LOG') === 'true');
define('WP_DEBUG_DISPLAY', false);

// Memory and execution limits
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '300');

// SSL and security for Railway
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
    define('FORCE_SSL_ADMIN', true);
}

// Site Factory plugin configuration
define('SF_API_ENABLED', true);
define('SF_MAX_SITES_PER_REQUEST', 1);
define('SF_API_RATE_LIMIT', 10); // requests per minute
define('SF_LOG_LEVEL', getenv('SF_LOG_LEVEL') ?: 'info');

// WordPress automatic updates
define('WP_AUTO_UPDATE_CORE', 'minor');
define('AUTOMATIC_UPDATER_DISABLED', false);

// Redis object cache (if available)
if (getenv('REDIS_URL')) {
    define('WP_REDIS_HOST', parse_url(getenv('REDIS_URL'), PHP_URL_HOST));
    define('WP_REDIS_PORT', parse_url(getenv('REDIS_URL'), PHP_URL_PORT));
    define('WP_REDIS_PASSWORD', parse_url(getenv('REDIS_URL'), PHP_URL_PASS));
    define('WP_CACHE', true);
}

// WordPress absolute path
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

// Load WordPress
require_once ABSPATH . 'wp-settings.php';