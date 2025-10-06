<?php

namespace App\Services;

use InvalidArgumentException;

class CocomoCalculator
{
    // Coeficientes COCOMO I por modo
    private const MODES = [
        'organico'     => ['a' => 2.4, 'b' => 1.05, 'c' => 2.5, 'd' => 0.38],
        'semiacoplado' => ['a' => 3.0, 'b' => 1.12, 'c' => 2.5, 'd' => 0.35],
        'empotrado'    => ['a' => 3.6, 'b' => 1.20, 'c' => 2.5, 'd' => 0.32],
    ];

    // 15 drivers (multiplicadores) del instructivo
    private const DRIVERS = [
        'RELY' => ['muy_bajo' => 0.75, 'bajo' => 0.88, 'nominal' => 1.00, 'alto' => 1.15, 'muy_alto' => 1.40, 'extra_alto' => null],
        'DATA' => ['muy_bajo' => null, 'bajo' => 0.94, 'nominal' => 1.00, 'alto' => 1.08, 'muy_alto' => 1.16, 'extra_alto' => null],
        'CPLX' => ['muy_bajo' => 0.70, 'bajo' => 0.85, 'nominal' => 1.00, 'alto' => 1.15, 'muy_alto' => 1.30, 'extra_alto' => 1.65],

        'TIME' => ['muy_bajo' => null, 'bajo' => null, 'nominal' => 1.00, 'alto' => 1.11, 'muy_alto' => 1.30, 'extra_alto' => 1.66],
        'STOR' => ['muy_bajo' => null, 'bajo' => null, 'nominal' => 1.00, 'alto' => 1.06, 'muy_alto' => 1.21, 'extra_alto' => 1.56],
        'VIRT' => ['muy_bajo' => 0.87, 'bajo' => 0.94, 'nominal' => 1.00, 'alto' => 1.10, 'muy_alto' => 1.15, 'extra_alto' => null],
        'TURN' => ['muy_bajo' => null, 'bajo' => 0.87, 'nominal' => 1.00, 'alto' => 1.07, 'muy_alto' => 1.15, 'extra_alto' => null],

        'ACAP' => ['muy_bajo' => 1.46, 'bajo' => 1.19, 'nominal' => 1.00, 'alto' => 0.86, 'muy_alto' => 0.71, 'extra_alto' => null],
        'AEXP' => ['muy_bajo' => 1.29, 'bajo' => 1.13, 'nominal' => 1.00, 'alto' => 0.91, 'muy_alto' => 0.82, 'extra_alto' => null],
        'PCAP' => ['muy_bajo' => 1.42, 'bajo' => 1.17, 'nominal' => 1.00, 'alto' => 0.86, 'muy_alto' => 0.70, 'extra_alto' => null],
        'PEXP' => ['muy_bajo' => 1.19, 'bajo' => 1.10, 'nominal' => 1.00, 'alto' => 0.90, 'muy_alto' => 0.85, 'extra_alto' => null],
        'LTEX' => ['muy_bajo' => 1.14, 'bajo' => 1.07, 'nominal' => 1.00, 'alto' => 0.95, 'muy_alto' => 0.84, 'extra_alto' => null],

        'MODP' => ['muy_bajo' => 1.24, 'bajo' => 1.10, 'nominal' => 1.00, 'alto' => 0.91, 'muy_alto' => 0.82, 'extra_alto' => null],
        'TOOL' => ['muy_bajo' => 1.24, 'bajo' => 1.10, 'nominal' => 1.00, 'alto' => 0.91, 'muy_alto' => 0.83, 'extra_alto' => null],
        'SCED' => ['muy_bajo' => 1.23, 'bajo' => 1.08, 'nominal' => 1.00, 'alto' => 1.04, 'muy_alto' => 1.10, 'extra_alto' => null],
    ];

    public static function driversTable(): array
    {
        return self::DRIVERS;
    }
    public static function modes(): array
    {
        return array_keys(self::MODES);
    }

    public static function compute(array $input): array
    {
        $kloc   = (float)$input['kloc'];
        $mode   = $input['mode'];
        $salary = (float)$input['salary'];
        $sel    = $input['drivers'] ?? [];

        if ($kloc <= 0)   throw new InvalidArgumentException('KLOC debe ser > 0');
        if ($salary <= 0) throw new InvalidArgumentException('Salario debe ser > 0');
        if (!isset(self::MODES[$mode])) throw new InvalidArgumentException('Modo inválido');

        // EAF
        $eaf = 1.0;
        $used = [];
        foreach (self::DRIVERS as $driver => $levels) {
            $level = $sel[$driver] ?? 'nominal';
            $mult  = $levels[$level] ?? null;
            if ($mult === null) throw new InvalidArgumentException("Nivel '$level' no aplica para $driver");
            $used[$driver] = ['level' => $level, 'mult' => $mult];
            $eaf *= $mult;
        }

        $coef = self::MODES[$mode];
        $pm   = $coef['a'] * pow($kloc, $coef['b']) * $eaf;
        $tdev = $coef['c'] * pow($pm,   $coef['d']);
        $p    = $pm / max($tdev, 0.000001);
        $cost = $p * $salary;

        return [
            'inputs' => compact('kloc', 'mode', 'salary'),
            'coeffs' => $coef,
            'eaf'    => round($eaf, 4),
            'pm'     => round($pm, 2),
            'tdev'   => round($tdev, 2),
            'p'      => round($p, 2),
            'cost'   => round($cost, 2),
            'used_multipliers' => $used,
        ];
    }
}
