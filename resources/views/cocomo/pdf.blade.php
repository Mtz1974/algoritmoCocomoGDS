<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Estimación COCOMO I</title>
    <style>

        /* 1. Reset y Fuente */
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.5;
        }

        /* 2. Títulos */
        h1 {
            font-size: 20px;
            color: #4f46e5; /* Color primario (Indigo) */
            margin-bottom: 5px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 3px;
        }

        h2 {
            font-size: 16px;
            color: #333;
            margin: 15px 0 8px 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 2px;
        }

        .muted {
            color: #666;
            font-size: 10px;
            margin-bottom: 15px;
        }

        /* 3. Tablas Estilizadas */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            border: 1px solid #eee;
        }

        th,
        td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        /* Estilo de Encabezados */
        th {
            background-color: #f4f7fa; /* Gris claro para encabezados */
            color: #555;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }

        /* Resaltado de Filas (para el modo elegido) */
        .highlight-row {
            background-color: #e6f7ff !important; /* Azul claro para la fila seleccionada */
            font-weight: bold;
        }

        /* Resaltado de Datos Clave */
        .cost-cell {
            font-size: 16px;
            font-weight: bold;
            color: #4f46e5; /* Destacar costo */
        }

        .pm-cell {
            font-weight: bold;
        }
        /* Estilo para resaltar la fila de Costo */
.cost-row-highlight th,
.cost-row-highlight td {
    /* Fondo verde pálido suave */
    background-color: #f0fdf4 !important;
    /* Borde verde más fuerte */
    border-bottom: 2px solid #34d399;
}

/* ⚠️ Nuevo estilo para la celda de valor de Costo */
.cost-cell {
    /* Eliminamos el conflicto de colores y usamos un verde fuerte */
    font-size: 16px;
    font-weight: bold;
    color: #059669 !important; /* Verde oscuro (Green-700) para el texto */
}
    </style>
</head>

<body>
    <h1>Estimación de Costos — COCOMO I</h1>

    <p class="muted">
        **Inputs:** KLOC: **{{ number_format($inputs['kloc'], 2) }}** ·
        Modo Base: **{{ ucfirst($inputs['mode']) }}** ·
        Salario Mensual: **${{ number_format($inputs['salary'], 0, ',', '.') }}**
    </p>

    <h2>Resultados Detallados (Modo: {{ ucfirst($inputs['mode']) }})</h2>
    <table>
        <tr>
            <th style="width: 50%;">Esfuerzo Total (PM)</th>
            <td class="pm-cell">{{ number_format($result['pm'], 2, ',', '.') }} persona-mes</td>
        </tr>
        <tr>
            <th>Duración del Proyecto (TDEV)</th>
            <td>{{ number_format($result['tdev'], 2, ',', '.') }} meses</td>
        </tr>
        <tr>
            <th>Personas promedio requeridas (P)</th>
            <td>{{ number_format($result['p'], 2, ',', '.') }} desarrolladores</td>
        </tr>
        <tr>
            <th>Factor de Ajuste (EAF)</th>
            <td>{{ number_format($result['eaf'], 3, ',', '.') }}</td>
        </tr>
       <tr class="cost-row-highlight">
            <th>Costo Total Estimado (C)</th>
            <td class="cost-cell">${{ number_format($result['cost'], 2, ',', '.') }}</td>
        </tr>
    </table>

    <h2>Comparación entre Modos (Mismos KLOC y EAF)</h2>
    <table>
        <thead>
            <tr>
                <th>Modo</th>
                <th>PM</th>
                <th>TDEV (meses)</th>
                <th>P</th>
                <th>Costo</th>
                <th>EAF</th>
            </tr>
        </thead>
        <tbody>
            @foreach (['organico', 'semiacoplado', 'empotrado'] as $m)
                @php
                    $row = $comparison[$m];
                    $is_selected = $inputs['mode'] === $m;
                @endphp
                <tr @if ($is_selected) class="highlight-row" @endif>
                    <td @if ($is_selected) class="pm-cell" @endif>{{ ucfirst($m) }}</td>
                    <td>{{ number_format($row['pm'], 2, ',', '.') }}</td>
                    <td>{{ number_format($row['tdev'], 2, ',', '.') }}</td>
                    <td>{{ number_format($row['p'], 2, ',', '.') }}</td>
                    <td @if ($is_selected) class="cost-cell" @endif>${{ number_format($row['cost'], 2, ',', '.') }}</td>
                    <td>{{ number_format($row['eaf'], 3, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Multiplicadores usados para el EAF</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 60%;">Driver de Costo</th>
                <th>Nivel Elegido</th>
                <th>Multiplicador</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($result['used_multipliers'] as $drv => $row)
                <tr>
                    <td>{{ $drv }}</td>
                    <td>{{ str_replace('_', ' ', ucfirst($row['level'])) }}</td>
                    <td>×{{ $row['mult'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
