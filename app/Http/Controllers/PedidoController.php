<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PedidoController extends Controller
{
    private const CARRITO = 'carrito_pedido';

    public function index(Request $request)
    {
        $carrito = $request->session()->get(self::CARRITO, []);

        return view('pedidos.index', [
            // Primero los pedidos abiertos, para que no queden detrás de los ya cerrados al paginar.
            'pedidos' => Pedido::with(['cliente', 'items'])
                ->orderByRaw('CASE WHEN estado IN (?, ?) THEN 1 ELSE 0 END', Pedido::FINALES)
                ->orderBy('fecha_entrega')->orderByDesc('id')->paginate(25),
            'clientes' => Cliente::orderBy('nombre')->get(),
            'productos' => Producto::activos()->orderBy('nombre')->get(),
            'carrito' => $carrito,
            'totalCarrito' => collect($carrito)->sum(fn ($l) => $l['cantidad'] * $l['precio']),
            'estados' => Pedido::ESTADOS,
        ]);
    }

    public function agregar(Request $request)
    {
        $datos = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $producto = Producto::activos()->find($datos['producto_id']);
        if (!$producto) {
            return back()->with('error', 'Producto no válido.');
        }

        // El pedido no reserva stock: se descuenta al convertirlo en venta.
        $carrito = $request->session()->get(self::CARRITO, []);
        $carrito[] = [
            'producto_id' => $producto->id,
            'codigo' => (string) $producto->codigo,
            'nombre' => $producto->nombre,
            'cantidad' => (int) $datos['cantidad'],
            'precio' => (float) $producto->precio,
        ];
        $request->session()->put(self::CARRITO, $carrito);

        return back()->with('ok', 'Producto agregado al pedido.');
    }

    public function quitar(Request $request, int $indice)
    {
        $carrito = $request->session()->get(self::CARRITO, []);
        unset($carrito[$indice]);
        $request->session()->put(self::CARRITO, array_values($carrito));

        return back();
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'fecha_pedido' => ['nullable', 'date'],
            'fecha_entrega' => ['required', 'date', Rule::when($request->filled('fecha_pedido'), 'after_or_equal:fecha_pedido')],
            'estado' => ['required', Rule::in(array_keys(Pedido::ESTADOS))],
            'adelanto' => ['nullable', 'numeric', 'min:0'],
        ], [
            'cliente_id.required' => 'Selecciona un cliente.',
            'fecha_entrega.required' => 'Indica la fecha de entrega.',
            'fecha_entrega.after_or_equal' => 'La fecha de entrega no puede ser anterior a la fecha del pedido.',
        ]);

        $carrito = $request->session()->get(self::CARRITO, []);
        if ($carrito === []) {
            return back()->withInput()->with('error', 'Agrega al menos un producto al pedido.');
        }

        // Redondeado a centavos: sin esto, 0.7 + 0.1 da 0.7999… y un adelanto de 0.80 se rechazaría.
        $total = round(collect($carrito)->sum(fn ($l) => $l['cantidad'] * $l['precio']), 2);
        $adelanto = round((float) ($datos['adelanto'] ?? 0), 2);
        if ($adelanto > $total) {
            return back()->withInput()->with('error', 'El adelanto no puede ser mayor al total del pedido.');
        }

        $resumen = collect($carrito)->map(fn ($l) => $l['cantidad'] . 'x ' . $l['nombre'])->implode(', ');

        DB::transaction(function () use ($datos, $carrito, $total, $adelanto, $resumen) {
            $pedido = new Pedido([
                'cliente_id' => $datos['cliente_id'],
                'diseno' => Str::limit($resumen, 200, '…'),
                'tamano' => '',
                'colores' => '',
                'fecha_entrega' => $datos['fecha_entrega'],
                'adelanto' => $adelanto,
                'total_pedido' => $total,
                'estado' => $datos['estado'],
            ]);
            if (!empty($datos['fecha_pedido'])) {
                $pedido->created_at = Carbon::parse($datos['fecha_pedido'])->startOfDay();
            }
            $pedido->save();

            foreach ($carrito as $l) {
                $pedido->items()->create([
                    'producto_id' => $l['producto_id'],
                    'producto_codigo' => $l['codigo'],
                    'producto_nombre' => $l['nombre'],
                    'cantidad' => $l['cantidad'],
                    'precio_unitario' => $l['precio'],
                    'subtotal' => round($l['cantidad'] * $l['precio'], 2),
                ]);
            }
        });

        $request->session()->forget(self::CARRITO);

        return redirect()->route('pedidos.index')->with('ok', 'Pedido registrado correctamente.');
    }

    /** Solo Administrador (middleware 'admin' en la ruta). */
    public function cambiarEstado(Request $request, Pedido $pedido)
    {
        $datos = $request->validate(['estado' => ['required', Rule::in(array_keys(Pedido::ESTADOS))]]);

        // Reabrir un pedido entregado permitiría venderlo (y descontar stock) otra vez.
        if ($pedido->cerrado) {
            return redirect()->route('pedidos.index')
                ->with('error', 'El pedido #' . $pedido->id . ' ya fue ' . strtolower($pedido->estado) . ' y no puede cambiar de estado.');
        }
        $pedido->update(['estado' => $datos['estado']]
            + ($datos['estado'] === 'Entregado' ? ['entregado_at' => today()] : []));

        return redirect()->route('pedidos.index')->with('ok', 'Estado del pedido actualizado.');
    }
}
