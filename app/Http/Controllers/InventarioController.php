<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $categoria = (string) $request->query('categoria', '');
        $color = trim((string) $request->query('color', ''));
        $orden = $request->query('orden') === 'desc' ? 'desc' : 'asc';

        return view('inventario.index', [
            'productos' => Producto::activos()->filtrar($buscar, $categoria, $color, $orden)->get(),
            'colores' => Producto::activos()->whereRaw("TRIM(COALESCE(color, '')) <> ''")->distinct()->orderBy('color')->pluck('color'),
            'buscar' => $buscar, 'categoriaFiltro' => $categoria, 'colorFiltro' => $color, 'orden' => $orden,
            'hayFiltros' => $buscar !== '' || $categoria !== '' || $color !== '',
        ]);
    }

    public function ajustar(Request $request, Producto $producto)
    {
        $datos = $request->validate(['delta' => ['required', 'integer', 'between:-1000,1000']]);

        DB::transaction(function () use ($producto, $datos) {
            $p = Producto::lockForUpdate()->findOrFail($producto->id);
            $p->update(['stock' => max(0, $p->stock + (int) $datos['delta'])]); // nunca negativo
        });

        return back()->with('ok', 'Stock actualizado.');
    }
}
