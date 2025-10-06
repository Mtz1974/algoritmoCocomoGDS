<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Estimador COCOMO I</title>
    <meta name="viewport" content="width=device-width,initial-scale=1.0">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Configuración para colores en Dark Mode
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#6366f1', /* Indigo (para botones y acentos) */
                        'primary-dark': '#4f46e5',
                        'bg-dark': '#111827', /* Background Negro/Muy Oscuro (RGB 17, 24, 39) */
                        'card-dark': '#1f2937', /* Fondo de tarjeta (Gris muy oscuro) */
                        'error-color': '#f87171', /* Rojo más brillante para el modo oscuro */
                        'success-color': '#34d399', /* Verde para Esfuerzo */
                        'warning-color': '#fbbf24', /* Amarillo para TDEV */


                    }
                }
            }
        }
    </script>
    <style>
        /* Estilos personalizados y Animaciones */
        @keyframes spin { 0% { transform: rotate(0deg) } 100% { transform: rotate(360deg) } }
        .loading-spinner {
            display: none;
            border: 4px solid #374151; /* gray-700 */
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            width: 1.25rem;
            height: 1.25rem;
            animation: spin 1s linear infinite;
        }

        /* Animación de entrada de resultados: Slide-in desde la izquierda */
        .results-card {
            opacity: 0;
            transform: translateX(-50px); /* Empieza 50px a la izquierda */
            transition: opacity 0.7s ease-out, transform 0.7s ease-out; /* Transición más lenta y fluida */
        }
        .results-card.fade-in {
            opacity: 1;
            transform: translateX(0);
        }

        /* Estilos de tabla y elementos resaltados */
        .highlight-row {
            background-color: #374151 !important; /* Gris oscuro para resaltar */
            font-weight: 600;
        }
        .stat-card {
            @apply p-4 rounded-xl shadow-xl transition duration-300 border-t-4 bg-card-dark;
        }
    </style>
</head>

