<?php

namespace App\Http\Controllers;

use App\Models\PrecioHistorial;
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
            'productos' => Producto::activos()->filtrar($buscar, $categoria, $color, $orden)->paginate(25)->withQueryString(),
            'historialPrecios' => PrecioHistorial::with(['producto', 'usuario'])->latest('id')
                ->paginate(10, ['*'], 'pagina_precios')->withQueryString(),
            'colores' => Producto::activos()->whereRaw("TRIM(COALESCE(color, '')) <> ''")->distinct()->orderBy('color')->pluck('color'),
            'buscar' => $buscar, 'categoriaFiltro' => $categoria, 'colorFiltro' => $color, 'orden' => $orden,
            'hayFiltros' => $buscar !== '' || $categoria !== '' || $color !== '',
        ]);
    }

    public function ajustar(Request $request, Producto $producto)
    {
        $datos = $request->validate([
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'precio' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        DB::transaction(function () use ($producto, $datos, $request) {
            $anterior = $producto->precio;
            $producto->update($datos);
            if (round($anterior, 2) !== round((float) $datos['precio'], 2)) {
                PrecioHistorial::create([
                    'producto_id' => $producto->id, 'usuario_id' => $request->user()->id,
                    'precio_anterior' => $anterior, 'precio_nuevo' => $datos['precio'],
                ]);
            }
        });

        return back()->with('ok', 'Inventario de ' . $producto->nombre . ' actualizado.');
    }
}
