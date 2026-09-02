<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Cabeceras de seguridad (equivale a aplicarCabecerasSeguridad() del PHP original). */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'same-origin');
        // CSP restrictiva: solo scripts propios; estilos propios + Google Fonts.
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; style-src 'self' https://fonts.googleapis.com 'unsafe-inline'; "
            . "font-src https://fonts.gstatic.com; img-src 'self' data:; script-src 'self';"
        );

        return $response;
    }
}
