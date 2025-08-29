# Infrastructure Setup

This directory contains all Docker infrastructure configuration for the WordPress Multisite Factory.

## Services

### Nginx
- **Port**: 80
- **Config**: `nginx/site.conf`
- **Features**: Subdomain routing, security headers, static file caching

### PHP-FPM
- **Port**: 9000 (internal)
- **Version**: 8.3
- **Extensions**: GD, ZIP, EXIF, MySQLi, Intl, cURL, OPcache, Redis
- **Config**: `php/php.ini`

### MariaDB
- **Port**: 3306 (internal)
- **Version**: 10.11
- **Database**: `wp_factory`
- **Volumes**: Persistent data storage

### Redis
- **Port**: 6379 (internal)
- **Version**: 7
- **Purpose**: Object cache, session storage
- **Volumes**: Persistent data storage

### MailHog
- **Ports**: 8025 (web), 1025 (SMTP)
- **Purpose**: Email testing and capture
- **Access**: http://localhost:8025

### WP-CLI
- **Purpose**: WordPress management via CLI
- **Config**: `wp-cli/config.yml`
- **Mounts**: WordPress core and plugin directories

### Landing App
- **Port**: 3000
- **Purpose**: Next.js frontend for site creation
- **Command**: Auto-install dependencies and start dev server

## Configuration

### Environment Variables
All services use environment variables from `.env` file:
- Database credentials
- WordPress admin settings
- Site factory token
- PHP memory limits

### Volumes
- `db_data`: MariaDB persistent storage
- `redis_data`: Redis persistent storage
- `../wordpress`: WordPress core files
- `../wp-plugin/site-factory`: Site Factory plugin

### Networks
- `wp_network`: Bridge network for inter-service communication

## Usage

```bash
# Start all services
docker compose -f infrastructure/docker-compose.yml up -d

# View logs
docker compose -f infrastructure/docker-compose.yml logs -f

# Stop services
docker compose -f infrastructure/docker-compose.yml down

# Rebuild PHP image
docker compose -f infrastructure/docker-compose.yml build php
```

## Security Features

- Security headers in Nginx
- PHP security settings
- File upload limits
- WordPress-specific protections
- Real IP handling for rate limiting
