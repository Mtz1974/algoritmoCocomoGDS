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

# 1. INSTALAR DEPENDENCIAS DE COMPILACIÓN Y EJECUCIÓN
#    Instalamos temporalmente las librerías -dev para la compilación (oniguruma, etc.)
RUN apk add --no-cache --virtual .build-deps \
    libzip-dev \
    libpng-dev \
    jpeg-dev \
    freetype-dev \
    oniguruma-dev \
    \
    # Dependencias de ejecución (Nginx y Supervisor)
    && apk add --no-cache \
    nginx \
    supervisor \
    \
    # Instalar y configurar extensiones de PHP
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    \
    # 2. LIMPIEZA: Eliminar las dependencias de desarrollo (librerías -dev)
    #    Esto mantiene la imagen ligera, solo quedan las extensiones compiladas.
    && apk del .build-deps

# Copiar el código final de la etapa de build
COPY --from=build /app /var/www/html

# --- CONFIGURACIÓN DE PERMISOS Y USUARIOS (CRÍTICO) ---
# 3. Dar permisos a storage y cache
RUN adduser -D -u 82 www-data \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 4. Copiar y asegurar permisos de configuración
COPY ./nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./nginx/supervisor.conf /etc/supervisor/conf.d/supervisor.conf
RUN chmod 644 /etc/nginx/nginx.conf /etc/supervisor/conf.d/supervisor.conf

# 5. Crear el directorio de logs de Nginx para evitar fallas de permisos al iniciar
RUN mkdir -p /var/log/nginx \
    && touch /var/log/nginx/error.log \
    && chown -R www-data:www-data /var/log/nginx

# 6. Forzar la ejecución de procesos como www-data
USER www-data

# Exponer el puerto de Nginx
EXPOSE 80

# Comando para iniciar Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisor.conf"]
