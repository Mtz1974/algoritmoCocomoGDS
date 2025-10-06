# ----------------------------------------------------------------------------------
# ETAPA 1: BUILD (Construir la aplicación y sus assets)
# Usamos una imagen oficial de PHP 8.2 en Alpine para ligereza
FROM php:8.2-fpm-alpine AS build

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
    # Instalar extensiones de PHP
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Definir el directorio de trabajo
WORKDIR /app

# Copiar archivos y código fuente
COPY . .

# Instalar dependencias de PHP y optimizar (sin archivos de desarrollo)
RUN composer install --no-dev --optimize-autoloader

# ⚠️ OMITIR COMANDOS DE CACHÉ (php artisan config:cache, etc.)
#    Render inyecta la APP_KEY después del build, lo que causaría fallos aquí.

# ----------------------------------------------------------------------------------
# ETAPA 2: PRODUCTION (Imagen final de producción)
FROM php:8.2-fpm-alpine AS production

# Instalar dependencias de ejecución (incluyendo Nginx)
RUN apk add --no-cache \
    nginx \
    supervisor \
    # Reinstalar extensiones para la imagen final
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Copiar el código final de la etapa de build
COPY --from=build /app /var/www/html

# --- CONFIGURACIÓN DE PERMISOS Y USUARIOS (CRÍTICO) ---
# 1. Crear el usuario www-data (si no existe) y dar permisos a storage
RUN adduser -D -u 82 www-data \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 2. Copiar la configuración de Nginx y Supervisor
COPY ./nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./nginx/supervisor.conf /etc/supervisor/conf.d/supervisor.conf

# 3. Asegurar permisos de lectura para los archivos de configuración
RUN chmod 644 /etc/nginx/nginx.conf /etc/supervisor/conf.d/supervisor.conf

# 4. PASO PARA EVITAR ERRORES DE LOGS: Crear el directorio de logs de Nginx
#    Esto evita que Nginx falle al intentar escribir en un directorio inexistente o sin permisos.
RUN mkdir -p /var/log/nginx \
    && touch /var/log/nginx/error.log \
    && chown -R www-data:www-data /var/log/nginx

# 5. Forzar la ejecución de procesos como www-data
USER www-data

# Exponer el puerto de Nginx
EXPOSE 80

# Comando para iniciar Supervisor (que a su vez inicia Nginx y PHP-FPM)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisor.conf"]
