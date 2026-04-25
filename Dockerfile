FROM php:8.2-apache

RUN apt-get update && \
    docker-php-ext-install mysqli && \
    a2dismod mpm_event mpm_worker mpm_prefork 2>/dev/null; \
    a2enmod mpm_prefork && \
    a2enmod rewrite

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

CMD ["apache2-foreground"]
