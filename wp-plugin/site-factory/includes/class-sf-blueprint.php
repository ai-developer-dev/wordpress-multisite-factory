<?php

namespace SiteFactory;

/**
 * Handle site blueprints and content creation
 */
class SF_Blueprint {
    
    private $blueprint_dir;
    
    public function __construct() {
        $this->blueprint_dir = SF_PLUGIN_DIR . 'blueprints/';
    }
    
    /**
     * Apply a blueprint to the current site
     */
    public function apply_blueprint($blueprint_name, $meta = array()) {
        // Get blueprint content
        $blueprint_content = $this->get_blueprint_content($blueprint_name);
        if (!$blueprint_content) {
            $this->create_default_content($meta);
            return;
        }
        
        // Replace tokens in blueprint
        $processed_content = $this->replace_tokens($blueprint_content, $meta);
        
        // Create pages and content
        $this->create_site_content($processed_content, $meta);
        
        // Set up site structure
        $this->setup_site_structure($meta);
    }
    
    /**
     * Get blueprint content from file
     */
    private function get_blueprint_content($blueprint_name) {
        $blueprint_file = $this->blueprint_dir . $blueprint_name . '.html';
        
        if (file_exists($blueprint_file)) {
            return file_get_contents($blueprint_file);
        }
        
        return false;
    }
    
    /**
     * Replace tokens in content
     */
    private function replace_tokens($content, $meta) {
        $tokens = array(
            '{{businessName}}' => isset($meta['businessName']) ? $meta['businessName'] : 'Your Business',
            '{{street}}' => isset($meta['street']) ? $meta['street'] : '123 Main St',
            '{{city}}' => isset($meta['city']) ? $meta['city'] : 'Your City',
            '{{state}}' => isset($meta['state']) ? $meta['state'] : 'ST',
            '{{zip}}' => isset($meta['zip']) ? $meta['zip'] : '12345',
            '{{email}}' => isset($meta['email']) ? $meta['email'] : 'info@example.com',
            '{{phone}}' => isset($meta['phone']) ? $meta['phone'] : '(555) 123-4567',
            '{{businessType}}' => isset($meta['businessType']) ? $meta['businessType'] : 'Business',
            '{{description}}' => isset($meta['description']) ? $meta['description'] : 'Professional services and solutions'
        );
        
        return str_replace(array_keys($tokens), array_values($tokens), $content);
    }
    
    /**
     * Create default content if no blueprint
     */
    private function create_default_content($meta) {
        $site_title = get_bloginfo('name');
        
        // Create Home page
        $home_page = array(
            'post_title' => 'Home',
            'post_content' => $this->get_default_home_content($meta),
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1
        );
        
        $home_id = wp_insert_post($home_page);
        
        // Set as front page
        update_option('show_on_front', 'page');
        update_option('page_on_front', $home_id);
        
        // Create About page
        $about_page = array(
            'post_title' => 'About',
            'post_content' => $this->get_default_about_content($meta),
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1
        );
        
        wp_insert_post($about_page);
        
        // Create Contact page
        $contact_page = array(
            'post_title' => 'Contact',
            'post_content' => $this->get_default_contact_content($meta),
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1
        );
        
        wp_insert_post($contact_page);
    }
    
