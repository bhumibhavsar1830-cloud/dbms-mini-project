FROM php:8.2-apache

# Enable mysqli + rewrite
RUN docker-php-ext-install mysqli && a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Set working dir
WORKDIR /var/www/html

# Apache config: allow .htaccess
RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/sagms.conf \
 && a2enconf sagms

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

