# ----------------------------------------------------------------------------------
# ETAPA 1: BUILD (Construir la aplicación)
# ----------------------------------------------------------------------------------
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
    postgresql-dev \
    && docker-php-ext-install pdo_pgsql pdo_mysql mbstring zip exif pcntl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /app

# Copiar archivos del proyecto
COPY . .

# Instalar dependencias de PHP optimizadas para producción
RUN composer install --no-dev --optimize-autoloader --no-interaction

# ----------------------------------------------------------------------------------
# ETAPA 2: PRODUCCIÓN
# ----------------------------------------------------------------------------------
FROM php:8.2-fpm-alpine AS production

# Ejecutar como root temporalmente para configuración
USER root

# Instalar dependencias del sistema y extensiones PHP
RUN apk add --no-cache --virtual .build-deps \
    libzip-dev \
    libpng-dev \
    jpeg-dev \
    freetype-dev \
    oniguruma-dev \
    postgresql-dev \
    && apk add --no-cache \
    nginx \
    supervisor \
    libpng \
    libjpeg \
    freetype \
    libzip \
    postgresql-libs \
    && docker-php-ext-install pdo_pgsql pdo_mysql mbstring zip exif pcntl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && apk del .build-deps

# Copiar aplicación desde la etapa de build
COPY --from=builder /app /var/www/html

# Crear todos los directorios necesarios con estructura completa
RUN mkdir -p \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/app/public \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    /tmp/nginx_client_temp \
    /tmp/nginx_proxy_temp \
    /tmp/nginx_fastcgi_temp \
    /tmp/nginx_uwsgi_temp \
    /tmp/nginx_scgi_temp

# Asignar permisos correctos a directorios
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    /tmp/nginx_* \
    && chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    /tmp/nginx_*

# Configurar PHP para logging correcto
RUN echo "display_errors = Off" >> /usr/local/etc/php/conf.d/docker-php-errors.ini \
    && echo "display_startup_errors = Off" >> /usr/local/etc/php/conf.d/docker-php-errors.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/docker-php-errors.ini \
    && echo "error_log = /dev/stderr" >> /usr/local/etc/php/conf.d/docker-php-errors.ini \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/docker-php-errors.ini

# Copiar archivo .env (asegúrate de que exista en tu proyecto)
COPY .env /var/www/html/.env

# Dar permisos al archivo .env
RUN chown www-data:www-data /var/www/html/.env \
    && chmod 644 /var/www/html/.env

# Limpiar todos los caches de Laravel
RUN php /var/www/html/artisan config:clear || true \
    && php /var/www/html/artisan cache:clear || true \
    && php /var/www/html/artisan view:clear || true \
    && php /var/www/html/artisan route:clear || true

# Copiar configuraciones de Nginx y Supervisor
COPY ./nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./nginx/supervisor.conf /etc/supervisor/conf.d/supervisor.conf

# Dar permisos correctos a archivos de configuración
RUN chmod 644 /etc/nginx/nginx.conf \
    && chmod 644 /etc/supervisor/conf.d/supervisor.conf

# Verificar que la sintaxis de Nginx sea correcta
RUN nginx -t

# Cambiar al usuario sin privilegios (www-data)
USER www-data

# Exponer puerto HTTP
EXPOSE 80

# Comando de inicio usando Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisor.conf"]
