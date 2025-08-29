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

# Start Apache
echo "🎬 Starting Apache..."
exec apache2-foreground