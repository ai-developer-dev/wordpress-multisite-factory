#!/bin/bash

# Phase 1: Local Multisite Setup
# This script brings up the Docker stack and installs WordPress Multisite

set -e

# Source common functions
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/common.sh"

# Main setup function
main() {
    log "=== PHASE 1: Local Multisite Setup ==="
    
    # Initial setup checks
    setup
    
    # Start Docker services
    log "Starting Docker services..."
    dc up -d
    
    # Wait for services to be ready
    wait_service "db"
    wait_service "php"
    wait_service "nginx"
    
    # Check if WordPress is already installed
    if [ -d "wordpress/wp-admin" ]; then
        log "WordPress appears to be already installed"
    else
        log "WordPress not found, downloading and installing..."
        install_wordpress
    fi
    
    # Configure WordPress
    configure_wordpress
    
    # Install and configure plugins
    setup_plugins
    
    # Create test sub-site
    create_test_site
    
    # Final tests
    run_tests
    
    success "=== PHASE 1 COMPLETED SUCCESSFULLY ==="
    log "Access points:"
    log "  - WordPress Network Admin: http://factory.lvh.me/wp-admin/network/"
    log "  - Test Sub-site: http://test.factory.lvh.me/"
    log "  - MailHog: http://localhost:8025"
}

# Install WordPress core
install_wordpress() {
    log "Downloading WordPress core..."
    wp core download --version=latest
    
    log "Creating wp-config.php..."
    wp config create \
        --dbhost=db \
        --dbname="${DB_NAME}" \
        --dbuser="${DB_USER}" \
        --dbpass="${DB_PASSWORD}" \
        --extra-php="
            define('WP_DEBUG', true);
            define('WP_DEBUG_LOG', true);
            define('WP_DEBUG_DISPLAY', false);
            define('WP_MEMORY_LIMIT', '${PHP_MEMORY_LIMIT:-512M}');
            define('SITE_FACTORY_TOKEN', '${SITE_FACTORY_TOKEN}');
        "
    
    log "Creating database..."
    wp db create
    
    log "Installing WordPress Multisite..."
    wp core multisite-install \
        --url="${BASE_URL}" \
        --title="Website Factory (Local)" \
        --admin_user="${ADMIN_USER}" \
        --admin_password="${ADMIN_PASSWORD}" \
        --admin_email="${ADMIN_EMAIL}" \
        --subdomains
}

# Configure WordPress settings
configure_wordpress() {
    log "Configuring WordPress settings..."
    
    # Set permalink structure
    wp option update permalink_structure '/%postname%/' --network
    
    # Configure SMTP settings
    wp option update smtp_host "${SMTP_HOST}" --network
    wp option update smtp_port "${SMTP_PORT}" --network
    wp option update smtp_from "${SMTP_FROM}" --network
    wp option update smtp_name "${SMTP_NAME}" --network
    
    # Enable multisite features
    wp option update enable_registration 1 --network
    wp option update add_new_users 1 --network
    
    success "WordPress configured successfully"
}

# Install and configure plugins
setup_plugins() {
    log "Setting up plugins..."
    
    # Install WP Mail SMTP
    wp plugin install wp-mail-smtp --activate --network
    
    # Install Redis Object Cache
    wp plugin install redis-cache --activate --network
    
    # Configure Redis
    wp redis enable --network
    
    # Install a simple page cache plugin
    wp plugin install wp-super-cache --activate --network
    
    success "Plugins configured successfully"
}

# Create test sub-site
create_test_site() {
    log "Creating test sub-site 'test'..."
    
    # Check if test site already exists
    if wp site list --field=domain | grep -q "test.factory.lvh.me"; then
        log "Test site already exists"
        return 0
    fi
    
    # Create test site
    wp site create \
        --slug=test \
        --title="Test Site" \
        --email="${ADMIN_EMAIL}"
    
    success "Test site created: http://test.factory.lvh.me/"
}

# Run tests to verify setup
run_tests() {
    log "Running Phase 1 tests..."
    
    # Test main site
    log "Testing main site..."
    if curl -s -o /dev/null -w "%{http_code}" "http://factory.lvh.me" | grep -q "200"; then
        success "Main site responds with 200"
    else
        error "Main site not responding correctly"
        return 1
    fi
    
    # Test test sub-site
    log "Testing test sub-site..."
    if curl -s -o /dev/null -w "%{http_code}" "http://test.factory.lvh.me" | grep -q "200"; then
        success "Test sub-site responds with 200"
    else
        error "Test sub-site not responding correctly"
        return 1
    fi
    
    # Test Redis status
    log "Testing Redis cache..."
    if wp redis status --network | grep -q "Connected"; then
        success "Redis cache is working"
    else
        warning "Redis cache may not be working properly"
    fi
    
    # Test MailHog
    log "Testing MailHog..."
    if curl -s -o /dev/null -w "%{http_code}" "http://localhost:8025" | grep -q "200"; then
        success "MailHog is accessible"
    else
        warning "MailHog may not be accessible"
    fi
    
    success "All Phase 1 tests passed!"
}

# Error handling
trap 'error "Phase 1 setup failed. Check logs above."' ERR

# Run main function
main "$@"

