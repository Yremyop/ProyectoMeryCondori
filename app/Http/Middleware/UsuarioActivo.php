<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Cierra la sesión de un usuario que fue desactivado mientras estaba conectado. */
class UsuarioActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->activo()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta fue desactivada. Consulta con el administrador.']);
        }

        return $next($request);
    }
}
