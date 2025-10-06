# 💻 Estimador de Costos COCOMO I
Este proyecto implementa el modelo COCOMO I (Constructive Cost Model) en un entorno Laravel/Blade para estimar el esfuerzo (persona-mes), la duración (meses) y el costo total de un proyecto de software, considerando el tamaño en KLOC y 15 factores de costo (EAF).



# 🛠️ Requisitos del Sistema
Necesitas tener instalado lo siguiente:

>1. PHP (versión 8.1 o superior).

>2. Composer (gestor de dependencias de PHP).

>3. Node.js y npm (para la gestión de recursos de frontend, aunque actualmente usa un CDN para Tailwind).

>4. Laravel (el framework base del proyecto).

>🚀 Pasos de Instalación y Configuración

# Sigue estos pasos para poner en marcha el proyecto:

>1. Clonar el Repositorio

>2. git clone [(https://github.com/Mtz1974/algoritmoCocomoGDS)]

>3. cd algoritmoCocomoGDS

# Instalar Dependencias de PHP

Ejecuta Composer para instalar todas las librerías necesarias de Laravel:

>  # composer install

# Configurar el Entorno

> # cp .env.example .env

# Ejemplo:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=COCOMO
DB_USERNAME=root
DB_PASSWORD=

Genera la clave de aplicación de Laravel:

> # php artisan key:generate


# Ejecutar la Aplicación:
Inicia el servidor de desarrollo local de Laravel:

> # php artisan serve

Instalar Dependencias de Frontend (Opcional, pero recomendado)
Aunque la interfaz actual usa Tailwind CDN, si deseas usar assets locales o la versión de producción de Tailwind, instala Node.js:

npm install
npm run dev # Para desarrollo
# o
npm run build # Para producción

Levantar la base de datos:
php artisan migrate 



>💡 Uso

1. Ingreso de Datos: Completa los campos de KLOC y Salario Mensual.

2. Selección del Modo: Elige el modo del proyecto (Orgánico, Semiacoplado, Empotrado).

3. Drivers de Costo: Ajusta los 15 factores de costo (EAF) según las características de tu proyecto.

4. Calcular: Haz clic en "Calcular Estimación" para ver los resultados animados.

5. Exportar: Haz clic en "Exportar PDF" para descargar un resumen formal.
