FROM wordpress:6.4-apache

# Copy WordPress files
COPY wordpress/ /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Simple port handling for Railway
RUN echo '#!/bin/bash' > /entrypoint.sh && \
    echo 'if [ "$PORT" ]; then' >> /entrypoint.sh && \
    echo '  sed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf' >> /entrypoint.sh && \
    echo '  sed -i "s/80/$PORT/g" /etc/apache2/ports.conf' >> /entrypoint.sh && \
    echo 'fi' >> /entrypoint.sh && \
    echo 'apache2-foreground' >> /entrypoint.sh && \
    chmod +x /entrypoint.sh

EXPOSE 80

CMD ["/entrypoint.sh"]