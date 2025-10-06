/** @type {import('tailwindcss').Config} */
module.exports = {
  // 1. Archivos de Contenido: Le decimos a Tailwind dónde buscar tus clases.
  // Escanea todas las vistas de Blade dentro de 'resources/views'.
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],

  // 2. Configuración Temática (Opcional, pero útil para personalizar)
  theme: {
    extend: {
      colors: {
        // Define tu color primario para que coincida con el código Blade que te di
        'primary': '#4f46e5', // Indigo
        'primary-dark': '#4338ca',
        'bg-light': '#f9fafb',
        'error-color': '#ef4444',
      },
      // Puedes agregar tus propias fuentes, espaciados, etc. aquí
    },
  },

  // 3. Plugins: Generalmente usado para formularios o tipografía
  plugins: [
    require('@tailwindcss/forms'), // Recomendado para mejorar el estilo de formularios, select, etc.
  ],
};
