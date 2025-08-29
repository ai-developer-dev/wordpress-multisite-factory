FROM wordpress:6.4-apache

# Install basic PHP extensions
RUN docker-php-ext-install mysqli

# Copy WordPress files
COPY wordpress/ /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Simple PORT handling for Railway
ENV APACHE_HTTP_PORT=80
RUN echo 'if [ "$PORT" ]; then sed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf; fi; apache2-foreground' > /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/bin/bash", "/start.sh"]