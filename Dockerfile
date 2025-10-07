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

# 💡 Mover WORKDIR ANTES de COPY mejora la caché
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

# 3️⃣ Permisos de Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 4️⃣ Copiar configuraciones
COPY ./nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./nginx/supervisor.conf /etc/supervisor/conf.d/supervisor.conf
RUN chmod 644 /etc/nginx/nginx.conf /etc/supervisor/conf.d/supervisor.conf

# 5️⃣ FIX: crear rutas de logs y tmp con permisos válidos
RUN mkdir -p /var/www/html/logs \
    && mkdir -p /var/lib/nginx/tmp/scgi \
    && chown -R www-data:www-data /var/www/html/logs /var/lib/nginx/tmp \
    && chmod -R 775 /var/www/html/logs /var/lib/nginx/tmp

# 6️⃣ Rutas temporales necesarias para Render
RUN mkdir -p /opt/render/project/src/tmp/{client_temp,proxy_temp,fastcgi_temp,uwsgi_temp} \
    && chown -R www-data:www-data /opt/render/project/src/tmp

# 7️⃣ Verificar sintaxis de Nginx
RUN nginx -t || cat /var/www/html/logs/error.log || true

# 8️⃣ Ejecutar como www-data (no root)
USER www-data

# 9️⃣ Exponer puerto HTTP
EXPOSE 80

# 🔟 Iniciar con Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisor.conf"]
