FROM php:8.2-apache

# Required PHP extensions for the project (PDO + MySQLi)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache rewrite module for future routing/.htaccess needs
RUN a2enmod rewrite

WORKDIR /var/www/html

# Copy application source into the container image
COPY . /var/www/html/

# Ensure Apache can read/write expected runtime directories if needed
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
