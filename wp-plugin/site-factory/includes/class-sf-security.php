<?php

namespace SiteFactory;

/**
 * Handle security, rate limiting, and abuse prevention
 */
class SF_Security {
    
    private $rate_limit_window = 3600; // 1 hour
    private $max_requests_per_window = 10; // Max 10 requests per hour per IP
    private $logger;
    
    public function __construct() {
        $this->logger = new SF_Logger();
        
        // Add security headers
        add_action('send_headers', array($this, 'add_security_headers'));
        
        // Block suspicious requests
        add_action('init', array($this, 'check_suspicious_requests'));
    }
    
    /**
     * Check rate limiting
     */
    public function check_rate_limit() {
        $ip_address = $this->get_client_ip();
        
        // Skip rate limiting for localhost
        if (in_array($ip_address, array('127.0.0.1', '::1', 'localhost'))) {
            return true;
        }
        
        // Check if IP is blocked
        if ($this->is_ip_blocked($ip_address)) {
            $this->logger->log_auth_failure($ip_address, $this->get_user_agent(), 'ip_blocked');
            return false;
        }
        
        // Check rate limit
        $current_requests = $this->get_rate_limit_count($ip_address);
        
        if ($current_requests >= $this->max_requests_per_window) {
            $this->logger->log_rate_limit_hit($ip_address, $this->get_user_agent());
            return false;
        }
        
        // Increment rate limit counter
        $this->increment_rate_limit($ip_address);
        
        return true;
    }
    
    /**
     * Get rate limit count for an IP
     */
    private function get_rate_limit_count($ip_address) {
        $transient_key = 'sf_rate_limit_' . md5($ip_address);
        $data = get_transient($transient_key);
        
        if (!$data) {
            return 0;
        }
        
        // Check if window has expired
        if (time() - $data['timestamp'] > $this->rate_limit_window) {
            delete_transient($transient_key);
            return 0;
        }
        
        return $data['count'];
    }
    
    /**
     * Increment rate limit counter
     */
    private function increment_rate_limit($ip_address) {
        $transient_key = 'sf_rate_limit_' . md5($ip_address);
        $data = get_transient($transient_key);
        
        if (!$data) {
            $data = array(
                'count' => 1,
                'timestamp' => time()
            );
        } else {
            $data['count']++;
        }
        
        set_transient($transient_key, $data, $this->rate_limit_window);
    }
    
    /**
     * Check if IP is blocked
     */
    private function is_ip_blocked($ip_address) {
        $blocked_ips = get_site_option('sf_blocked_ips', array());
        return in_array($ip_address, $blocked_ips);
    }
    
    /**
     * Block an IP address
     */
    public function block_ip($ip_address, $reason = 'abuse') {
        $blocked_ips = get_site_option('sf_blocked_ips', array());
        
        if (!in_array($ip_address, $blocked_ips)) {
            $blocked_ips[] = $ip_address;
            update_site_option('sf_blocked_ips', $blocked_ips);
            
            // Log the block
            $this->logger->write_log(array(
                'timestamp' => current_time('Y-m-d H:i:s'),
                'action' => 'ip_blocked',
                'ip_address' => $ip_address,
                'reason' => $reason
            ));
        }
    }
    
