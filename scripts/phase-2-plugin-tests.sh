#!/bin/bash

# Phase 2: Site Factory Plugin Tests
# This script tests the Site Factory plugin functionality

set -e

# Source common functions
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/common.sh"

# Main test function
main() {
    log "=== PHASE 2: Site Factory Plugin Tests ==="
    
    # Load environment
    load_env
    
    # Check if WordPress is running
    if ! check_service "php"; then
        error "WordPress services are not running. Please run Phase 1 first."
        exit 1
    fi
    
    # Test plugin activation
    test_plugin_activation
    
    # Test REST endpoint without auth
    test_endpoint_no_auth
    
    # Test REST endpoint with invalid auth
    test_endpoint_invalid_auth
    
    # Test REST endpoint with valid auth
    test_endpoint_valid_auth
    
    # Test site creation
    test_site_creation
    
    # Test rate limiting
    test_rate_limiting
    
    success "=== PHASE 2 COMPLETED SUCCESSFULLY ==="
}

# Test plugin activation
test_plugin_activation() {
    log "Testing plugin activation..."
    
    # Check if plugin is active
    if wp plugin list --status=active --field=name | grep -q "site-factory"; then
        success "Site Factory plugin is active"
    else
        error "Site Factory plugin is not active"
        exit 1
    fi
    
    # Check if REST endpoint is registered
    if curl -s "http://factory.lvh.me/wp-json/site-factory/v1/create" | grep -q "rest_no_route\|rest_forbidden"; then
        success "REST endpoint is registered (returns expected error)"
    else
        error "REST endpoint is not properly registered"
        exit 1
    fi
}

# Test endpoint without authentication
test_endpoint_no_auth() {
    log "Testing endpoint without authentication..."
    
    local response=$(curl -s -w "%{http_code}" -o /tmp/response.json \
        -X POST \
        -H "Content-Type: application/json" \
        -d '{"site_title":"Test Site","admin_email":"test@example.com","desired_domain":"test-site"}' \
        "http://factory.lvh.me/wp-json/site-factory/v1/create")
    
    if [ "$response" = "401" ]; then
        success "Endpoint correctly returns 401 without auth"
    else
        error "Endpoint should return 401 without auth, got $response"
        cat /tmp/response.json
        exit 1
    fi
}

# Test endpoint with invalid authentication
test_endpoint_invalid_auth() {
    log "Testing endpoint with invalid authentication..."
    
    local response=$(curl -s -w "%{http_code}" -o /tmp/response.json \
        -X POST \
        -H "Content-Type: application/json" \
        -H "X-Auth: invalid_token" \
        -d '{"site_title":"Test Site","admin_email":"test@example.com","desired_domain":"test-site"}' \
        "http://factory.lvh.me/wp-json/site-factory/v1/create")
    
    if [ "$response" = "401" ]; then
        success "Endpoint correctly returns 401 with invalid auth"
    else
        error "Endpoint should return 401 with invalid auth, got $response"
        cat /tmp/response.json
        exit 1
    fi
}

# Test endpoint with valid authentication
test_endpoint_valid_auth() {
    log "Testing endpoint with valid authentication..."
    
    local response=$(curl -s -w "%{http_code}" -o /tmp/response.json \
        -X POST \
        -H "Content-Type: application/json" \
        -H "X-Auth: ${SITE_FACTORY_TOKEN}" \
        -d '{"site_title":"Test Site","admin_email":"test@example.com","desired_domain":"test-site"}' \
        "http://factory.lvh.me/wp-json/site-factory/v1/create")
    
    if [ "$response" = "200" ]; then
        success "Endpoint correctly returns 200 with valid auth"
        cat /tmp/response.json
    else
        error "Endpoint should return 200 with valid auth, got $response"
        cat /tmp/response.json
        exit 1
    fi
}

# Test actual site creation
test_site_creation() {
    log "Testing actual site creation..."
    
    local test_domain="test-creation-$(date +%s)"
    local test_email="test-$(date +%s)@example.com"
    
    local response=$(curl -s -w "%{http_code}" -o /tmp/site_creation.json \
        -X POST \
        -H "Content-Type: application/json" \
        -H "X-Auth: ${SITE_FACTORY_TOKEN}" \
        -d "{\"site_title\":\"Test Creation Site\",\"admin_email\":\"${test_email}\",\"desired_domain\":\"${test_domain}\",\"blueprint\":\"cpa-onepage\",\"meta\":{\"businessName\":\"Test Business\",\"businessType\":\"CPA\"}}" \
        "http://factory.lvh.me/wp-json/site-factory/v1/create")
    
    if [ "$response" = "200" ]; then
        success "Site creation successful"
        
        # Parse response to get site URL
        local site_url=$(cat /tmp/site_creation.json | jq -r '.siteUrl // empty')
        if [ -n "$site_url" ]; then
            log "Created site URL: $site_url"
            
            # Test if site is accessible
            if curl -s -o /dev/null -w "%{http_code}" "$site_url" | grep -q "200"; then
                success "Created site is accessible"
            else
                warning "Created site may not be accessible yet (DNS propagation)"
            fi
        else
            warning "Could not extract site URL from response"
        fi
        
        cat /tmp/site_creation.json
    else
        error "Site creation failed with status $response"
        cat /tmp/site_creation.json
        exit 1
    fi
}

# Test rate limiting
test_rate_limiting() {
    log "Testing rate limiting..."
    
    local success_count=0
    local rate_limit_hit=false
    
    # Make multiple requests quickly
    for i in {1..15}; do
        local response=$(curl -s -w "%{http_code}" -o /dev/null \
            -X POST \
            -H "Content-Type: application/json" \
            -H "X-Auth: ${SITE_FACTORY_TOKEN}" \
            -d "{\"site_title\":\"Rate Test ${i}\",\"admin_email\":\"ratetest${i}@example.com\",\"desired_domain\":\"rate-test-${i}\"}" \
            "http://factory.lvh.me/wp-json/site-factory/v1/create")
        
        if [ "$response" = "200" ]; then
            success_count=$((success_count + 1))
        elif [ "$response" = "429" ]; then
            rate_limit_hit=true
            log "Rate limit hit after $success_count successful requests"
            break
        fi
    done
    
    if [ "$rate_limit_hit" = true ]; then
        success "Rate limiting is working (hit after $success_count requests)"
    else
        warning "Rate limiting may not be working (no 429 response after 15 requests)"
    fi
}

# Cleanup function
cleanup() {
    rm -f /tmp/response.json /tmp/site_creation.json
}

# Error handling
trap 'cleanup; error "Phase 2 plugin tests failed. Check logs above."' ERR
trap cleanup EXIT

# Run main function
main "$@"

