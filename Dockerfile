# ----------------------------------------------------------------------------------
FROM php:8.2-fpm-alpine AS builder
    # ETAPA 2: PRODUCTION (Imagen final de producción)
FROM php:8.2-fpm-alpine AS production

USER root

# 1. INSTALAR DEPENDENCIAS DE COMPILACIÓN Y EJECUCIÓN
RUN apk add --no-cache --virtual .build-deps \
    libzip-dev \
    libpng-dev \
    jpeg-dev \
    freetype-dev \
    oniguruma-dev \
    \
    # 💡 LIBRERÍAS DE EJECUCIÓN: Estas NO deben eliminarse.
    #    Nginx, Supervisor y las librerías runtime de GD (libpng, libjpeg, freetype).
    && apk add --no-cache \
    nginx \
    supervisor \
    libpng \
    libjpeg \
    freetype \
    \
    # Instalar y configurar extensiones de PHP
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    \
    # 2. LIMPIEZA: Solo eliminamos las dependencias de DESARROLLO (.build-deps)
    && apk del .build-deps

# Copiar el código final de la etapa de build
COPY --from=build /app /var/www/html

# --- CONFIGURACIÓN DE PERMISOS Y USUARIOS ---

# 3. Dar permisos a storage y cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 4. Copiar y asegurar permisos de configuración
COPY ./nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./nginx/supervisor.conf /etc/supervisor/conf.d/supervisor.conf
RUN chmod 644 /etc/nginx/nginx.conf /etc/supervisor/conf.d/supervisor.conf

# 5. Crear el directorio de logs de Nginx
RUN mkdir -p /var/log/nginx \
    && touch /var/log/nginx/error.log \
    && chown -R www-data:www-data /var/log/nginx

# 6. FINAL: Forzar la ejecución de procesos como www-data
USER www-data

# Exponer el puerto de Nginx
EXPOSE 80

# Comando para iniciar Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisor.conf"]
