# ==============================================================================
# Papastro Framework v3.0.0 Production Dockerfile
# High-Concurrency Hybrid PHP + Astro Island Architecture
# @author kangpcode (Dhafa Nazula Permadi)
# ==============================================================================

FROM php:8.3-cli-alpine AS base

# Install system dependencies & PHP extensions
RUN apk add --no-cache \
    sqlite-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    linux-headers \
    git \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_sqlite \
    pdo_mysql \
    pcntl \
    sockets \
    opcache \
    zip \
    gd

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure production OPcache & JIT
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache-prod.ini \
    && echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/opcache-prod.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache-prod.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache-prod.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache-prod.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache-prod.ini \
    && echo "opcache.jit=tracing" >> /usr/local/etc/php/conf.d/opcache-prod.ini \
    && echo "opcache.jit_buffer_size=128M" >> /usr/local/etc/php/conf.d/opcache-prod.ini

WORKDIR /var/www/papastro

# Copy project files
COPY . .

# Set permissions for storage & cache
RUN mkdir -p storage/logs storage/cache/views storage/framework/pasway database \
    && chmod -R 777 storage database \
    && composer install --no-dev --optimize-autoloader --prefer-dist --no-progress

EXPOSE 8000 8080 8081

# Default: Start Papastro Octane High-Concurrency Server
CMD ["php", "papastro", "runtime:start", "--host=0.0.0.0", "--port=8000", "--workers=4"]

