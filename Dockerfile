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
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN echo '<Directory /var/www/html/public>\n\tOptions Indexes FollowSymLinks\n\tAllowOverride All\n\tRequire all granted\n</Directory>' >> /etc/apache2/apache2.conf

# Configure Apache to listen on port 8080 instead of 80
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/:80/:8080/' /etc/apache2/sites-available/*.conf

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
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage

# Switch to non-root user
USER www-data

# Expose port 8080
EXPOSE 8080

# The default command will start Apache in the foreground
CMD ["apache2-foreground"]
