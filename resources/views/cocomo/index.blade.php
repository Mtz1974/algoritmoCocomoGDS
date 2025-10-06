<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Estimador COCOMO I</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        :root {
            --bg: #f7f7f7;
            --card: #fff;
            --text: #111;
            --muted: #666;
            --err: #b00020;
            --border: #e6e6e6
        }

        * {
            box-sizing: border-box
        }

        body {
            font-family: system-ui, Segoe UI, Roboto, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 24px;
            max-width: 1120px;
            margin-inline: auto
        }

        h1 {
            margin: 0 0 16px
        }

        h3,
        h4 {
            margin: 0 0 10px
        }

        .card {
            background: var(--card);
            padding: 16px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .06);
            margin-bottom: 18px
        }

        .grid {
            display: grid;
            gap: 12px
        }

        .g3 {
            grid-template-columns: repeat(3, 1fr)
        }

        label {
            font-weight: 600;
            display: block;
            margin: 4px 0
        }

        input,
        select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #fff
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px
        }

        th,
        td {
            padding: 8px 10px;
            border-bottom: 1px solid var(--border);
            text-align: left
        }

        th {
            background: #fafafa
        }

        .btn {
            background: #111;
            color: #fff;
            border: none;
            padding: 10px 14px;
            border-radius: 10px;
            cursor: pointer;
            margin-right: 8px
        }

        .btn:disabled {
            opacity: .6;
            cursor: not-allowed
        }

        .muted {
            color: var(--muted)
        }

        .err {
            color: var(--err);
            font-weight: 600
        }

        .hl {
            background: #f2f7ff
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap
        }

        @media (max-width:900px) {
            .g3 {
                grid-template-columns: 1fr
            }
        }
    </style>
</head>

<body>
    <h1>💻 Estimador de Costos — COCOMO I</h1>

    {{-- ERRORES DE VALIDACIÓN --}}
    @if ($errors->any())
        <div class="card" style="border-left:4px solid var(--err)">
            @foreach ($errors->all() as $e)
                <div class="err">• {{ $e }}</div>
            @endforeach
        </div>
    @endif

    {{-- FORMULARIO PRINCIPAL --}}
    <form class="card" method="POST" action="{{ route('cocomo.calculate') }}">
        @csrf

        <div class="grid g3">
            <div>
                <label for="kloc">KLOC (miles de líneas de código)</label>
                <input type="number" id="kloc" name="kloc" step="0.0001"
                    value="{{ old('kloc', $defaults['kloc'] ?? 40) }}" required>
            </div>

            <div>
                <label for="mode">Modo</label>
                <select id="mode" name="mode">
                    @foreach ($modes ?? [] as $m)
                        <option value="{{ $m }}" @selected(old('mode', $defaults['mode'] ?? 'organico') === $m)>
                            {{ ucfirst($m) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="salary">Salario mensual (por persona)</label>
                <input type="number" id="salary" name="salary" step="0.01"
                    value="{{ old('salary', $defaults['salary'] ?? 500000) }}" required>
            </div>
        </div>

        <h3 style="margin-top:12px">15 Factores de Costo (EAF)</h3>
        <p class="muted" style="margin-top:-6px">Elegí el nivel para cada driver. El EAF es el producto de todos los
            multiplicadores.</p>

        <div class="grid" style="grid-template-columns:repeat(3,1fr)">
            @foreach ($driversTable ?? [] as $drv => $levels)
                <div>
                    <label>{{ $drv }}</label>
                    @php $sel = old("drivers.$drv", ($defaults['drivers'][$drv] ?? 'nominal')); @endphp
                    <select name="drivers[{{ $drv }}]">
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

        <div class="actions" style="margin-top:16px">
            <button class="btn" type="submit">Calcular</button>

            {{-- Botón PDF: reutiliza los valores del formulario y abre en nueva pestaña --}}
            <button class="btn" type="submit" formaction="{{ route('cocomo.pdf') }}" formtarget="_blank">
                Exportar PDF
            </button>

            <span class="muted">Tip: Cambiá drivers para ver sensibilidad del EAF y compará modos abajo.</span>
        </div>
    </form>

    {{-- RESULTADOS DEL MODO ELEGIDO --}}
    @isset($result)
        <div class="card">
            <h3>📊 Resultados (modo elegido)</h3>
            <table>
                <tr>
                    <th>Esfuerzo (PM)</th>
                    <td>{{ $result['pm'] }} persona-mes</td>
                </tr>
                <tr>
                    <th>Duración (TDEV)</th>
                    <td>{{ $result['tdev'] }} meses</td>
                </tr>
                <tr>
                    <th>Personas promedio (P)</th>
                    <td>{{ $result['p'] }}</td>
                </tr>
                <tr>
                    <th>Costo total (C)</th>
                    <td>${{ number_format($result['cost'], 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <th>Factor de ajuste (EAF)</th>
                    <td>{{ $result['eaf'] }}</td>
                </tr>
                <tr>
                    <th>Modo / Coefs</th>
                    <td class="muted">
                        {{ ucfirst($result['inputs']['mode']) }}
                        — a={{ $result['coeffs']['a'] }}, b={{ $result['coeffs']['b'] }},
                        c={{ $result['coeffs']['c'] }}, d={{ $result['coeffs']['d'] }}
                    </td>
                </tr>
            </table>

            <h4 style="margin-top:14px">Multiplicadores usados (composición del EAF)</h4>
            <table>
                <thead>
                    <tr>
                        <th>Driver</th>
                        <th>Nivel</th>
                        <th>× Multiplicador</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($result['used_multipliers'] as $drv => $row)
                        <tr>
                            <td>{{ $drv }}</td>
                            <td>{{ str_replace('_', ' ', $row['level']) }}</td>
                            <td>×{{ $row['mult'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endisset

    {{-- ⚖️ TABLA COMPARATIVA ENTRE MODOS --}}
    @isset($comparison)
        <div class="card">
            <h3>⚖️ Comparación entre modos (mismos KLOC, salario y drivers)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Modo</th>
                        <th>PM</th>
                        <th>TDEV (meses)</th>
                        <th>Personas (P)</th>
                        <th>Costo (C)</th>
                        <th>EAF</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (['organico', 'semiacoplado', 'empotrado'] as $m)
                        @php $row = $comparison[$m] ?? null; @endphp
                        @if ($row)
                            <tr @if (isset($result['inputs']['mode']) && $result['inputs']['mode'] === $m) class="hl" @endif>
                                <td><strong>{{ ucfirst($m) }}</strong></td>
                                <td>{{ $row['pm'] }}</td>
                                <td>{{ $row['tdev'] }}</td>
                                <td>{{ $row['p'] }}</td>
                                <td>${{ number_format($row['cost'], 2, ',', '.') }}</td>
                                <td>{{ $row['eaf'] }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
            <p class="muted" style="margin-top:8px">La fila resaltada corresponde al modo elegido en el formulario.</p>
        </div>
    @endisset
</body>

</html>
