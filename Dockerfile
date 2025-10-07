# ----------------------------------------------------------------------------------
# ETAPA 1: BUILD (Construir la aplicación y sus assets)
FROM php:8.2-fpm-alpine AS builder

# Instalar dependencias del sistema y extensiones de PHP
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

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copiar archivos y código fuente
COPY . .

# Instalar dependencias de PHP y optimizar (sin archivos de desarrollo)
RUN composer install --no-dev --optimize-autoloader

# ----------------------------------------------------------------------------------
# ETAPA 2: PRODUCCIÓN
FROM php:8.2-fpm-alpine AS production

USER root

# 1️⃣ Instalar dependencias necesarias
RUN apk add --no-cache --virtual .build-deps \
    libzip-dev libpng-dev jpeg-dev freetype-dev oniguruma-dev \
    && apk add --no-cache nginx supervisor libpng libjpeg freetype libzip \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && apk del .build-deps

# 2️⃣ Copiar la aplicación
COPY --from=builder /app /var/www/html

# 3️⃣ Crear directorios necesarios y asignar permisos
RUN mkdir -p /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    /var/www/html/logs \
    /tmp/nginx_client_temp \
    /tmp/nginx_proxy_temp \
    /tmp/nginx_fastcgi_temp \
    /tmp/nginx_uwsgi_temp \
    /tmp/nginx_scgi_temp \
    && chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    /var/www/html/logs \
    /tmp/nginx_* \
    && chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    /var/www/html/logs \
    /tmp/nginx_*

# 4️⃣ Copiar configuraciones
COPY ./nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./nginx/supervisor.conf /etc/supervisor/conf.d/supervisor.conf

# 5️⃣ Verificar sintaxis de Nginx
RUN nginx -t

# 6️⃣ Ejecutar como www-data (no root)
USER www-data

# 7️⃣ Exponer puerto HTTP
EXPOSE 80

# 8️⃣ Iniciar con Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisor.conf"]
