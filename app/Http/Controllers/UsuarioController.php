<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index()
    {
        return view('usuarios.index', ['usuarios' => Usuario::orderBy('nombre')->get()]);
    }

    public function store(Request $request)
    {
        $request->merge(['usuario' => strtolower(trim((string) $request->input('usuario')))]);
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'usuario' => ['required', 'alpha_dash:ascii', 'min:3', 'max:50', 'unique:usuarios,usuario'],
            'password' => ['required', 'string', 'min:8'],
            'rol' => ['required', Rule::in(['Administrador', 'Vendedor'])],
        ], [
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'usuario.unique' => 'Ya existe ese nombre de usuario.',
            'usuario.alpha_dash' => 'El usuario solo puede tener letras, números, guiones y guion bajo (sin espacios).',
        ]);

        Usuario::create($datos + ['estado' => 'Activo']); // la contraseña se cifra por el cast "hashed"

        return redirect()->route('usuarios.index')->with('ok', 'Usuario creado correctamente.');
    }

    public function cambiarEstado(Request $request, Usuario $usuario)
    {
        if ($usuario->id === $request->user()->id) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }
        $usuario->update(['estado' => $usuario->estado === 'Activo' ? 'Inactivo' : 'Activo']);

        return back()->with('ok', 'Estado del usuario actualizado.');
    }

    public function cambiarRol(Request $request, Usuario $usuario)
    {
        if ($usuario->id === $request->user()->id) {
            return back()->with('error', 'No puedes cambiar tu propio rol.');
        }
        $usuario->update(['rol' => $usuario->esAdmin() ? 'Vendedor' : 'Administrador']);

        return back()->with('ok', 'Rol del usuario actualizado.');
    }
}
