# ---------------------------------------------
# PHP 8.2 + Node 18 + Composer  (Alpine edition)
# ---------------------------------------------
FROM php:8.2-fpm-alpine

# 🧰 Base tools & PHP extensions
RUN apk add --no-cache \
    git curl zip unzip libpng-dev libjpeg-turbo-dev freetype-dev \
    libzip-dev postgresql-dev \
    oniguruma-dev icu-dev bash npm nodejs

# 🧩 Enable PHP extensions
RUN docker-php-ext-configure zip && \
    docker-php-ext-install pdo pdo_pgsql mbstring bcmath zip gd

# 🎼 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 👤 Non-root user
RUN addgroup -g 1000 www && adduser -G www -u 1000 -D www
USER www

WORKDIR /var/www
EXPOSE 9000
CMD ["php-fpm"]
