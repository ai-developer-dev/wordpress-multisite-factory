<?php
// Railway-specific WordPress configuration
define('DB_NAME', $_ENV['WORDPRESS_DB_NAME'] ?? 'wordpress');
define('DB_USER', $_ENV['WORDPRESS_DB_USER'] ?? 'root');
define('DB_PASSWORD', $_ENV['WORDPRESS_DB_PASSWORD'] ?? '');
define('DB_HOST', $_ENV['WORDPRESS_DB_HOST'] ?? 'localhost');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

// WordPress URLs
define('WP_HOME', $_ENV['WORDPRESS_HOME'] ?? 'http://localhost');
define('WP_SITEURL', $_ENV['WORDPRESS_SITEURL'] ?? 'http://localhost');

// Security keys (generate new ones)
define('AUTH_KEY', $_ENV['WORDPRESS_AUTH_KEY'] ?? 'your-unique-phrase-here');
define('SECURE_AUTH_KEY', $_ENV['WORDPRESS_SECURE_AUTH_KEY'] ?? 'your-unique-phrase-here');
define('LOGGED_IN_KEY', $_ENV['WORDPRESS_LOGGED_IN_KEY'] ?? 'your-unique-phrase-here');
define('NONCE_KEY', $_ENV['WORDPRESS_NONCE_KEY'] ?? 'your-unique-phrase-here');
define('AUTH_SALT', $_ENV['WORDPRESS_AUTH_SALT'] ?? 'your-unique-phrase-here');
define('SECURE_AUTH_SALT', $_ENV['WORDPRESS_SECURE_AUTH_SALT'] ?? 'your-unique-phrase-here');
define('LOGGED_IN_SALT', $_ENV['WORDPRESS_LOGGED_IN_SALT'] ?? 'your-unique-phrase-here');
define('NONCE_SALT', $_ENV['WORDPRESS_NONCE_SALT'] ?? 'your-unique-phrase-here');

// Multisite configuration
define('WP_ALLOW_MULTISITE', true);
define('MULTISITE', true);
define('SUBDOMAIN_INSTALL', true);
define('DOMAIN_CURRENT_SITE', $_ENV['WORDPRESS_DOMAIN'] ?? 'localhost');
define('PATH_CURRENT_SITE', '/');
define('SITE_ID_CURRENT_SITE', 1);
define('BLOG_ID_CURRENT_SITE', 1);

// Performance and security
define('WP_DEBUG', $_ENV['WORDPRESS_DEBUG'] ?? false);
define('WP_DEBUG_LOG', $_ENV['WORDPRESS_DEBUG_LOG'] ?? false);
define('WP_DEBUG_DISPLAY', $_ENV['WORDPRESS_DEBUG_DISPLAY'] ?? false);
define('WP_MEMORY_LIMIT', $_ENV['WORDPRESS_MEMORY_LIMIT'] ?? '256M');
define('WP_MAX_MEMORY_LIMIT', $_ENV['WORDPRESS_MAX_MEMORY_LIMIT'] ?? '512M');

// File system
define('FS_METHOD', 'direct');

// Auto-updates
define('AUTOMATIC_UPDATER_DISABLED', true);
define('WP_AUTO_UPDATE_CORE', false);

// Database table prefix
$table_prefix = $_ENV['WORDPRESS_TABLE_PREFIX'] ?? 'wp_';

// Load WordPress
require_once __DIR__ . '/wp-settings.php';

if ($_ENV['WORDPRESS_DEBUG'] ?? false) {
    define('WP_DEBUG_LOG', true);
    define('WP_DEBUG_DISPLAY', false);
    ini_set('log_errors', 1);
    ini_set('error_log', '/var/log/php_errors.log');
}