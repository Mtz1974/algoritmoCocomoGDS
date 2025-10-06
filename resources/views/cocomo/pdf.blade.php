<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Estimación COCOMO I</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px
        }

        h1,
        h2 {
            margin: 0 0 8px
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left
        }

        .muted {
            color: #555
        }
    </style>
</head>

<body>
    <h1>Estimación de Costos — COCOMO I</h1>
    <p class="muted">
        KLOC: {{ $inputs['kloc'] }} ·
        Modo: {{ ucfirst($inputs['mode']) }} ·
        Salario: ${{ number_format($inputs['salary'], 2, ',', '.') }}
    </p>

    <h2>Resultados (modo elegido)</h2>
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
            <th>Personas (P)</th>
            <td>{{ $result['p'] }}</td>
        </tr>
        <tr>
            <th>Costo total (C)</th>
            <td>${{ number_format($result['cost'], 2, ',', '.') }}</td>
        </tr>
        <tr>
            <th>EAF</th>
            <td>{{ $result['eaf'] }}</td>
        </tr>
    </table>

    <h2>Comparación entre modos</h2>
    <table>
        <thead>
            <tr>
                <th>Modo</th>
                <th>PM</th>
                <th>TDEV</th>
                <th>P</th>
                <th>Costo</th>
                <th>EAF</th>
            </tr>
        </thead>
        <tbody>
            @foreach (['organico', 'semiacoplado', 'empotrado'] as $m)
                @php $row = $comparison[$m]; @endphp
                <tr>
                    <td>{{ ucfirst($m) }}</td>
                    <td>{{ $row['pm'] }}</td>
                    <td>{{ $row['tdev'] }}</td>
                    <td>{{ $row['p'] }}</td>
                    <td>${{ number_format($row['cost'], 2, ',', '.') }}</td>
                    <td>{{ $row['eaf'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Multiplicadores usados (EAF)</h2>
    <table>
        <thead>
            <tr>
                <th>Driver</th>
                <th>Nivel</th>
                <th>×</th>
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
</body>

</html>
