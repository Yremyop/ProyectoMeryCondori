<?php

namespace App\Providers;

use App\Models\LogSeguridad;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Mitigación DoS a nivel de aplicación (limitarPeticiones() del original).
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(config('seguridad.limite_login'))->by($r->ip())
            ->response(function () use ($r) {
                LogSeguridad::registrar('rate_limit', 'Límite de peticiones excedido en login');
                abort(429, 'Demasiadas solicitudes en poco tiempo. Espera un momento e inténtalo de nuevo.');
            }));

        RateLimiter::for('app', fn (Request $r) => Limit::perMinute(config('seguridad.limite_peticiones'))->by($r->user()?->id ?: $r->ip())
            ->response(function () {
                LogSeguridad::registrar('rate_limit', 'Límite de peticiones excedido');
                abort(429, 'Demasiadas solicitudes en poco tiempo. Espera un momento e inténtalo de nuevo.');
            }));

        // Contadores de alerta de la barra superior (resumenAlertas() del original).
        View::composer('layouts.app', function ($view) {
            $stockBajo = Producto::activos()->stockBajo()->count();
            $porVencer = Pedido::activos()->whereDate('fecha_entrega', '<=', today()->addDays(7))->count();
            $view->with('alertas', [
                'stock_bajo' => $stockBajo,
                'por_vencer' => $porVencer,
                'total' => $stockBajo + $porVencer,
            ]);
        });
    }
}
