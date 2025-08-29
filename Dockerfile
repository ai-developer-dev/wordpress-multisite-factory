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

# Railway uses PORT environment variable
ENV PORT=80

# Create simple startup script for Railway
RUN echo '#!/bin/bash\n\
PORT=${PORT:-80}\n\
echo "Starting Apache on port $PORT"\n\
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf\n\
sed -i "s/:80>/:$PORT>/g" /etc/apache2/sites-available/000-default.conf\n\
exec apache2-foreground\n\
' > /start-apache.sh && chmod +x /start-apache.sh

EXPOSE $PORT

CMD ["/start-apache.sh"]
