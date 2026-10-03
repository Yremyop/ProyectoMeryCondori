<?php

namespace App\Http\Controllers;

use App\Support\Cotizador;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CotizadorController extends Controller
{
    public function index()
    {
        return $this->vista(null, [
            'cliente' => '', 'diseno' => '', 'ancho' => 8, 'alto' => 6, 'cantidad' => 50,
            'densidad' => 'Media', 'respaldo' => 'Para coser', 'diseno_nuevo' => 'No', 'urgencia' => 'Normal',
        ]);
    }

    public function calcular(Request $request)
    {
        $c = config('cotizador');

        $datos = $request->validate([
            'cliente' => ['nullable', 'string', 'max:150'],
            'diseno' => ['nullable', 'string', 'max:150'],
            'ancho' => ['required', 'numeric', 'gt:0'],
            'alto' => ['required', 'numeric', 'gt:0'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'densidad' => ['required', Rule::in(array_keys($c['densidad']))],
            'respaldo' => ['required', Rule::in(array_keys($c['respaldo']))],
            'diseno_nuevo' => ['required', Rule::in(['Si', 'No'])],
            'urgencia' => ['required', Rule::in(['Normal', 'Express'])],
        ], [
            'ancho.gt' => 'El ancho debe ser mayor a 0.',
            'alto.gt' => 'El alto debe ser mayor a 0.',
            'cantidad.min' => 'La cantidad mínima es 1.',
        ]);

        return $this->vista(Cotizador::calcular($c, $datos), $datos);
    }

    private function vista(?array $r, array $f)
    {
        return view('cotizador.index', ['c' => config('cotizador'), 'r' => $r, 'f' => $f]);
    }
}
