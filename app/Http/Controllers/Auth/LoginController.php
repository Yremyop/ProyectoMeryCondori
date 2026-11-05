<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LogSeguridad;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login', ['maxIntentos' => config('seguridad.max_intentos_login'), 'bloqueoMin' => config('seguridad.bloqueo_segundos') / 60]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate(
            ['usuario' => ['required', 'string'], 'password' => ['required', 'string']],
            ['usuario.required' => 'Ingresa tu usuario y contraseña.', 'password.required' => 'Ingresa tu usuario y contraseña.']
        );

        $usuario = strtolower(trim($datos['usuario']));
        // Anti fuerza bruta: se bloquea el par usuario|IP tras varios fallos seguidos.
        $clave = 'login:' . $usuario . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($clave, config('seguridad.max_intentos_login'))) {
            $hasta = now()->addSeconds(RateLimiter::availableIn($clave))->format('H:i');
            return back()->withInput(['usuario' => $usuario])->withErrors([
                'usuario' => "Demasiados intentos fallidos. Cuenta bloqueada temporalmente hasta las $hasta.",
            ]);
        }

        try {
            $ok = Auth::attempt(['usuario' => $usuario, 'password' => $datos['password'], 'estado' => 'Activo']);
        } catch (QueryException $e) {
            throw $e; // una falla de la base no es una contraseña incorrecta ni cuenta como intento
        } catch (\RuntimeException $e) {
            // Contraseña guardada en texto plano (legado): ejecutar `php artisan usuarios:hashear-legacy`.
            $ok = false;
        }

        if ($ok) {
            RateLimiter::clear($clave);
            $request->session()->regenerate(); // mitiga fijación de sesión
            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($clave, config('seguridad.bloqueo_segundos'));
        LogSeguridad::registrar('login_fallido', "Intento fallido para $usuario desde " . $request->ip());
        if (RateLimiter::tooManyAttempts($clave, config('seguridad.max_intentos_login'))) {
            LogSeguridad::registrar('fuerza_bruta_bloqueo', "Bloqueado por intentos fallidos: $usuario");
        }

        // Mensaje genérico: no revela si el usuario existe.
        return back()->withInput(['usuario' => $usuario])->withErrors(['usuario' => 'Usuario o contraseña incorrectos.']);
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
