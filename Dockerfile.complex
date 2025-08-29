FROM wordpress:6.4-php8.2-apache

# Install required system packages for PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libicu-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    zip \
    unzip \
    curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    zip \
    mysqli \
    gd \
    exif \
    intl \
    curl \
    mbstring

# Set PHP configuration for production
RUN echo "memory_limit = 256M" > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "upload_max_filesize = 64M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 64M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/timeout.ini

# Copy WordPress files
COPY wordpress/ /var/www/html/
COPY wp-plugin/site-factory/ /var/www/html/wp-content/plugins/site-factory/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Copy custom Apache configuration
COPY apache/wordpress.conf /etc/apache2/conf-available/wordpress.conf
RUN a2enconf wordpress

# Copy startup script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Enable required Apache modules
RUN a2enmod rewrite headers expires

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost:${PORT:-80}/health.php || exit 1

EXPOSE 80

# Use custom entrypoint script
CMD ["/usr/local/bin/docker-entrypoint.sh"]
