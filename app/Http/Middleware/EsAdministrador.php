<?php

namespace App\Http\Middleware;

use App\Models\LogSeguridad;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Restringe un módulo al rol Administrador (requireAdmin() del original). */
class EsAdministrador
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->esAdmin()) {
            LogSeguridad::registrar('acceso_no_autorizado', 'Acceso denegado a ' . $request->path());
            abort(403, 'Este módulo solo está disponible para el rol Administrador.');
        }

        return $next($request);
    }
}
