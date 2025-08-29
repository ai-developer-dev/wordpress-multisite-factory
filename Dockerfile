FROM wordpress:6.4-apache

# Copy WordPress files
COPY wordpress/ /var/www/html/

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80