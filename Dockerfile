# Use an official PHP image with Apache
FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo pdo_mysql zip sockets

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY ./app .

# Install Composer dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Expose ports
EXPOSE 80
EXPOSE 8081

# Start Apache and the WebSocket server
CMD bash -c "apache2-foreground & php /var/www/html/bin/server.php"

