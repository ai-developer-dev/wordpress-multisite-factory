#!/bin/bash
set -e

# Set the port Apache should listen on (Railway provides PORT env var)
APACHE_PORT=${PORT:-80}

echo "🚀 Starting Apache on port $APACHE_PORT (Railway PORT: $PORT)"

# Backup original configurations if not already done
if [ ! -f /etc/apache2/ports.conf.orig ]; then
    cp /etc/apache2/ports.conf /etc/apache2/ports.conf.orig
fi
if [ ! -f /etc/apache2/conf-available/wordpress.conf.orig ]; then
    cp /etc/apache2/conf-available/wordpress.conf /etc/apache2/conf-available/wordpress.conf.orig
fi

# Update Apache configuration files to use the correct port
echo "📝 Updating Apache configuration for port $APACHE_PORT..."

# Update ports.conf
sed -i "s/Listen [0-9]\+/Listen $APACHE_PORT/g" /etc/apache2/ports.conf

# Update default site
if [ -f /etc/apache2/sites-available/000-default.conf ]; then
    sed -i "s/:80/:$APACHE_PORT/g" /etc/apache2/sites-available/000-default.conf
fi

# Update WordPress configuration - try multiple patterns
sed -i "s/\*:80/*:$APACHE_PORT/g" /etc/apache2/conf-available/wordpress.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$APACHE_PORT>/g" /etc/apache2/conf-available/wordpress.conf

# Test Apache configuration
echo "🔧 Testing Apache configuration..."
apache2ctl configtest

# Verify configuration
echo "=== 📊 Apache Port Configuration ==="
echo "🔢 PORT environment variable: $PORT"
echo "🎯 Apache will listen on: $APACHE_PORT"
echo "📄 Ports.conf content:"
cat /etc/apache2/ports.conf | grep -E "(Listen|#)"
echo "🏠 VirtualHost configuration:"
grep -n "VirtualHost" /etc/apache2/conf-available/wordpress.conf || echo "❌ No VirtualHost found!"
echo "=================================="

# Start Apache with error handling
echo "🎬 Starting Apache..."
exec apache2-foreground