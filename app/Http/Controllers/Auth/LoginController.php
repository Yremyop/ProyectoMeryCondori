<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LogSeguridad;
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
            ['email' => ['required', 'string'], 'password' => ['required', 'string']],
            ['email.required' => 'Ingresa tu correo y contraseña.', 'password.required' => 'Ingresa tu correo y contraseña.']
        );

        $email = strtolower(trim($datos['email']));
        // Anti fuerza bruta: se bloquea el par correo|IP tras varios fallos seguidos.
        $clave = 'login:' . $email . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($clave, config('seguridad.max_intentos_login'))) {
            $hasta = now()->addSeconds(RateLimiter::availableIn($clave))->format('H:i');
            return back()->withInput(['email' => $email])->withErrors([
                'email' => "Demasiados intentos fallidos. Cuenta bloqueada temporalmente hasta las $hasta.",
            ]);
        }

        try {
            $ok = Auth::attempt(['email' => $email, 'password' => $datos['password'], 'estado' => 'Activo']);
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
        LogSeguridad::registrar('login_fallido', "Intento fallido para $email desde " . $request->ip());
        if (RateLimiter::tooManyAttempts($clave, config('seguridad.max_intentos_login'))) {
            LogSeguridad::registrar('fuerza_bruta_bloqueo', "Bloqueado por intentos fallidos: $email");
        }

        // Mensaje genérico: no revela si el correo existe.
        return back()->withInput(['email' => $email])->withErrors(['email' => 'Correo o contraseña incorrectos.']);
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
