# ----------------------------------------------------------------------------------
# ETAPA 1: BUILD
FROM php:8.2-fpm-alpine AS builder

RUN apk add --no-cache \
    git \
    curl \
    unzip \
    libzip-dev \
    libpng-dev \
    jpeg-dev \
    freetype-dev \
    oniguruma-dev \
    supervisor \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .
RUN composer install --no-dev --optimize-autoloader

# ----------------------------------------------------------------------------------
# ETAPA 2: PRODUCCIÓN
FROM php:8.2-fpm-alpine AS production

USER root

# Instalar dependencias
RUN apk add --no-cache --virtual .build-deps \
    libzip-dev libpng-dev jpeg-dev freetype-dev oniguruma-dev \
    && apk add --no-cache nginx supervisor libpng libjpeg freetype libzip \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && apk del .build-deps

# Copiar aplicación
COPY --from=builder /app /var/www/html

# ✅ Crear SOLO los directorios necesarios
RUN mkdir -p /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    /tmp/nginx_client_temp \
    /tmp/nginx_proxy_temp \
    /tmp/nginx_fastcgi_temp \
    /tmp/nginx_uwsgi_temp \
    /tmp/nginx_scgi_temp \
    && chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    /tmp/nginx_* \
    && chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    /tmp/nginx_*

# Copiar configuraciones
COPY ./nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./nginx/supervisor.conf /etc/supervisor/conf.d/supervisor.conf

# Verificar sintaxis de Nginx
RUN nginx -t

# Cambiar a usuario sin privilegios
USER www-data

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisor.conf"]
