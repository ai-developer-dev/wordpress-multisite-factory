#!/bin/bash
set -e

# Set the port Apache should listen on (Railway provides PORT env var)
APACHE_PORT=${PORT:-80}

echo "Starting Apache on port $APACHE_PORT"

# Update Apache configuration files to use the correct port
sed -i "s/Listen 80/Listen $APACHE_PORT/g" /etc/apache2/ports.conf
sed -i "s/:80/:$APACHE_PORT/g" /etc/apache2/sites-available/000-default.conf
sed -i "s/\*:80/*:$APACHE_PORT/g" /etc/apache2/conf-available/wordpress.conf

# Verify configuration
echo "=== Apache Port Configuration ==="
echo "PORT environment variable: $PORT"
echo "Apache will listen on: $APACHE_PORT"
echo "Ports.conf content:"
grep "Listen" /etc/apache2/ports.conf || true
echo "VirtualHost configuration:"
grep "VirtualHost" /etc/apache2/conf-available/wordpress.conf || true
echo "=================================="

# Start Apache
exec apache2-foreground