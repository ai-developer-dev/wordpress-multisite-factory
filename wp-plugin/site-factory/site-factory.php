<?php
/**
 * Plugin Name: Site Factory
 * Plugin URI: https://github.com/your-org/wordpress-multisite-factory
 * Description: Automatically create WordPress sub-sites with blueprints via REST API
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Network: true
 * Text Domain: site-factory
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SF_VERSION', '1.0.0');
define('SF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SF_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    $prefix = 'SiteFactory\\';
    $base_dir = SF_PLUGIN_DIR . 'includes/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . 'class-' . str_replace('\\', '/', strtolower(str_replace('_', '-', $relative_class))) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Initialize plugin
function sf_init() {
    // Only run on multisite
    if (!is_multisite()) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>Site Factory requires WordPress Multisite to be enabled.</p></div>';
        });
        return;
    }
    
    // Initialize components
    new SiteFactory\SF_REST();
    new SiteFactory\SF_Blueprint();
    new SiteFactory\SF_Users();
    new SiteFactory\SF_Logger();
    new SiteFactory\SF_Security();
    
    // Add admin menu
    add_action('network_admin_menu', 'sf_admin_menu');
}
add_action('init', 'sf_init');

// Admin menu
function sf_admin_menu() {
    add_submenu_page(
        'settings.php',
        'Site Factory',
        'Site Factory',
        'manage_network_options',
        'site-factory',
        'sf_admin_page'
    );
}

// Admin page
function sf_admin_page() {
    include SF_PLUGIN_DIR . 'admin/admin-page.php';
}

// Activation hook
register_activation_hook(__FILE__, 'sf_activate');
function sf_activate() {
    // Create uploads directory
    $upload_dir = wp_upload_dir();
    $sf_log_dir = $upload_dir['basedir'] . '/site-factory-logs';
    
    if (!file_exists($sf_log_dir)) {
        wp_mkdir_p($sf_log_dir);
    }
    
    // Create .htaccess to protect logs
    $htaccess_content = "Order deny,allow\nDeny from all";
    file_put_contents($sf_log_dir . '/.htaccess', $htaccess_content);
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'sf_deactivate');
function sf_deactivate() {
    flush_rewrite_rules();
}

