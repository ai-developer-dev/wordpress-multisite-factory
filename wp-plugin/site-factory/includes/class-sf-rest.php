<?php
/**
 * Site Factory REST API Handler
 * 
 * Handles REST API endpoints for creating WordPress sites via the landing page form
 */

namespace SiteFactory;

if (!defined('ABSPATH')) {
    exit;
}

class SF_REST {
    
    private $namespace = 'site-factory/v1';
    private $rate_limiter;
    
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
        $this->rate_limiter = new SF_Rate_Limiter();
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Create new WordPress site
        register_rest_route($this->namespace, '/create-site', [
            'methods' => 'POST',
            'callback' => [$this, 'create_site'],
            'permission_callback' => [$this, 'check_api_permissions'],
            'args' => [
                'business_name' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => [$this, 'validate_business_name']
                ],
                'email' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => 'is_email'
                ],
                'phone' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => [$this, 'validate_phone']
                ],
                'address' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                ],
                'business_type' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => [$this, 'validate_business_type']
                ],
                'template' => [
                    'required' => false,
                    'type' => 'string',
                    'default' => 'default',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);
        
        // Get site creation status
        register_rest_route($this->namespace, '/site-status/(?P<site_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_site_status'],
            'permission_callback' => [$this, 'check_api_permissions'],
            'args' => [
                'site_id' => [
                    'required' => true,
                    'type' => 'integer'
                ]
            ]
        ]);
        
        // List available business templates
        register_rest_route($this->namespace, '/templates', [
            'methods' => 'GET',
            'callback' => [$this, 'get_templates'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    /**
     * Create a new WordPress site
     */
    public function create_site($request) {
        // Check rate limiting
        if (!$this->rate_limiter->check_rate_limit()) {
            return new \WP_Error(
                'rate_limit_exceeded',
                'Too many requests. Please try again later.',
                ['status' => 429]
            );
        }
        
        $params = $request->get_params();
        
        // Log the creation attempt
        SF_Logger::log('info', 'Site creation requested', $params);
        
        try {
            // Generate unique site slug
            $site_slug = $this->generate_site_slug($params['business_name']);
            
            // Create the WordPress site
            $site_id = wpmu_create_blog(
                get_current_site()->domain,
                '/' . $site_slug . '/',
                $params['business_name'],
                get_current_user_id() ?: 1,
                [
                    'public' => 1,
                    'WPLANG' => get_locale()
                ],
                get_current_site()->id
            );
            
            if (is_wp_error($site_id)) {
                SF_Logger::log('error', 'Site creation failed', [
                    'error' => $site_id->get_error_message(),
                    'params' => $params
                ]);
                
                return new \WP_Error(
                    'site_creation_failed',
                    'Failed to create WordPress site: ' . $site_id->get_error_message(),
                    ['status' => 500]
                );
            }
            
            // Store business information
            $this->store_business_info($site_id, $params);
            
            // Apply business template
            $this->apply_business_template($site_id, $params['template'], $params['business_type']);
            
            // Create admin user
            $user_id = $this->create_site_admin($site_id, $params);
            
            // Send welcome email
            $this->send_welcome_email($user_id, $site_id, $params);
            
            $site_url = get_site_url($site_id);
            $admin_url = get_admin_url($site_id);
            
            SF_Logger::log('success', 'Site created successfully', [
                'site_id' => $site_id,
                'site_url' => $site_url,
                'business_name' => $params['business_name']
            ]);
            
            return new \WP_REST_Response([
                'success' => true,
                'site_id' => $site_id,
                'site_url' => $site_url,
                'admin_url' => $admin_url,
                'site_slug' => $site_slug,
                'message' => 'WordPress site created successfully!'
            ], 201);
            
        } catch (Exception $e) {
            SF_Logger::log('error', 'Site creation exception', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return new \WP_Error(
                'site_creation_error',
                'An error occurred while creating the site.',
                ['status' => 500]
            );
        }
    }
    
    /**
     * Get site creation status
     */
    public function get_site_status($request) {
        $site_id = $request->get_param('site_id');
        
        if (!get_blog_details($site_id)) {
            return new \WP_Error(
                'site_not_found',
                'Site not found',
                ['status' => 404]
            );
        }
        
        switch_to_blog($site_id);
        
        $status = [
            'site_id' => $site_id,
            'site_url' => get_site_url(),
            'admin_url' => get_admin_url(),
            'status' => 'active',
            'created' => get_blog_details($site_id)->registered,
            'business_info' => get_option('sf_business_info', [])
        ];
        
        restore_current_blog();
        
        return new \WP_REST_Response($status, 200);
    }
    
    /**
     * Get available business templates
     */
    public function get_templates() {
        $templates = [
            'default' => [
                'name' => 'Default Business',
                'description' => 'Clean, professional design suitable for most businesses',
                'preview' => '/wp-content/themes/default-business/screenshot.png'
            ],
            'restaurant' => [
                'name' => 'Restaurant',
                'description' => 'Perfect for restaurants, cafes, and food businesses',
                'preview' => '/wp-content/themes/restaurant/screenshot.png'
            ],
            'professional' => [
                'name' => 'Professional Services',
                'description' => 'Ideal for lawyers, consultants, and service providers',
                'preview' => '/wp-content/themes/professional/screenshot.png'
            ],
            'retail' => [
                'name' => 'Retail Store',
                'description' => 'Great for retail shops and e-commerce businesses',
                'preview' => '/wp-content/themes/retail/screenshot.png'
            ]
        ];
        
        return new \WP_REST_Response($templates, 200);
    }
    
    /**
     * Check API permissions
     */
    public function check_api_permissions($request) {
        // Check if API is enabled
        if (!defined('SF_API_ENABLED') || !SF_API_ENABLED) {
            return false;
        }
        
        // For public site creation, we'll use a simple token system
        $api_token = $request->get_header('Authorization');
        if (!$api_token) {
            $api_token = $request->get_param('api_token');
        }
        
        // Remove 'Bearer ' prefix if present
        $api_token = str_replace('Bearer ', '', $api_token);
        
        // Check against stored API token
        $valid_token = get_site_option('sf_api_token');
        if (!$valid_token || !hash_equals($valid_token, $api_token)) {
            return new \WP_Error(
                'invalid_token',
                'Invalid API token',
                ['status' => 401]
            );
        }
        
        return true;
    }
    
    /**
     * Generate unique site slug
     */
    private function generate_site_slug($business_name) {
        $base_slug = sanitize_title($business_name);
        $slug = $base_slug;
        $counter = 1;
        
        while (domain_exists(get_current_site()->domain, '/' . $slug . '/')) {
            $slug = $base_slug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Store business information
     */
    private function store_business_info($site_id, $params) {
        switch_to_blog($site_id);
        
        $business_info = [
            'business_name' => $params['business_name'],
            'email' => $params['email'],
            'phone' => $params['phone'],
            'address' => $params['address'],
            'business_type' => $params['business_type'],
            'created_at' => current_time('mysql')
        ];
        
        update_option('sf_business_info', $business_info);
        
        restore_current_blog();
    }
    
    /**
     * Apply business template
     */
    private function apply_business_template($site_id, $template, $business_type) {
        switch_to_blog($site_id);
        
        // Apply template-specific settings
        $template_config = $this->get_template_config($template, $business_type);
        
        if ($template_config) {
            // Set theme
            if (isset($template_config['theme'])) {
                switch_theme($template_config['theme']);
            }
            
            // Import content
            if (isset($template_config['content'])) {
                $this->import_template_content($template_config['content']);
            }
            
            // Configure plugins
            if (isset($template_config['plugins'])) {
                $this->activate_template_plugins($template_config['plugins']);
            }
        }
        
        restore_current_blog();
    }
    
    /**
     * Create site admin user
     */
    private function create_site_admin($site_id, $params) {
        $user_data = [
            'user_login' => sanitize_user($params['email']),
            'user_email' => $params['email'],
            'user_pass' => wp_generate_password(12, true),
            'display_name' => $params['business_name'],
            'role' => 'administrator'
        ];
        
        $user_id = wp_create_user(
            $user_data['user_login'],
            $user_data['user_pass'],
            $user_data['user_email']
        );
        
        if (!is_wp_error($user_id)) {
            // Add user to site
            add_user_to_blog($site_id, $user_id, 'administrator');
            
            // Store password for welcome email
            update_user_meta($user_id, 'sf_temp_password', $user_data['user_pass']);
        }
        
        return $user_id;
    }
    
    /**
     * Send welcome email
     */
    private function send_welcome_email($user_id, $site_id, $params) {
        $user = get_user_by('id', $user_id);
        $site_url = get_site_url($site_id);
        $admin_url = get_admin_url($site_id);
        $temp_password = get_user_meta($user_id, 'sf_temp_password', true);
        
        $subject = sprintf('Welcome to your new WordPress site: %s', $params['business_name']);
        
        $message = sprintf(
            "Hello %s,\n\n" .
            "Your new WordPress website has been successfully created!\n\n" .
            "Site Details:\n" .
            "- Business Name: %s\n" .
            "- Website URL: %s\n" .
            "- Admin Panel: %s\n\n" .
            "Login Credentials:\n" .
            "- Username: %s\n" .
            "- Password: %s\n\n" .
            "Please log in and change your password immediately.\n\n" .
            "Best regards,\n" .
            "WordPress Site Factory Team",
            $params['business_name'],
            $params['business_name'],
            $site_url,
            $admin_url,
            $user->user_login,
            $temp_password
        );
        
        wp_mail($params['email'], $subject, $message);
        
        // Remove temp password
        delete_user_meta($user_id, 'sf_temp_password');
    }
    
    /**
     * Validation methods
     */
    public function validate_business_name($value) {
        return !empty($value) && strlen($value) >= 2 && strlen($value) <= 100;
    }
    
    public function validate_phone($value) {
        return !empty($value) && preg_match('/^[\d\s\-\+\(\)]+$/', $value);
    }
    
    public function validate_business_type($value) {
        $allowed_types = ['restaurant', 'retail', 'professional', 'healthcare', 'education', 'nonprofit', 'other'];
        return in_array($value, $allowed_types);
    }
    
    /**
     * Get template configuration
     */
    private function get_template_config($template, $business_type) {
        // This would be expanded with actual template configurations
        return [
            'theme' => 'twentytwentyfour',
            'content' => $template . '_content.xml',
            'plugins' => ['contact-form-7', 'yoast-seo']
        ];
    }
    
    private function import_template_content($content_file) {
        // Template content import logic would go here
    }
    
    private function activate_template_plugins($plugins) {
        // Plugin activation logic would go here
    }
}

/**
 * Rate Limiter Class
 */
class SF_Rate_Limiter {
    
    private $max_requests;
    private $time_window;
    
    public function __construct() {
        $this->max_requests = defined('SF_API_RATE_LIMIT') ? SF_API_RATE_LIMIT : 10;
        $this->time_window = 60; // 1 minute
    }
    
    public function check_rate_limit() {
        $client_ip = $this->get_client_ip();
        $cache_key = 'sf_rate_limit_' . md5($client_ip);
        
        $requests = wp_cache_get($cache_key);
        if ($requests === false) {
            $requests = 0;
        }
        
        if ($requests >= $this->max_requests) {
            return false;
        }
        
        wp_cache_set($cache_key, $requests + 1, '', $this->time_window);
        
        return true;
    }
    
    private function get_client_ip() {
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = trim(explode(',', $_SERVER[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
}

