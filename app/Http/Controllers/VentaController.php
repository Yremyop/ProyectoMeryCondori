<?php

namespace App\Http\Controllers;

use App\Exceptions\VentaRechazada;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Support\Pdfs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    private const CARRITO = 'carrito_venta';

    public function index(Request $request)
    {
        $modo = $request->query('modo_venta') === 'pedido' ? 'pedido' : 'directa';
        $carrito = $request->session()->get(self::CARRITO, []);

        $pedidoEdicion = null;
        if ($modo === 'pedido' && $request->filled('editar_pedido_id')) {
            $pedidoEdicion = Pedido::with(['cliente', 'items'])->find((int) $request->query('editar_pedido_id'));
        }

        return view('ventas.index', [
            'modo' => $modo,
            'carrito' => $carrito,
            'totalCarrito' => collect($carrito)->sum(fn ($l) => $l['cantidad'] * $l['precio']),
            'productos' => Producto::activos()->orderBy('nombre')->get(),
            'clientes' => Cliente::orderBy('nombre')->get(),
            'pedidos' => $modo === 'pedido'
                ? Pedido::with(['cliente', 'items'])->whereNotIn('estado', Pedido::FINALES)->latest('id')->get() : collect(),
            'pedidoEdicion' => $pedidoEdicion,
            'historial' => Venta::with(['cliente', 'usuario', 'detalles.producto'])->latest('id')->paginate(25)->withQueryString(),
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

        $carrito = $request->session()->get(self::CARRITO, []);
        $enCarrito = collect($carrito)->where('producto_id', $producto->id)->sum('cantidad');
        if ($producto->stock < $enCarrito + $datos['cantidad']) {
            return back()->with('error', "Stock insuficiente para {$producto->nombre}. Disponible: {$producto->stock}.");
        }

        $carrito[] = [
            'producto_id' => $producto->id,
            'nombre' => $producto->nombre,
            'cantidad' => (int) $datos['cantidad'],
            'precio' => (float) $producto->precio,
        ];
        $request->session()->put(self::CARRITO, $carrito);

        return back()->with('ok', 'Producto agregado a la venta.');
    }

    public function quitar(Request $request, int $indice)
    {
        $carrito = $request->session()->get(self::CARRITO, []);
        unset($carrito[$indice]);
        $request->session()->put(self::CARRITO, array_values($carrito));

        return back();
    }

    /** Confirma la venta directa con el carrito de la sesión. */
    public function store(Request $request)
    {
        $datos = $request->validate(['cliente_id' => ['required', 'integer', 'exists:clientes,id']],
            ['cliente_id.required' => 'Selecciona un cliente.', 'cliente_id.exists' => 'Selecciona un cliente válido.']);

        $carrito = $request->session()->get(self::CARRITO, []);
        if ($carrito === []) {
            return back()->with('error', 'Agrega al menos un producto a la venta.');
        }

        try {
            $venta = DB::transaction(function () use ($carrito, $datos, $request) {
                $productos = $this->verificarStock(collect($carrito)->groupBy('producto_id')
                    ->map(fn ($l) => $l->sum('cantidad'))->all());

                // Se cobra el precio vigente al confirmar, no el que tenía al agregarse al carrito.
                $venta = Venta::create([
                    'cliente_id' => $datos['cliente_id'],
                    'usuario_id' => $request->user()->id,
                    'fecha' => today(),
                    'total' => round(collect($carrito)->sum(fn ($l) => $l['cantidad'] * $productos[$l['producto_id']]->precio), 2),
                ]);
                foreach ($carrito as $l) {
                    $this->registrarLinea($venta, $l['producto_id'], $l['cantidad'], $productos[$l['producto_id']]->precio);
                }
                return $venta;
            });
        } catch (VentaRechazada $e) {
            return back()->with('error', $e->getMessage());
        }

        $request->session()->forget(self::CARRITO);

        return redirect()->route('facturas.show', $venta)->with('ok', 'Venta registrada correctamente.');
    }

    /** Convierte un pedido en venta: ajusta cantidades, descuenta stock y entrega el pedido. */
    public function venderPedido(Request $request, Pedido $pedido)
    {
        $datos = $request->validate([
            'cantidades' => ['array'], 'cantidades.*' => ['nullable', 'integer', 'min:0'],
            'eliminar' => ['array'], 'eliminar.*' => ['integer'],
        ]);

        try {
            $venta = DB::transaction(function () use ($pedido, $datos, $request) {
                $pedido = Pedido::lockForUpdate()->findOrFail($pedido->id);
                if ($pedido->cerrado) {
                    throw new VentaRechazada('Este pedido ya fue ' . strtolower($pedido->estado) . ' y no puede venderse de nuevo.');
                }

                $eliminar = array_map('intval', $datos['eliminar'] ?? []);
                foreach ($pedido->items as $item) {
                    $nueva = (int) ($datos['cantidades'][$item->id] ?? $item->cantidad);
                    if (in_array($item->id, $eliminar, true) || $nueva <= 0) {
                        $item->delete();
                    } elseif ($nueva !== $item->cantidad) {
                        $item->update(['cantidad' => $nueva, 'subtotal' => round($nueva * $item->precio_unitario, 2)]);
                    }
                }

                // Solo se venden las líneas que siguen ligadas a un producto del catálogo.
                $lineas = $pedido->items()->whereNotNull('producto_id')->get();
                if ($lineas->isEmpty()) {
                    throw new VentaRechazada('El pedido no tiene productos para vender.');
                }

                $this->verificarStock($lineas->groupBy('producto_id')->map(fn ($l) => $l->sum('cantidad'))->all());

                $total = round($lineas->sum(fn ($l) => $l->cantidad * $l->precio_unitario), 2);
                $venta = Venta::create([
                    'cliente_id' => $pedido->cliente_id,
                    'usuario_id' => $request->user()->id,
                    'fecha' => today(),
                    'total' => $total,
                ]);
                foreach ($lineas as $l) {
                    $this->registrarLinea($venta, $l->producto_id, $l->cantidad, (float) $l->precio_unitario);
                }

                $pedido->update(['estado' => 'Entregado', 'total_pedido' => $total]);

                return $venta;
            });
        } catch (VentaRechazada $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('facturas.show', $venta)->with('ok', 'Pedido convertido en venta y entregado.');
    }

    public function pdf()
    {
        $ventas = Venta::with(['cliente', 'detalles.producto'])->latest('id')->get();

        return response(Pdfs::historial($ventas), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="historial_ventas.pdf"',
        ]);
    }

    /**
     * @param array<int,int> $requerido producto_id => unidades. Debe llamarse dentro de una transacción.
     * @return array<int,Producto> productos bloqueados, por id
     */
    private function verificarStock(array $requerido): array
    {
        $productos = [];
        foreach ($requerido as $productoId => $cantidad) {
            $p = Producto::lockForUpdate()->find($productoId);
            if (!$p || !$p->activo) {
                throw new VentaRechazada('Uno de los productos ya no está disponible.');
            }
            if ($p->stock < $cantidad) {
                throw new VentaRechazada("Stock insuficiente para {$p->nombre}. Disponible: {$p->stock}.");
            }
            $productos[$productoId] = $p;
        }

        return $productos;
    }

    private function registrarLinea(Venta $venta, int $productoId, int $cantidad, float $precio): void
    {
        VentaDetalle::create([
            'venta_id' => $venta->id,
            'producto_id' => $productoId,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'subtotal' => round($cantidad * $precio, 2),
        ]);
        Producto::whereKey($productoId)->decrement('stock', $cantidad);
    }
}
