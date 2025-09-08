# ================================================
# Mobile Order System - Multi-stage PHP-FPM Build
# ベストプラクティス: セキュリティ・パフォーマンス最適化
# ================================================

# =======================================
# Stage 1: Composer Dependencies Build
# =======================================
FROM composer:2.6 AS composer-build

WORKDIR /app

# Copy composer files first (Docker layer caching optimization)
COPY composer.json composer.lock ./

# Install dependencies (optimize for production)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --optimize-autoloader \
    --prefer-dist \
    --no-interaction

# Copy application source and finalize autoloader
COPY . .
RUN composer dump-autoload --optimize

# =======================================
# Stage 2: Production Runtime
# =======================================
FROM php:8.2-fpm-alpine AS production

# Install system dependencies and PHP extensions (single layer)
RUN apk add --no-cache \
    mysql-client \
    redis \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    oniguruma-dev \
    curl \
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        opcache \
        pcntl \
        bcmath \
        gd \
        zip \
        mbstring \
    && apk add --no-cache --virtual .build-deps \
        autoconf \
        g++ \
        make \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

# Create application directory and user
RUN addgroup -g 1000 -S appgroup \
    && adduser -u 1000 -S appuser -G appgroup

WORKDIR /var/www/html

# Copy optimized application from composer stage
COPY --from=composer-build --chown=appuser:appgroup /app .

# Production PHP configuration
COPY aws/application/containers/config/php.ini /usr/local/etc/php/conf.d/mobile-order.ini
COPY aws/application/containers/config/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY aws/application/containers/config/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Laravel optimization for production
RUN php artisan config:clear \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan event:cache

# Set proper permissions (security hardening)
RUN chown -R appuser:appgroup /var/www/html \
    && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 644 /var/www/html/public \
    && find /var/www/html -type d -exec chmod 755 {} \;

# Health check endpoint
COPY aws/application/containers/healthcheck.php /var/www/html/public/healthcheck.php

# Security: Run as non-root user
USER appuser

EXPOSE 9000

# Health check configuration
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD php-fpm -t && php /var/www/html/public/healthcheck.php || exit 1

# Production command
CMD ["php-fpm"]