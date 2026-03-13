# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mysqli pdo_mysql zip

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache DocumentRoot to point to /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set working directory
WORKDIR /var/www/html

# Copy the application source code
COPY . .

# Set some basic memory limits for PHP
RUN echo "memory_limit=256M" > /usr/local/etc/php/conf.d/memory-limit.ini
RUN echo "upload_max_filesize=64M" >> /usr/local/etc/php/conf.d/memory-limit.ini
RUN echo "post_max_size=64M" >> /usr/local/etc/php/conf.d/memory-limit.ini

# Ensure storage directory exists and has correct permissions
RUN mkdir -p storage/preview storage/logs storage/cache \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage

# Expose port 80
EXPOSE 80

# The default command will start Apache in the foreground
CMD ["apache2-foreground"]
