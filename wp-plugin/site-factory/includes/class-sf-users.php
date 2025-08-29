<?php

namespace SiteFactory;

/**
 * Handle user creation and management for new sites
 */
class SF_Users {
    
    public function __construct() {
        // Hook into user creation events
        add_action('wpmu_new_user', array($this, 'handle_new_user'), 10, 1);
    }
    
    /**
     * Create or attach a user to a site
     */
    public function create_or_attach_user($email, $site_id) {
        // Check if user already exists
        $user = get_user_by('email', $email);
        
        if (!$user) {
            // Create new user
            $user = $this->create_user($email);
        }
        
        if (is_wp_error($user)) {
            return $user;
        }
        
        // Add user to the site
        $result = $this->add_user_to_site($user->ID, $site_id);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Send welcome email
        $this->send_welcome_email($user, $site_id);
        
        return $user;
    }
    
    /**
     * Create a new user
     */
    private function create_user($email) {
        // Generate username from email
        $username = $this->generate_username($email);
        
        // Generate secure password
        $password = wp_generate_password(12, false);
        
        // Create user
        $user_id = wpmu_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Get user object
        $user = get_user_by('id', $user_id);
        
        // Set user meta
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $this->extract_first_name($email),
            'display_name' => $this->extract_first_name($email)
        ));
        
        // Store password for email
        update_user_meta($user_id, '_temp_password', $password);
        
        return $user;
    }
    
    /**
     * Add user to a specific site
     */
    private function add_user_to_site($user_id, $site_id) {
        // Switch to the target site
        switch_to_blog($site_id);
        
        // Add user to site with admin role
        $result = add_user_to_blog($site_id, $user_id, 'administrator');
        
        // Restore original blog
        restore_current_blog();
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return true;
    }
    
    /**
     * Generate username from email
     */
    private function generate_username($email) {
        $username = sanitize_user(str_replace('@', '_', $email));
        
        // Ensure uniqueness
        $counter = 1;
        $original_username = $username;
        
        while (username_exists($username)) {
            $username = $original_username . '_' . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    /**
     * Extract first name from email
     */
    private function extract_first_name($email) {
        $name_part = explode('@', $email)[0];
        
        // Convert to title case and replace dots/underscores with spaces
        $name_part = str_replace(array('.', '_'), ' ', $name_part);
        $name_part = ucwords(strtolower($name_part));
        
        return $name_part;
    }
    
    /**
     * Send welcome email to new user
     */
    private function send_welcome_email($user, $site_id) {
        // Switch to the target site
        switch_to_blog($site_id);
        
        $site_url = get_site_url();
        $site_name = get_bloginfo('name');
        $admin_url = get_admin_url();
        
        // Get temporary password
        $temp_password = get_user_meta($user->ID, '_temp_password', true);
        
        // Email subject
        $subject = sprintf('Welcome to %s - Your New Website', $site_name);
        
        // Email body
        $message = sprintf(
            "Hello %s,\n\n" .
            "Welcome to %s! Your new website has been created successfully.\n\n" .
            "Here are your login details:\n" .
            "Website: %s\n" .
            "Admin URL: %s\n" .
            "Username: %s\n" .
            "Password: %s\n\n" .
            "Please log in and change your password immediately for security.\n\n" .
            "If you have any questions, please don't hesitate to contact us.\n\n" .
            "Best regards,\n" .
            "The Website Factory Team",
            $user->first_name ?: $user->display_name,
            $site_name,
            $site_url,
            $admin_url,
            $user->user_login,
            $temp_password
        );
        
        // Send email
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>'
        );
        
        wp_mail($user->user_email, $subject, $message, $headers);
        
        // Clean up temporary password
        delete_user_meta($user->ID, '_temp_password');
        
        // Restore original blog
        restore_current_blog();
    }
    
    /**
     * Handle new user creation (hook)
     */
    public function handle_new_user($user_id) {
        // This can be used for additional user setup if needed
        // For now, we'll just log it
        $user = get_user_by('id', $user_id);
        if ($user) {
            error_log("Site Factory: New user created - {$user->user_email}");
        }
    }
    
    /**
     * Get user's sites
     */
    public function get_user_sites($user_id) {
        $sites = get_blogs_of_user($user_id);
        return $sites;
    }
    
    /**
     * Remove user from a site
     */
    public function remove_user_from_site($user_id, $site_id) {
        // Switch to the target site
        switch_to_blog($site_id);
        
        // Remove user from site
        $result = remove_user_from_blog($user_id, $site_id);
        
        // Restore original blog
        restore_current_blog();
        
        return $result;
    }
}

