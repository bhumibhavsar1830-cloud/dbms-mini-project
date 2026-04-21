FROM php:8.2-apache
RUN docker-php-ext-install mysqli
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html && \
    a2enmod rewrite
EXPOSE 80
CMD ["/bin/bash", "-c", "a2dismod mpm_event; a2enmod mpm_prefork; apache2-foreground"]
