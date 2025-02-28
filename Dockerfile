# Use a PHP base image with Apache
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install \
    pdo_mysql \
    zip \
    gd \
    mbstring \
    exif \
    pcntl \
    bcmath

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy all application files to the container
COPY . /var/www/html

# Set permissions for the public and storage folders
RUN chown -R www-data:www-data /var/www/html/public /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 755 /var/www/html/public /var/www/html/storage /var/www/html/bootstrap/cache

# Set the working directory
WORKDIR /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# If you have any problem with the "composer install" command, you can try to run:
    # 1- docker exec -it nexopos_app bash (to enter the container)
    # 2- cd /var/www/html
    # 3- composer install --no-dev --optimize-autoloader
    # 4- chown -R www-data:www-data /var/www/html
    # 5- chmod -R 755 /var/www/html
    # 6- exit (to exit the container)
    # 7- docker compose build --no-cache
    # 8- docker compose up -d

# Configure Apache to serve the public folder
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Expose port 80
EXPOSE 80