<body class="bg-bg-dark p-4 sm:p-8 text-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-extrabold text-white mb-6 border-b border-gray-700 pb-2">💻 Estimador de Costos — COCOMO I</h1>

        {{-- ERRORES DE VALIDACIÓN --}}
        @if ($errors->any())
            <div class="bg-red-900 bg-opacity-30 border-l-4 border-error-color text-red-300 p-4 mb-6 rounded-lg shadow-sm">
                @foreach ($errors->all() as $e)
                    <div class="text-sm font-medium">• {{ $e }}</div>
                @endforeach
            </div>
        @endif

        {{-- FORMULARIO PRINCIPAL --}}
        <form class="bg-card-dark p-6 sm:p-8 rounded-xl shadow-2xl mb-8" method="POST" action="{{ route('cocomo.calculate') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach (['kloc' => 'KLOC (miles de líneas de código)', 'mode' => 'Modo del proyecto (COCOMO I)', 'salary' => 'Salario mensual (por persona) en $'] as $key => $label)
                <div>
                    <label for="{{ $key }}" class="block text-sm font-medium text-gray-400 mb-2">{{ $label }}</label>
                    @if ($key === 'mode')
                        <select id="mode" name="mode" class="mt-1 block w-full rounded-lg border border-gray-600 px-4 py-2 focus:ring-primary focus:border-primary shadow-sm transition duration-150 bg-gray-700 text-gray-100">
                            @foreach ($modes ?? [] as $m)
                                <option value="{{ $m }}" @selected(old('mode', $defaults['mode'] ?? 'organico') === $m)>
                                    {{ ucfirst($m) }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="number" id="{{ $key }}" name="{{ $key }}" step="0.01"
                            value="{{ old($key, $defaults[$key] ?? ($key === 'kloc' ? 40 : 500000)) }}" required
                            class="mt-1 block w-full rounded-lg border border-gray-600 px-4 py-2 focus:ring-primary focus:border-primary shadow-sm transition duration-150 bg-gray-700 text-gray-100">
                    @endif
                </div>
                @endforeach
            </div>

            <h3 class="text-xl font-bold text-gray-300 mt-8 mb-2">15 Factores de Costo (EAF)</h3>
            <p class="text-sm text-gray-400 mb-6">Elegí el nivel para cada driver.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($driversTable ?? [] as $drv => $levels)
                    <div class="bg-gray-800 p-3 rounded-lg border border-gray-700 shadow-lg hover:shadow-xl transition duration-200">
                        <label class="block text-sm font-medium text-gray-400 mb-1">{{ $drv }}</label>
                        @php $sel = old("drivers.$drv", ($defaults['drivers'][$drv] ?? 'nominal')); @endphp
                        <select name="drivers[{{ $drv }}]"
                            class="block w-full rounded-lg border border-gray-600 px-3 py-2 focus:ring-primary focus:border-primary shadow-sm transition duration-150 bg-gray-700 text-gray-100 text-sm">
                            @foreach ($levels as $level => $mult)
                                @if (!is_null($mult))
                                    <option value="{{ $level }}" @selected($sel === $level)>
                                        {{ str_replace('_', ' ', ucfirst($level)) }} (×{{ $mult }})
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>

            <div class="actions flex items-center flex-wrap gap-4 mt-8 pt-4 border-t border-gray-700">
                <button class="bg-primary hover:bg-primary-dark text-white font-semibold py-3 px-8 rounded-xl shadow-lg hover:shadow-xl transition duration-200 transform hover:scale-[1.01]"
                    type="submit" onclick="showLoading(event)">
                    Calcular Estimación
                </button>

                <button class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-8 rounded-xl shadow-lg hover:shadow-xl transition duration-200 transform hover:scale-[1.01]"
                    type="submit" formaction="{{ route('cocomo.pdf') }}" formtarget="_blank">
                    Exportar PDF
                </button>

                <div id="loading-spinner" class="loading-spinner"></div>

                <span class="text-sm text-gray-500 ml-auto hidden sm:block">
                    Tip: Cambia drivers para ver la sensibilidad del EAF.
                </span>
            </div>
        </form>

        {{-- RESULTADOS DEL MODO ELEGIDO --}}
        @isset($result)
            <div class="bg-card-dark p-6 sm:p-8 rounded-xl shadow-2xl mb-8 results-card" id="result-card">
                <h3 class="text-2xl font-bold text-primary mb-6">📊 Resultados (Modo: {{ ucfirst($result['inputs']['mode']) }})</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

                    <div class="stat-card border-success-color">
                        <p class="text-xs font-semibold uppercase text-gray-400">Esfuerzo Total</p>
                        <p class="text-3xl font-extrabold text-success-color mt-1">{{ number_format($result['pm'], 2) }}</p>
                        <p class="text-sm text-gray-400">persona-mes</p>
                    </div>

                    <div class="stat-card border-warning-color">
                        <p class="text-xs font-semibold uppercase text-gray-400">Duración del Proyecto</p>
                        <p class="text-3xl font-extrabold text-warning-color mt-1">{{ number_format($result['tdev'], 2) }}</p>
                        <p class="text-sm text-gray-400">meses</p>
                    </div>

                    <div class="stat-card border-blue-500">
                        <p class="text-xs font-semibold uppercase text-gray-400">Personas promedio</p>
                        <p class="text-3xl font-extrabold text-blue-400 mt-1">{{ number_format($result['p'], 2) }}</p>
                        <p class="text-sm text-gray-400">desarrolladores</p>
                    </div>
<div class="stat-card col-span-2
             border-t-4 border-violet-600 dark:border-fuchsia-600">

    <p class="text-xs font-semibold uppercase
              text-gray-500 dark:text-gray-400">Costo Total Estimado</p>

    <p class="text-4xl font-extrabold text-violet-600 dark:text-fuchsia-400 mt-1">
        ${{ number_format($result['cost'], 0, ',', '.') }}
    </p>

    <p class="text-sm text-gray-600 dark:text-gray-400">moneda local</p>
</div>

                </div>

                <div class="text-right text-sm text-gray-400 mt-4 mb-6">
                    <span class="font-semibold text-gray-300">EAF (Factor de Ajuste):</span> {{ number_format($result['eaf'], 3) }}
                </div>


                <h4 class="text-lg font-bold text-gray-300 mt-6 mb-3 border-t border-gray-700 pt-4">Multiplicadores usados (Composición del EAF)</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-700 rounded-lg border border-gray-700 shadow-sm">
                        <thead class="bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider rounded-tl-lg">Driver</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Nivel Elegido</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider rounded-tr-lg">× Multiplicador</th>
                            </tr>
                        </thead>
                        <tbody class="bg-gray-800 divide-y divide-gray-700">
                            @foreach ($result['used_multipliers'] as $drv => $row)
                                <tr class="hover:bg-gray-700 transition duration-150">
                                    <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-200">{{ $drv }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-400">{{ str_replace('_', ' ', ucfirst($row['level'])) }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm font-semibold text-gray-200">×{{ $row['mult'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endisset

        {{-- ⚖️ TABLA COMPARATIVA ENTRE MODOS --}}
        @isset($comparison)
            <div class="bg-card-dark p-6 sm:p-8 rounded-xl shadow-2xl results-card" id="comparison-card">
                <h3 class="text-2xl font-bold text-gray-300 mb-4">⚖️ Comparación entre modos</h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-700 rounded-lg border border-gray-700 shadow-md">
                        <thead class="bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider rounded-tl-lg">Modo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">PM</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">TDEV (meses)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Personas (P)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Costo (C)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider rounded-tr-lg">EAF</th>
                            </tr>
                        </thead>
                        <tbody class="bg-gray-800 divide-y divide-gray-700">
                            @foreach (['organico', 'semiacoplado', 'empotrado'] as $m)
                                @php $row = $comparison[$m] ?? null; @endphp
                                @if ($row)
                                    <tr @if (isset($result['inputs']['mode']) && $result['inputs']['mode'] === $m) class="highlight-row" @endif class="hover:bg-gray-700 transition duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-200">{{ ucfirst($m) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">{{ number_format($row['pm'], 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">{{ number_format($row['tdev'], 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">{{ number_format($row['p'], 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-400">${{ number_format($row['cost'], 2, ',', '.') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">{{ number_format($row['eaf'], 3) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-500 mt-4">La fila resaltada corresponde al modo elegido en el formulario. Los resultados usan los mismos KLOC y EAF.</p>
            </div>
        @endisset
    </div>

    <script>
        function showLoading(event) {
            if (event.target.formAction && event.target.formAction.endsWith('calculate')) {
                // Muestra el spinner y deshabilita los botones
                document.getElementById('loading-spinner').style.display = 'inline-block';
                document.querySelectorAll('button[type="submit"]').forEach(button => {
                    button.disabled = true;
                    if (button.textContent.includes('Calcular')) {
                        button.textContent = 'Calculando...';
                    }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const resultCard = document.getElementById('result-card');
            const comparisonCard = document.getElementById('comparison-card');

            // 1. Animación de aparición para la tarjeta de resultados principal
            if (resultCard) {
                setTimeout(() => {
                    resultCard.classList.add('fade-in');
                }, 50);
            }

            // 2. Animación para la tarjeta de comparación con un delay mayor para el efecto de cascada
            if (comparisonCard) {
                 setTimeout(() => {
                    comparisonCard.classList.add('fade-in');
                }, 350);
            }
        });
    </script>
</body>

</html>
