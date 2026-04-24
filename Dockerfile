# Use the official PHP Apache base image
FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite (often needed for PHP routers)
RUN a2enmod rewrite

# Copy the entire project into the Apache document root
COPY . /var/www/html/

# Set appropriate permissions for the web root
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80 (Standard for Render Web Services)
EXPOSE 80
