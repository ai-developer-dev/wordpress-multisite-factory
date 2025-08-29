FROM wordpress:6.4-apache

# Install PHP extensions for both MySQL and PostgreSQL
RUN docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql

# Fix Apache ServerName warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy WordPress files
COPY wordpress/ /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Enhanced startup script with debugging
RUN cat > /start.sh << 'EOF'
#!/bin/bash
set -e

echo "🚀 Starting WordPress on Railway..."
echo "📊 Environment Debug:"
echo "  - PORT: ${PORT:-80}"
echo "  - DATABASE_URL: ${DATABASE_URL:+[SET]}"
echo "  - RAILWAY_ENVIRONMENT: ${RAILWAY_ENVIRONMENT:-not-set}"

# Configure Apache port
if [ "$PORT" ]; then
    echo "🔧 Configuring Apache for port $PORT..."
    sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
    sed -i "s/:80/:$PORT/g" /etc/apache2/sites-available/000-default.conf
    echo "✅ Apache configured for port $PORT"
else
    echo "⚠️  Using default port 80"
fi

# Test Apache configuration
echo "🧪 Testing Apache configuration..."
apache2ctl configtest

# Show final configuration
echo "📄 Final Apache configuration:"
echo "  Ports.conf:"
cat /etc/apache2/ports.conf | grep Listen || echo "    No Listen directive found"
echo "  Default site:"
grep -E "(VirtualHost|Listen)" /etc/apache2/sites-available/000-default.conf || echo "    No VirtualHost found"

# Start Apache
echo "🎬 Starting Apache..."
exec apache2-foreground
EOF

RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]