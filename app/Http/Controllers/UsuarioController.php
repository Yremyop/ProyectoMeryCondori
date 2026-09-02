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
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:usuarios,email'],
            'password' => ['required', 'string', 'min:8'],
            'rol' => ['required', Rule::in(['Administrador', 'Vendedor'])],
        ], [
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
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
}
