FROM php:8.1-apache

# Install necessary packages for GD extension
RUN apt-get update && \
    apt-get install -y libgd-dev libpng-dev libjpeg-dev libfreetype6-dev zip unzip && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd

# Set the document root for Apache
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Copy the custom Apache config file into the container
COPY ./config/000-default.conf /etc/apache2/sites-available/000-default.conf

# Set correct permissions in the container
RUN mkdir -p /var/www/html && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Enable the Apache mod_rewrite module
RUN a2enmod rewrite

# Restart Apache to apply changes
CMD ["apache2-foreground"]

# Copy configuration for image processing
COPY php.ini /usr/local/etc/php/

COPY fonts/ /var/www/html/fonts/