    /**
     * Create site content from blueprint
     */
    private function create_site_content($content, $meta) {
        // Parse HTML content and create pages
        $dom = new \DOMDocument();
        @$dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        $pages = $dom->getElementsByTagName('page');
        
        foreach ($pages as $page_element) {
            $title = $page_element->getAttribute('title') ?: 'Page';
            $slug = $page_element->getAttribute('slug') ?: sanitize_title($title);
            $is_front = $page_element->getAttribute('front') === 'true';
            
            // Get page content
            $page_content = '';
            foreach ($page_element->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    $page_content .= $dom->saveHTML($child);
                }
            }
            
            // Create page
            $page_data = array(
                'post_title' => $title,
                'post_content' => $page_content,
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => $slug,
                'post_author' => 1
            );
            
            $page_id = wp_insert_post($page_data);
            
            // Set as front page if specified
            if ($is_front) {
                update_option('show_on_front', 'page');
                update_option('page_on_front', $page_id);
            }
        }
    }
    
    /**
     * Setup site structure and settings
     */
    private function setup_site_structure($meta) {
        // Set site title if provided
        if (!empty($meta['businessName'])) {
            update_option('blogname', $meta['businessName']);
        }
        
        // Set tagline
        if (!empty($meta['description'])) {
            update_option('blogdescription', $meta['description']);
        }
        
        // Set permalink structure
        update_option('permalink_structure', '/%postname%/');
        
        // Create navigation menu
        $this->create_navigation_menu();
    }
    
    /**
     * Create navigation menu
     */
    private function create_navigation_menu() {
        $menu_name = 'Primary Menu';
        $menu_exists = wp_get_nav_menu_object($menu_name);
        
        if (!$menu_exists) {
            $menu_id = wp_create_nav_menu($menu_name);
            
            // Get pages to add to menu
            $pages = get_pages();
            foreach ($pages as $page) {
                wp_update_nav_menu_item($menu_id, 0, array(
                    'menu-item-title' => $page->post_title,
                    'menu-item-object-id' => $page->ID,
                    'menu-item-object' => 'page',
                    'menu-item-status' => 'publish',
                    'menu-item-type' => 'post_type'
                ));
            }
            
            // Assign menu to primary location
            $locations = get_theme_mod('nav_menu_locations');
            $locations['primary'] = $menu_id;
            set_theme_mod('nav_menu_locations', $locations);
        }
    }
    
    /**
     * Get default home page content
     */
    private function get_default_home_content($meta) {
        $business_name = isset($meta['businessName']) ? $meta['businessName'] : 'Your Business';
        $description = isset($meta['description']) ? $meta['description'] : 'Professional services and solutions';
        
        return "
        <h1>Welcome to {$business_name}</h1>
        <p>{$description}</p>
        <p>We're here to serve you with excellence and professionalism.</p>
        ";
    }
    
    /**
     * Get default about page content
     */
    private function get_default_about_content($meta) {
        $business_name = isset($meta['businessName']) ? $meta['businessName'] : 'Your Business';
        $business_type = isset($meta['businessType']) ? $meta['businessType'] : 'Business';
        
        return "
        <h1>About {$business_name}</h1>
        <p>We are a dedicated {$business_type} committed to providing exceptional service to our clients.</p>
        <p>Our team brings years of experience and expertise to every project.</p>
        ";
    }
    
    /**
     * Get default contact page content
     */
    private function get_default_contact_content($meta) {
        $business_name = isset($meta['businessName']) ? $meta['businessName'] : 'Your Business';
        $street = isset($meta['street']) ? $meta['street'] : '123 Main St';
        $city = isset($meta['city']) ? $meta['city'] : 'Your City';
        $state = isset($meta['state']) ? $meta['state'] : 'ST';
        $zip = isset($meta['zip']) ? $meta['zip'] : '12345';
        $email = isset($meta['email']) ? $meta['email'] : 'info@example.com';
        $phone = isset($meta['phone']) ? $meta['phone'] : '(555) 123-4567';
        
        return "
        <h1>Contact {$business_name}</h1>
        <h3>Address</h3>
        <p>{$street}<br>{$city}, {$state} {$zip}</p>
        
        <h3>Contact Information</h3>
        <p>Email: <a href='mailto:{$email}'>{$email}</a><br>
        Phone: {$phone}</p>
        
        <h3>Business Hours</h3>
        <p>Monday - Friday: 9:00 AM - 5:00 PM<br>
        Saturday: 10:00 AM - 2:00 PM<br>
        Sunday: Closed</p>
        ";
    }
}

