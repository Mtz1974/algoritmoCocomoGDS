# ----------------------------------------------------------------------------------
# ETAPA 1: BUILD (Construir la aplicación y sus assets)
# Usamos una imagen oficial de PHP con extensiones de Laravel
FROM php:8.2-fpm-alpine AS build

# Instalar dependencias del sistema necesarias
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
    # Instalar extensiones de PHP necesarias
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Definir el directorio de trabajo
WORKDIR /app

# Copiar archivos y código fuente (excepto los ignorados por .dockerignore si existe)
COPY . .

# Instalar dependencias de PHP y optimizar (sin archivos de desarrollo)
RUN composer install --no-dev --optimize-autoloader

# Ejecutar npm para construir assets (si usas Tailwind localmente)
# Si solo usas CDN, puedes comentar estas líneas:
# RUN apk add --no-cache nodejs npm
# RUN npm install
# RUN npm run build

# ⚠️ Elimina o comenta los comandos de caché. La APP_KEY no está disponible aquí.
# RUN php artisan optimize:clear
# RUN php artisan config:cache
# RUN php artisan route:cache
# RUN php artisan view:cache
# ----------------------------------------------------------------------------------
# ETAPA 2: PRODUCTION (Imagen final de producción)
# Usamos una imagen ligera para servir la aplicación
FROM php:8.2-fpm-alpine AS production

# Instalar extensiones de PHP (solo las necesarias para la ejecución)
RUN apk add --no-cache \
    nginx \
    libzip-dev \
    libpng-dev \
    jpeg-dev \
    freetype-dev \
    oniguruma-dev \
    supervisor \
    # Copiar extensiones compiladas de la etapa de build
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Copiar el código y las dependencias de la etapa 'build'
COPY --from=build /app /var/www/html

# Configurar permisos para Laravel
RUN chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage

# Copiar la configuración de Nginx (necesitarás crear este archivo)
COPY ./nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./nginx/supervisor.conf /etc/supervisor/conf.d/supervisor.conf

# Exponer el puerto de Nginx
EXPOSE 80

# Comando para iniciar Nginx y PHP-FPM
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisor.conf"]
