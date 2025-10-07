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

# Aseguramos que corra como root durante instalación
USER root

# 1️⃣ Instalar dependencias del sistema y librerías necesarias
RUN apk add --no-cache --virtual .build-deps \
    libzip-dev \
    libpng-dev \
    jpeg-dev \
    freetype-dev \
    oniguruma-dev \
    \
    && apk add --no-cache \
    nginx \
    supervisor \
    libpng \
    libjpeg \
    freetype \
    libzip \
    \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    \
    && apk del .build-deps

# 2️⃣ Copiar el código desde la etapa builder
COPY --from=builder /app /var/www/html

# 3️⃣ Permisos necesarios para Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 4️⃣ Copiar configuraciones de Nginx y Supervisor
COPY ./nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./nginx/supervisor.conf /etc/supervisor/conf.d/supervisor.conf
RUN chmod 644 /etc/nginx/nginx.conf /etc/supervisor/conf.d/supervisor.conf

# 5️⃣ FIX: Permisos de Nginx y logs (evita el “Permission denied”)
RUN mkdir -p /var/lib/nginx/logs /var/lib/nginx/tmp/scgi \
    && chown -R www-data:www-data /var/lib/nginx

# 6️⃣ FIX DEFINITIVO PARA RENDER:
# Crear las rutas temporales accesibles para Nginx en /opt/render/project/src/tmp/
RUN mkdir -p /opt/render/project/src/tmp/{client_temp,proxy_temp,fastcgi_temp,uwsgi_temp} \
    && chown -R www-data:www-data /opt/render/project/src/tmp

# 7️⃣ Verificar sintaxis de Nginx antes del deploy
RUN nginx -t || cat /var/log/nginx/error.log || true

# 8️⃣ Cambiar usuario para ejecución final (seguridad)
USER www-data

# 9️⃣ Exponer el puerto web
EXPOSE 80

# 🔟 Iniciar todos los procesos con Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisor.conf"]
