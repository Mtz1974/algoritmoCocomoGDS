<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CocomoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $drivers = [
            'RELY',
            'DATA',
            'CPLX',
            'TIME',
            'STOR',
            'VIRT',
            'TURN',
            'ACAP',
            'AEXP',
            'PCAP',
            'PEXP',
            'LTEX',
            'MODP',
            'TOOL',
            'SCED'
        ];

        $driverRules = [];
        foreach ($drivers as $d) {
            $driverRules["drivers.$d"] = 'required|string|in:muy_bajo,bajo,nominal,alto,muy_alto,extra_alto';
        }

        return array_merge([
            'kloc'   => 'required|numeric|min:0.0001',
            'mode'   => 'required|string|in:organico,semiacoplado,empotrado',
            'salary' => 'required|numeric|min:0.01',
        ], $driverRules);
    }
}
