<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

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
        $producto->update($request->validate([
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'precio' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]));

        return back()->with('ok', 'Inventario de ' . $producto->nombre . ' actualizado.');
    }
}
