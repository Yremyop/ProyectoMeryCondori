<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\Request;

class BusquedaController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $res = ['clientes' => collect(), 'productos' => collect(), 'pedidos' => collect(), 'ventas' => collect()];

        if ($q !== '') {
            $like = '%' . $q . '%';
            $id = ctype_digit($q) ? (int) $q : 0;

            $res['clientes'] = Cliente::buscar($q)->orderBy('nombre')->limit(8)->get();
            $res['productos'] = Producto::activos()
                ->where(fn ($w) => $w->where('nombre', 'like', $like)->orWhere('codigo', 'like', $like))
                ->orderBy('nombre')->limit(8)->get();
            $res['pedidos'] = Pedido::with('cliente')
                ->where(fn ($w) => $w->where('diseno', 'like', $like)->orWhere('id', $id)
                    ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', $like)))
                ->orderByDesc('id')->limit(8)->get();
            $res['ventas'] = Venta::with('cliente')
                ->where(fn ($w) => $w->where('id', $id)->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', $like)))
                ->orderByDesc('id')->limit(8)->get();
        }

        return view('busqueda.index', ['q' => $q, 'res' => $res, 'total' => collect($res)->sum(fn ($c) => $c->count())]);
    }
}
