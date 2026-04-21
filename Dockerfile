FROM php:8.2-apache
RUN docker-php-ext-install mysqli
RUN sed -i 's/^#LoadModule rewrite_module/LoadModule rewrite_module/' /etc/apache2/apache2.conf || true
COPY . /var/www/html/
EXPOSE 80
CMD ["/bin/bash", "-c", "a2dismod mpm_event; a2enmod mpm_prefork; apache2-foreground"]
