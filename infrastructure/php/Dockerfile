FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libicu-dev \
    libcurl4-openssl-dev \
    libexif-dev \
    libonig-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        zip \
        exif \
        mysqli \
        intl \
        curl \
        opcache \
        mbstring

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy PHP configuration
COPY php.ini /usr/local/etc/php/

# Create uploads directory
RUN mkdir -p /var/www/html/wp-content/uploads/site-factory-logs

# Set working directory
WORKDIR /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000

CMD ["php-fpm"]

