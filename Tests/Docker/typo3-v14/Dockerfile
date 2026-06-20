# TYPO3 v14.1 Test Environment
FROM php:8.3-apache

# Set environment variables
ENV TYPO3_VERSION=14.1
ENV PHP_VERSION=8.3
ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libicu-dev \
    libonig-dev \
    mariadb-client \
    wget \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        zip \
        pdo_mysql \
        mysqli \
        xml \
        intl \
        mbstring \
        opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure Apache
RUN a2enmod rewrite
COPY Tests/Docker/typo3-v14/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Copy composer configuration for TYPO3 v14
COPY Tests/Docker/typo3-v14/composer.json /var/www/html/composer.json

# Install TYPO3 and dependencies
# Old line: RUN composer install --no-dev --optimize-autoloader
# RUN composer install --no-dev --optimize-autoloader --ignore-platform-req=php+
# Ensure any old static locks are cleared out so the container resolves dynamically
RUN rm -f composer.lock

# Run install with relaxed platform matching
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs


# Copy TYPO3 configuration
COPY Tests/Docker/typo3-v14/LocalConfiguration.php /var/www/html/config/system/LocalConfiguration.php
COPY Tests/Docker/typo3-v14/additional.php /var/www/html/config/system/additional.php

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Start Apache
CMD ["apache2-foreground"]
