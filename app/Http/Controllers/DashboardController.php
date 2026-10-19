<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $hoy = today();
        $ventasMes = (float) Venta::whereYear('fecha', $hoy->year)->whereMonth('fecha', $hoy->month)->sum('total');
        $ventasMesPrevio = (float) Venta::where('fecha', '>=', $hoy->copy()->subMonthNoOverflow()->startOfMonth())
            ->where('fecha', '<', $hoy->copy()->startOfMonth())->sum('total');
        $variacionMes = $ventasMesPrevio > 0 ? (int) round((($ventasMes - $ventasMesPrevio) / $ventasMesPrevio) * 100) : null;

        $ventasHoy = Venta::whereDate('fecha', $hoy)->selectRaw('COALESCE(SUM(total),0) as total, COUNT(*) as n')->first();
        $ticketPromedio = (float) Venta::where('fecha', '>=', $hoy->copy()->subDays(30))->avg('total');

        $estados = Arr::except(Pedido::ESTADOS, 'Cancelado');
        $conteo = Pedido::select('estado', DB::raw('COUNT(*) as n'))->groupBy('estado')->pluck('n', 'estado');
        $conteoEstados = [];
        $tarjetas = [];
        foreach (array_keys($estados) as $e) {
            $conteoEstados[$e] = (int) ($conteo[$e] ?? 0);
            $tarjetas[$e] = Pedido::with('cliente')->where('estado', $e)
                ->orderBy('fecha_entrega')->orderByDesc('id')->limit(2)->get();
        }

        $productosBajos = Producto::activos()->stockBajo()->orderByRaw('(stock_minimo - stock) DESC')->limit(4)->get();
        $porVencer = Pedido::with('cliente')->activos()
            ->whereDate('fecha_entrega', '<=', $hoy->copy()->addDays(7))->orderBy('fecha_entrega')->get();
        $conSaldo = Pedido::with('cliente')->whereNotIn('estado', Pedido::FINALES)
            ->whereColumn('total_pedido', '>', 'adelanto')->get()
            ->sortByDesc(fn ($p) => $p->saldo)->values();

        return view('dashboard', [
            'mesActual' => ucfirst($hoy->translatedFormat('F Y')),
            'ventasMes' => $ventasMes,
            'variacionMes' => $variacionMes,
            'ventasHoy' => $ventasHoy,
            'ticketPromedio' => $ticketPromedio,
            'pedidosActivos' => Pedido::activos()->count(),
            'clientesTotal' => Cliente::count(),
            'clientesNuevos' => Cliente::whereYear('created_at', $hoy->year)->whereMonth('created_at', $hoy->month)->count(),
            'estados' => $estados,
            'conteoEstados' => $conteoEstados,
            'tarjetas' => $tarjetas,
            'ultimasVentas' => Venta::with(['cliente', 'usuario'])->latest('id')->limit(6)->get(),
            'ventasTotales' => Venta::count(),
            'productosBajos' => $productosBajos,
            'stockBajoTotal' => Producto::activos()->stockBajo()->count(),
            'porVencer' => $porVencer,
            'conSaldo' => $conSaldo,
            'saldoTotal' => $conSaldo->sum(fn ($p) => $p->saldo),
        ]);
    }
}
