<?php

namespace SiteFactory;

/**
 * REST API endpoint for creating sites
 */
class SF_REST {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST routes
     */
    public function register_routes() {
        register_rest_route('site-factory/v1', '/create', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_site'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'site_title' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function($param) {
                        return !empty($param) && strlen($param) <= 100;
                    }
                ),
                'admin_email' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => 'is_email'
                ),
                'desired_domain' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_title',
                    'validate_callback' => function($param) {
                        return !empty($param) && preg_match('/^[a-z0-9-]+$/', $param);
                    }
                ),
                'blueprint' => array(
                    'required' => false,
                    'default' => 'default',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function($param) {
                        $allowed_blueprints = array('default', 'cpa-onepage', 'business-card');
                        return in_array($param, $allowed_blueprints);
                    }
                ),
                'meta' => array(
                    'required' => false,
                    'type' => 'object',
                    'default' => array()
                )
            )
        ));
    }
    
    /**
     * Check if request is authenticated
     */
    public function check_permission($request) {
        $auth_header = $request->get_header('X-Auth');
        $expected_token = defined('SITE_FACTORY_TOKEN') ? SITE_FACTORY_TOKEN : '';
        
        if (empty($expected_token)) {
            return new \WP_Error(
                'no_token_configured',
                'Site Factory token not configured',
                array('status' => 500)
            );
        }
        
        if (empty($auth_header) || $auth_header !== $expected_token) {
            return new \WP_Error(
                'invalid_auth',
                'Invalid authentication token',
                array('status' => 401)
            );
        }
        
        return true;
    }
    
    /**
     * Create a new site
     */
    public function create_site($request) {
        // Rate limiting check
        $rate_limit = new SF_Security();
        if (!$rate_limit->check_rate_limit()) {
            return new \WP_Error(
                'rate_limit_exceeded',
                'Rate limit exceeded. Please try again later.',
                array('status' => 429)
            );
        }
        
        // Get and validate parameters
        $site_title = $request->get_param('site_title');
        $admin_email = $request->get_param('admin_email');
        $desired_domain = $request->get_param('desired_domain');
        $blueprint = $request->get_param('blueprint');
        $meta = $request->get_param('meta');
        
        // Validate domain availability
        $domain = $desired_domain . '.' . parse_url(get_site_url(1), PHP_URL_HOST);
        if (domain_exists($domain)) {
            return new \WP_Error(
                'domain_exists',
                'Domain already exists',
                array('status' => 400)
            );
        }
        
        // Create the site
        $site_id = wp_insert_site(array(
            'domain' => $desired_domain,
            'path' => '/',
            'title' => $site_title,
            'user_id' => 1, // Network admin
            'meta' => array(
                'public' => 1,
                'site_factory_blueprint' => $blueprint,
                'site_factory_meta' => $meta
            )
        ));
        
        if (is_wp_error($site_id)) {
            return $site_id;
        }
        
        // Switch to the new blog
        switch_to_blog($site_id);
        
        // Apply blueprint
        $blueprint_handler = new SF_Blueprint();
        $blueprint_handler->apply_blueprint($blueprint, $meta);
        
        // Create admin user
        $user_handler = new SF_Users();
        $user_result = $user_handler->create_or_attach_user($admin_email, $site_id);
        
        // Restore original blog
        restore_current_blog();
        
        // Log the creation
        $logger = new SF_Logger();
        $logger->log_site_creation($site_id, $domain, $admin_email, $blueprint, $meta);
        
        // Return success response
        return array(
            'success' => true,
            'siteId' => $site_id,
            'siteUrl' => 'http://' . $domain,
            'message' => 'Site created successfully'
        );
    }
}

