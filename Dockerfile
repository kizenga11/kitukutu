FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql && \
    a2enmod rewrite

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html && \
    find /var/www/html -type f -exec chmod 644 {} \; && \
    find /var/www/html -type d -exec chmod 755 {} \;

EXPOSE 80

CMD ["apache2-foreground"]