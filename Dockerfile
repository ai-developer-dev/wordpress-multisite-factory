FROM wordpress:6.4-php8.2-apache

# Install ALL required system packages BEFORE configuring PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libicu-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    pkg-config \
    zip \
    unzip \
    curl \
    ca-certificates \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure GD extension FIRST with all dependencies
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp

# Install PHP extensions in correct order
RUN docker-php-ext-install -j$(nproc) \
    zip \
    mysqli \
    gd \
    exif \
    intl \
    curl \
    mbstring \
    opcache

# Set PHP configuration for production
RUN echo "memory_limit = 256M" > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "upload_max_filesize = 64M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 64M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/timeout.ini

# Copy WordPress files
COPY wordpress/ /var/www/html/
COPY wp-plugin/site-factory/ /var/www/html/wp-content/plugins/site-factory/

# Ensure Railway wp-config is used
RUN cp /var/www/html/wp-config-railway.php /var/www/html/wp-config.php

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Copy custom Apache configuration
COPY apache/wordpress.conf /etc/apache2/conf-available/wordpress.conf
RUN a2enconf wordpress

# Enable required Apache modules
RUN a2enmod rewrite headers expires

# Set default port but allow Railway to override
ENV PORT=80

# Configure Apache to listen on the PORT variable
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf && \
    sed -i 's/:80>/:${PORT}>/' /etc/apache2/sites-available/000-default.conf

# Create startup script that handles dynamic port
RUN echo '#!/bin/bash\n\
# Use PORT environment variable or default to 80\n\
export PORT=${PORT:-80}\n\
# Update Apache configuration with actual port\n\
sed -i "s/Listen .*/Listen $PORT/" /etc/apache2/ports.conf\n\
sed -i "s/<VirtualHost .*>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf\n\
# Start Apache\n\
exec apache2-foreground\n\
' > /usr/local/bin/start-apache-with-port.sh && chmod +x /usr/local/bin/start-apache-with-port.sh

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost:${PORT}/ || exit 1

EXPOSE ${PORT}

CMD ["/usr/local/bin/start-apache-with-port.sh"]
