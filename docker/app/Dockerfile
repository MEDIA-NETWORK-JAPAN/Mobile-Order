FROM php:8.2-fpm-alpine

# 必要なパッケージとPHP拡張機能をインストール
RUN apk add --no-cache \
    mysql-client \
    redis \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    oniguruma-dev \
    supervisor \
    autoconf \
    g++ \
    make \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli \
        opcache \
        pcntl \
        bcmath \
        gd \
        zip \
        mbstring

# Redis拡張機能
RUN pecl install redis \
    && docker-php-ext-enable redis \
    && apk del autoconf g++ make

# Composerインストール
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# 作業ディレクトリ設定
WORKDIR /var/www/html

# アプリケーションファイルをコピー
COPY . .

# Composer依存関係インストール
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Laravel最適化（本番用） - 設定キャッシュ無効、ルートとビューのみキャッシュ
RUN php artisan config:clear \
    && php artisan route:cache \
    && php artisan view:cache

# 権限設定
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# PHP-FPM設定
COPY docker/app/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/app/php.ini /usr/local/etc/php/conf.d/mobile-order.ini

# Supervisord設定（Nginx + PHP-FPMの同時実行）
COPY docker/app/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ヘルスチェックスクリプト
COPY docker/app/healthcheck.php /var/www/html/public/healthcheck.php

# 実行ユーザー
USER www-data

EXPOSE 9000

# PHP-FPMを直接実行（ECS Fargateでは各コンテナが独立して動作）
CMD ["php-fpm"]