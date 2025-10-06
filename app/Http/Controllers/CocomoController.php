<?php

namespace App\Http\Controllers;

use App\Http\Requests\CocomoRequest;
use App\Services\CocomoCalculator;
use Barryvdh\DomPDF\Facade\Pdf;

class CocomoController extends Controller
{
    /**
     * Muestra el formulario con valores por defecto.
     */
    public function index()
    {
        $driversTable = CocomoCalculator::driversTable();
        $modes        = CocomoCalculator::modes();

        // Valores por defecto del formulario
        $defaults = [
            'kloc'   => 40,
            'mode'   => 'organico',
            'salary' => 500000,
            'drivers' => array_fill_keys(array_keys($driversTable), 'nominal'),
        ];

        return view('cocomo.index', compact('driversTable', 'modes', 'defaults'));
    }

    /**
     * Procesa el formulario, calcula el modo elegido y arma
     * la comparación entre modos (orgánico / semiacoplado / empotrado)
     * usando los mismos KLOC, salario y drivers.
     */
    public function calculate(CocomoRequest $request)
    {
        $v = $request->validated();

        // Base común para cualquier modo
        $base = [
            'kloc'    => (float)$v['kloc'],
            'salary'  => (float)$v['salary'],
            'drivers' => $v['drivers'],
        ];

        // Resultado del modo elegido (tarjeta principal)
        $dataChosen = $base + ['mode' => $v['mode']];
        $result     = CocomoCalculator::compute($dataChosen);

        // Comparación de los tres modos con los mismos inputs
        $comparison = [];
        foreach (CocomoCalculator::modes() as $m) {
            $comparison[$m] = CocomoCalculator::compute($base + ['mode' => $m]);
        }

        // Tablas y valores para mantener el formulario con lo enviado
        $driversTable = CocomoCalculator::driversTable();
        $modes        = CocomoCalculator::modes();
        $defaults     = [
            'kloc'    => $base['kloc'],
            'mode'    => $v['mode'],
            'salary'  => $base['salary'],
            'drivers' => $base['drivers'],
        ];

        return view('cocomo.index', compact(
            'driversTable',
            'modes',
            'defaults',
            'result',
            'comparison'
        ));
    }

    /**
     * Exporta a PDF los resultados del modo elegido, la comparación entre modos
     * y los multiplicadores (EAF), usando los mismos datos del formulario.
     */
    public function exportPdf(CocomoRequest $request)
    {
        $v = $request->validated();

        // Base común
        $base = [
            'kloc'    => (float)$v['kloc'],
            'salary'  => (float)$v['salary'],
            'drivers' => $v['drivers'],
        ];

        // Resultado del modo elegido
        $dataChosen = $base + ['mode' => $v['mode']];
        $result     = CocomoCalculator::compute($dataChosen);

        // Comparación entre modos
        $comparison = [];
        foreach (CocomoCalculator::modes() as $m) {
            $comparison[$m] = CocomoCalculator::compute($base + ['mode' => $m]);
        }

        // Render del PDF
        $pdf = Pdf::loadView('cocomo.pdf', [
            'inputs'     => $v,
            'result'     => $result,
            'comparison' => $comparison,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Estimacion_COCOMO.pdf');
    }
}
