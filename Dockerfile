FROM wordpress:6.4-apache

# Install PHP extensions for both MySQL and PostgreSQL
RUN docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql

# Fix Apache ServerName warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy WordPress files
COPY wordpress/ /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Copy startup script
COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]