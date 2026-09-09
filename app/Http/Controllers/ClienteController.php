<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        return $this->vista($request, null);
    }

    public function edit(Request $request, Cliente $cliente)
    {
        return $this->vista($request, $cliente);
    }

    private function vista(Request $request, ?Cliente $editando)
    {
        $buscar = trim((string) $request->query('buscar', ''));

        return view('clientes.index', [
            'clientes' => Cliente::buscar($buscar)->orderBy('nombre')->get(),
            'buscar' => $buscar,
            'editando' => $editando,
        ]);
    }

    public function store(Request $request)
    {
        Cliente::create($this->validar($request));

        return redirect()->route('clientes.index')->with('ok', 'Cliente registrado correctamente.');
    }

    public function update(Request $request, Cliente $cliente)
    {
        $cliente->update($this->validar($request, $cliente));

        return redirect()->route('clientes.index')->with('ok', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente)
    {
        try {
            $cliente->delete();
        } catch (QueryException $e) {
            return redirect()->route('clientes.index')
                ->with('error', 'No se puede eliminar a ' . $cliente->nombre . ': tiene ventas o pedidos registrados.');
        }

        return redirect()->route('clientes.index')->with('ok', 'Cliente eliminado.');
    }

    private function validar(Request $request, ?Cliente $actual = null): array
    {
        $request->merge(['documento' => preg_replace('/\s+/', '', trim((string) $request->input('documento')))]);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'documento' => ['required', 'string', 'max:50', function ($attr, $valor, $fail) use ($actual) {
                $existe = Cliente::whereRaw("REPLACE(UPPER(documento), ' ', '') = ?", [strtoupper($valor)])
                    ->when($actual, fn ($q) => $q->where('id', '<>', $actual->id))->exists();
                if ($existe) {
                    $fail('El carnet/NIT ya está registrado para otro cliente.');
                }
            }],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
        ], ['documento.required' => 'Ingresa el carnet/NIT del cliente.']);

        $datos['nombre'] = trim($datos['nombre']);

        return $datos;
    }
}