    /**
     * Unblock an IP address
     */
    public function unblock_ip($ip_address) {
        $blocked_ips = get_site_option('sf_blocked_ips', array());
        $blocked_ips = array_diff($blocked_ips, array($ip_address));
        update_site_option('sf_blocked_ips', $blocked_ips);
        
        // Log the unblock
        $this->logger->write_log(array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'action' => 'ip_unblocked',
            'ip_address' => $ip_address
        ));
    }
    
    /**
     * Add security headers
     */
    public function add_security_headers() {
        // Only add headers for Site Factory endpoints
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/wp-json/site-factory/') !== false) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            
            // Content Security Policy
            header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");
        }
    }
    
    /**
     * Check for suspicious requests
     */
    public function check_suspicious_requests() {
        // Only check Site Factory endpoints
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/wp-json/site-factory/') === false) {
            return;
        }
        
        $ip_address = $this->get_client_ip();
        
        // Check for suspicious patterns
        if ($this->is_suspicious_request($ip_address)) {
            $this->block_ip($ip_address, 'suspicious_activity');
            wp_die('Access denied', 'Access Denied', array('response' => 403));
        }
    }
    
    /**
     * Check if request is suspicious
     */
    private function is_suspicious_request($ip_address) {
        // Check for rapid successive requests
        $recent_requests = $this->get_recent_requests($ip_address);
        
        if (count($recent_requests) > 5) {
            $last_request_time = end($recent_requests);
            if (time() - $last_request_time < 10) { // 5+ requests in 10 seconds
                return true;
            }
        }
        
        // Check for suspicious user agents
        $user_agent = $this->get_user_agent();
        $suspicious_agents = array(
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python', 'java'
        );
        
        foreach ($suspicious_agents as $suspicious) {
            if (stripos($user_agent, $suspicious) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get recent requests for an IP
     */
    private function get_recent_requests($ip_address) {
        $transient_key = 'sf_recent_requests_' . md5($ip_address);
        $data = get_transient($transient_key);
        
        if (!$data) {
            return array();
        }
        
        // Clean old requests (older than 1 minute)
        $data = array_filter($data, function($timestamp) {
            return time() - $timestamp < 60;
        });
        
        return $data;
    }
    
    /**
     * Record a request
     */
    public function record_request($ip_address) {
        $transient_key = 'sf_recent_requests_' . md5($ip_address);
        $data = get_transient($transient_key);
        
        if (!$data) {
            $data = array();
        }
        
        $data[] = time();
        
        // Keep only last 10 requests
        if (count($data) > 10) {
            $data = array_slice($data, -10);
        }
        
        set_transient($transient_key, $data, 60); // Expire in 1 minute
    }
    
    /**
     * Validate payload size and content
     */
    public function validate_payload($payload) {
        // Check payload size (max 1MB)
        $payload_size = strlen(serialize($payload));
        if ($payload_size > 1024 * 1024) {
            return new \WP_Error('payload_too_large', 'Payload size exceeds limit');
        }
        
        // Check for suspicious content
        if ($this->contains_suspicious_content($payload)) {
            return new \WP_Error('suspicious_content', 'Payload contains suspicious content');
        }
        
        return true;
    }
    
    /**
     * Check for suspicious content in payload
     */
    private function contains_suspicious_content($payload) {
        $suspicious_patterns = array(
            'javascript:', 'vbscript:', 'onload=', 'onerror=', 'onclick=',
            '<script', '</script>', 'eval(', 'document.cookie', 'window.location'
        );
        
        $payload_string = json_encode($payload);
        
        foreach ($suspicious_patterns as $pattern) {
            if (stripos($payload_string, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
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
     * Get security statistics
     */
    public function get_security_stats() {
        $blocked_ips = get_site_option('sf_blocked_ips', array());
        
        return array(
            'blocked_ips_count' => count($blocked_ips),
            'blocked_ips' => $blocked_ips,
            'rate_limit_window' => $this->rate_limit_window,
            'max_requests_per_window' => $this->max_requests_per_window
        );
    }
    
    /**
     * Clean up old security data
     */
    public function cleanup_old_data() {
        global $wpdb;
        
        // Clean up old transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s AND meta_value < %d",
                '_transient_sf_%',
                time() - (24 * 60 * 60) // 24 hours ago
            )
        );
        
        // Clean up old blocked IPs (older than 30 days)
        $blocked_ips = get_site_option('sf_blocked_ips', array());
        $old_blocked_ips = get_site_option('sf_blocked_ips_timestamps', array());
        
        foreach ($old_blocked_ips as $ip => $timestamp) {
            if (time() - $timestamp > 30 * 24 * 60 * 60) { // 30 days
                unset($blocked_ips[$ip]);
                unset($old_blocked_ips[$ip]);
            }
        }
        
        update_site_option('sf_blocked_ips', $blocked_ips);
        update_site_option('sf_blocked_ips_timestamps', $old_blocked_ips);
    }
}

