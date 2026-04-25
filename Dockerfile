FROM php:8.2-apache

RUN docker-php-ext-install mysqli

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html && a2enmod rewrite

CMD ["/bin/bash", "-c", "sed -i \"s/Listen 80/Listen ${PORT:-80}/g\" /etc/apache2/ports.conf && sed -i \"s/<VirtualHost \\*:80>/<VirtualHost *:${PORT:-80}>/g\" /etc/apache2/sites-enabled/000-default.conf && a2dismod mpm_event && a2enmod mpm_prefork && apache2-foreground"]
