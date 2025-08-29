#!/bin/bash

# Common script helpers for WordPress Multisite Factory
# Source this file in other scripts: source ./scripts/common.sh

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper functions
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
}

# Docker Compose shortcut
dc() {
    docker compose -f infrastructure/docker-compose.yml "$@"
}

# WP-CLI shortcut
wp() {
    dc run --rm wpcli wp "$@"
}

# Wait for database to be ready
wait_db() {
    log "Waiting for database to be ready..."
    local max_attempts=30
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        if dc exec db mysqladmin ping -h localhost -u root -p"${DB_ROOT_PASSWORD}" --silent 2>/dev/null; then
            success "Database is ready!"
            return 0
        fi
        
        log "Attempt $attempt/$max_attempts: Database not ready yet..."
        sleep 2
        attempt=$((attempt + 1))
    done
    
    error "Database failed to start after $max_attempts attempts"
    return 1
}

# Check if service is running
check_service() {
    local service=$1
    if dc ps --format json | jq -s -e ".[] | select(.Service == \"$service\" and .State == \"running\")" >/dev/null 2>&1; then
        return 0
    else
        return 1
    fi
}

# Wait for service to be ready
wait_service() {
    local service=$1
    local max_attempts=30
    local attempt=1
    
    log "Waiting for $service to be ready..."
    
    while [ $attempt -le $max_attempts ]; do
        if check_service "$service"; then
            success "$service is ready!"
            return 0
        fi
        
        log "Attempt $attempt/$max_attempts: $service not ready yet..."
        sleep 2
        attempt=$((attempt + 1))
    done
    
    error "$service failed to start after $max_attempts attempts"
    return 1
}

# Load environment variables
load_env() {
    if [ -f .env ]; then
        # Source the .env file instead of exporting to avoid issues with special characters
        set -a
        source .env
        set +a
        log "Environment loaded from .env"
    else
        error ".env file not found. Please copy env.example to .env and configure it."
        exit 1
    fi
}

# Check required tools
check_requirements() {
    local missing_tools=()
    
    for tool in docker jq curl; do
        if ! command -v "$tool" >/dev/null 2>&1; then
            missing_tools+=("$tool")
        fi
    done
    
    if [ ${#missing_tools[@]} -ne 0 ]; then
        error "Missing required tools: ${missing_tools[*]}"
        error "Please install them and try again."
        exit 1
    fi
    
    success "All required tools are available"
}

# Test network connectivity
test_connectivity() {
    log "Testing network connectivity..."
    
    # Test lvh.me resolution
    if ! ping -c 1 factory.lvh.me >/dev/null 2>&1; then
        warning "factory.lvh.me is not resolving. Please add to /etc/hosts:"
        warning "127.0.0.1 factory.lvh.me"
        warning "127.0.0.1 *.factory.lvh.me"
    else
        success "factory.lvh.me resolves correctly"
    fi
}

# Main setup function
setup() {
    log "Starting WordPress Multisite Factory setup..."
    
    # Check requirements
    check_requirements
    
    # Load environment
    load_env
    
    # Test connectivity
    test_connectivity
    
    success "Setup checks completed"
}
