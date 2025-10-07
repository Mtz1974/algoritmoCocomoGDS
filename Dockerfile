# ----------------------------------------------------------------------------------
# ETAPA 1: BUILD (Construir la aplicación y sus assets)
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

# ----------------------------------------------------------------------------------
# ETAPA 2: PRODUCTION (Imagen final de producción)
FROM php:8.2-fpm-alpine AS production

# 💡 ASEGURAMOS USUARIO ROOT para instalar y gestionar archivos.
USER root

# 1. INSTALAR DEPENDENCIAS DE COMPILACIÓN Y EJECUCIÓN (soluciona oniguruma)
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
    \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    \
    && apk del .build-deps

# Copiar el código final de la etapa de build
COPY --from=build /app /var/www/html

# --- CONFIGURACIÓN DE PERMISOS Y USUARIOS (CRÍTICO) ---

# 2. 💡 CORRECCIÓN FINAL: Eliminamos 'adduser' y solo gestionamos permisos (Como ROOT)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 3. Copiar y asegurar permisos de configuración (Como ROOT)
COPY ./nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./nginx/supervisor.conf /etc/supervisor/conf.d/supervisor.conf
RUN chmod 644 /etc/nginx/nginx.conf /etc/supervisor/conf.d/supervisor.conf

# 4. Crear el directorio de logs de Nginx para evitar fallas de permisos (Como ROOT)
RUN mkdir -p /var/log/nginx \
    && touch /var/log/nginx/error.log \
    && chown -R www-data:www-data /var/log/nginx

# 5. FINAL: Forzar la ejecución de procesos como www-data
USER www-data

# Exponer el puerto de Nginx
EXPOSE 80

# Comando para iniciar Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisor.conf"]
