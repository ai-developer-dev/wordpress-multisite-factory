<?php

namespace SiteFactory;

/**
 * Handle logging for Site Factory activities
 */
class SF_Logger {
    
    private $log_dir;
    private $log_file;
    
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_dir = $upload_dir['basedir'] . '/site-factory-logs';
        $this->log_file = $this->log_dir . '/' . date('Y-m') . '/site-factory.log';
        
        // Ensure log directory exists
        $this->ensure_log_directory();
    }
    
    /**
     * Ensure log directory exists
     */
    private function ensure_log_directory() {
        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);
        }
        
        $month_dir = dirname($this->log_file);
        if (!file_exists($month_dir)) {
            wp_mkdir_p($month_dir);
        }
        
        // Create .htaccess to protect logs
        $htaccess_file = $this->log_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "Order deny,allow\nDeny from all";
            file_put_contents($htaccess_file, $htaccess_content);
        }
    }
    
    /**
     * Log site creation
     */
    public function log_site_creation($site_id, $domain, $admin_email, $blueprint, $meta) {
        $log_entry = array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'action' => 'site_created',
            'site_id' => $site_id,
            'domain' => $domain,
            'admin_email' => $admin_email,
            'blueprint' => $blueprint,
            'meta' => $meta,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $this->get_user_agent()
        );
        
        $this->write_log($log_entry);
        
        // Also log to WordPress error log for debugging
        error_log(sprintf(
            'Site Factory: Site created - ID: %d, Domain: %s, Email: %s, Blueprint: %s',
            $site_id,
            $domain,
            $admin_email,
            $blueprint
        ));
    }
    
    /**
     * Log site creation failure
     */
    public function log_site_creation_failure($error_message, $request_data) {
        $log_entry = array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'action' => 'site_creation_failed',
            'error_message' => $error_message,
            'request_data' => $request_data,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $this->get_user_agent()
        );
        
        $this->write_log($log_entry);
        
        // Also log to WordPress error log
        error_log(sprintf(
            'Site Factory: Site creation failed - Error: %s',
            $error_message
        ));
    }
    
    /**
     * Log authentication failure
     */
    public function log_auth_failure($ip_address, $user_agent, $reason = 'invalid_token') {
        $log_entry = array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'action' => 'auth_failure',
            'reason' => $reason,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent
        );
        
        $this->write_log($log_entry);
        
        // Log to WordPress error log
        error_log(sprintf(
            'Site Factory: Authentication failure - IP: %s, Reason: %s',
            $ip_address,
            $reason
        ));
    }
    
    /**
     * Log rate limit hit
     */
    public function log_rate_limit_hit($ip_address, $user_agent) {
        $log_entry = array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'action' => 'rate_limit_hit',
            'ip_address' => $ip_address,
            'user_agent' => $user_agent
        );
        
        $this->write_log($log_entry);
        
        // Log to WordPress error log
        error_log(sprintf(
            'Site Factory: Rate limit hit - IP: %s',
            $ip_address
        ));
    }
    
    /**
     * Log plugin activation
     */
    public function log_plugin_activation() {
        $log_entry = array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'action' => 'plugin_activated',
            'version' => SF_VERSION,
            'multisite' => is_multisite(),
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version')
        );
        
        $this->write_log($log_entry);
    }
    
    /**
     * Write log entry to file
     */
    private function write_log($log_entry) {
        try {
            // Ensure log directory exists
            $this->ensure_log_directory();
            
            // Format log entry
            $log_line = json_encode($log_entry) . "\n";
            
            // Write to log file
            if (file_put_contents($this->log_file, $log_line, FILE_APPEND | LOCK_EX) === false) {
                error_log('Site Factory: Failed to write to log file: ' . $this->log_file);
            }
            
            // Rotate logs if they get too large (over 10MB)
            $this->rotate_logs_if_needed();
            
        } catch (Exception $e) {
            error_log('Site Factory: Logging error: ' . $e->getMessage());
        }
    }
    
    /**
     * Rotate logs if they get too large
     */
    private function rotate_logs_if_needed() {
        if (file_exists($this->log_file) && filesize($this->log_file) > 10 * 1024 * 1024) { // 10MB
            $backup_file = $this->log_file . '.' . date('Y-m-d-H-i-s') . '.backup';
            rename($this->log_file, $backup_file);
            
            // Compress old backup files
            $this->compress_old_logs();
        }
    }
    
    /**
     * Compress old log files
     */
    private function compress_old_logs() {
        $files = glob($this->log_dir . '/*.backup');
        foreach ($files as $file) {
            if (filemtime($file) < strtotime('-7 days')) { // Older than 7 days
                $gz_file = $file . '.gz';
                if (!file_exists($gz_file)) {
                    $gz = gzopen($gz_file, 'w9');
                    gzwrite($gz, file_get_contents($file));
                    gzclose($gz);
                    unlink($file); // Remove uncompressed file
                }
            }
        }
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Get user agent
     */
    private function get_user_agent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }
    
    /**
     * Get recent log entries
     */
    public function get_recent_logs($limit = 100) {
        if (!file_exists($this->log_file)) {
            return array();
        }
        
        $logs = array();
        $lines = file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if ($lines) {
            $lines = array_reverse(array_slice($lines, -$limit));
            
            foreach ($lines as $line) {
                $log_entry = json_decode($line, true);
                if ($log_entry) {
                    $logs[] = $log_entry;
                }
            }
        }
        
        return $logs;
    }
    
    /**
     * Clear old logs
     */
    public function clear_old_logs($days = 30) {
        $cutoff_time = strtotime("-{$days} days");
        $files = glob($this->log_dir . '/*');
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoff_time) {
                unlink($file);
            }
        }
        
        // Log the cleanup
        $this->write_log(array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'action' => 'logs_cleaned',
            'days_old' => $days
        ));
    }
}

