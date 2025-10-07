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

# 💡 CRÍTICO: Mover WORKDIR ANTES de COPY para solucionar el error de caché.
WORKDIR /app

# Copiar archivos y código fuente
COPY . .

# Instalar dependencias de PHP y optimizar (sin archivos de desarrollo)
RUN composer install --no-dev --optimize-autoloader

# ----------------------------------------------------------------------------------
# ----------------------------------------------------------------------------------
# ETAPA 2: PRODUCTION (Imagen final de producción)
FROM php:8.2-fpm-alpine AS production

# ASEGURAMOS USUARIO ROOT para instalar y gestionar archivos.
USER root

# 1. INSTALAR DEPENDENCIAS DE COMPILACIÓN Y EJECUCIÓN
RUN apk add --no-cache --virtual .build-deps \
    libzip-dev \
    libpng-dev \
    jpeg-dev \
    freetype-dev \
    oniguruma-dev \
    \
    # LIBRERÍAS DE EJECUCIÓN (permanecen):
    && apk add --no-cache \
    nginx \
    supervisor \
    libpng \
    libjpeg \
    freetype \
    libzip \
    \
    # Instalar y configurar extensiones de PHP
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    \
    # 2. LIMPIEZA: Solo eliminamos las dependencias de DESARROLLO
    && apk del .build-deps

# Copiar el código final de la etapa 'builder'
COPY --from=builder /app /var/www/html

# --- CONFIGURACIÓN DE PERMISOS Y USUARIOS (CRÍTICO) ---

# 3. Dar permisos a storage y cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 4. Copiar y asegurar permisos de configuración
COPY ./nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./nginx/supervisor.conf /etc/supervisor/conf.d/supervisor.conf
RUN chmod 644 /etc/nginx/nginx.conf /etc/supervisor/conf.d/supervisor.conf

# 5. ❌ PASO ELIMINADO: Ya no necesitamos crear logs manualmente.
#    (El paso #22 anterior se elimina aquí)

# 6. FINAL: Forzar la ejecución de procesos como www-data
USER www-data

# Exponer el puerto de Nginx
EXPOSE 80

# Comando para iniciar Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisor.conf"]
