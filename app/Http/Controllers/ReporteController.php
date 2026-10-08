<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $desde = $this->fecha($request->query('desde'));
        $hasta = $this->fecha($request->query('hasta'));
        $clienteId = (int) $request->query('cliente', 0);
        $productoId = (int) $request->query('producto', 0);
        $categoria = trim((string) $request->query('categoria', ''));
        $color = trim((string) $request->query('color', ''));
        // Periodo vacío = no mostrar el resumen por periodo.
        $periodo = in_array($request->query('periodo'), ['diario', 'semanal', 'mensual'], true)
            ? $request->query('periodo') : '';

        $filtroProducto = function ($q) use ($productoId, $categoria, $color) {
            $q->when($productoId > 0, fn ($w) => $w->where('productos.id', $productoId))
              ->when($categoria !== '', fn ($w) => $w->where('productos.categoria', $categoria))
              ->when($color !== '', fn ($w) => $w->where('productos.color', $color));
        };
        $hayFiltroProducto = $productoId > 0 || $categoria !== '' || $color !== '';

        $ventas = Venta::with('cliente')->has('detalles')
            ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta))
            ->when($clienteId > 0, fn ($q) => $q->where('cliente_id', $clienteId))
            ->when($hayFiltroProducto, fn ($q) => $q->whereHas('detalles.producto', $filtroProducto))
            ->get();

        // Las líneas respetan el filtro de producto. Los totales por venta/cliente/periodo salen
        // de la lista de ventas (una fila por venta) para no duplicar importes: el sistema PHP
        // original sumaba v.total sobre un JOIN con el detalle y contaba de más las ventas con
        // varias líneas.
        $lineas = VentaDetalle::with('producto')->whereIn('venta_id', $ventas->pluck('id'))
            ->when($hayFiltroProducto, fn ($q) => $q->whereHas('producto', $filtroProducto))
            ->get();

        $porProducto = $lineas->groupBy('producto_id')->map(fn ($g) => [
            'nombre' => $g->first()->producto->nombre ?? '—',
            'cantidad' => $g->sum('cantidad'),
            'ingresos' => $g->sum('subtotal'),
            'ventas' => $g->pluck('venta_id')->unique()->count(),
        ])->sortBy([['cantidad', 'desc'], ['ventas', 'desc']])->take(10)->values();

        $agrupador = fn ($campo) => $lineas->groupBy(fn ($l) => ($l->producto->$campo ?? '') ?: 'Sin dato')
            ->map(fn ($g, $k) => ['nombre' => $k, 'cantidad' => $g->sum('cantidad'), 'ingresos' => $g->sum('subtotal')])
            ->sortByDesc('ingresos')->values();

        $porCliente = $ventas->groupBy('cliente_id')->map(fn ($g) => [
            'nombre' => $g->first()->cliente->nombre ?? '—',
            'ventas' => $g->count(),
            'total' => $g->sum('total'),
        ])->sortBy([['total', 'desc'], ['ventas', 'desc']])->take(10)->values();

        $porPeriodo = $periodo === '' ? collect() : $ventas->groupBy(fn ($v) => match ($periodo) {
            'diario' => $v->fecha->format('Y-m-d'),
            'semanal' => $v->fecha->format('o-\WW'),
            default => $v->fecha->format('Y-m'),
        })->map(fn ($g, $k) => ['periodo' => $k, 'ventas' => $g->count(), 'total' => $g->sum('total')])
            ->sortKeysDesc()->values();

        return view('reportes.index', [
            'ingresos' => (float) $ventas->sum('total'),
            'numVentas' => $ventas->count(),
            'topProducto' => $porProducto->first()['nombre'] ?? '—',
            'porProducto' => $porProducto,
            'maxCantidad' => (int) ($porProducto->max('cantidad') ?? 0),
            'porCategoria' => $agrupador('categoria'),
            'porColor' => $agrupador('color'),
            'porCliente' => $porCliente,
            'ventasPeriodo' => $porPeriodo,
            'pendientes' => Pedido::where('estado', 'Pendiente')->count(),
            'clientes' => Cliente::orderBy('nombre')->get(),
            'productos' => Producto::orderBy('nombre')->get(),
            'categorias' => Producto::CATEGORIAS,
            'colores' => Producto::whereRaw("TRIM(COALESCE(color, '')) <> ''")->distinct()->orderBy('color')->pluck('color'),
            'desde' => $desde, 'hasta' => $hasta, 'periodo' => $periodo,
            'clienteId' => $clienteId, 'productoId' => $productoId,
            'categoriaFiltro' => $categoria, 'colorFiltro' => $color,
            'esPdf' => $request->boolean('pdf'),
            'pdfLink' => $request->fullUrlWithQuery(['pdf' => 1]),
        ]);
    }

    private function fecha($valor): ?string
    {
        if (!is_string($valor) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return null;
        }
        try {
            return Carbon::createFromFormat('Y-m-d', $valor)->format('Y-m-d') === $valor ? $valor : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
