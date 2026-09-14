<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        return $this->vista($request, null);
    }

    public function edit(Request $request, Producto $producto)
    {
        return $this->vista($request, $producto);
    }

    private function vista(Request $request, ?Producto $editando)
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $categoria = (string) $request->query('categoria', '');
        $color = trim((string) $request->query('color', ''));
        $orden = $request->query('orden') === 'desc' ? 'desc' : 'asc';

        return view('productos.index', [
            'productos' => Producto::activos()->filtrar($buscar, $categoria, $color, $orden)->get(),
            'colores' => Producto::activos()->whereRaw("TRIM(COALESCE(color, '')) <> ''")->distinct()->orderBy('color')->pluck('color'),
            'buscar' => $buscar, 'categoriaFiltro' => $categoria, 'colorFiltro' => $color, 'orden' => $orden,
            'editando' => $editando,
            'hayFiltros' => $buscar !== '' || $categoria !== '' || $color !== '',
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $datos['imagen'] = $this->guardarImagen($request);
        Producto::create($datos);

        return redirect()->route('productos.index')->with('ok', 'Producto agregado correctamente.');
    }

    public function update(Request $request, Producto $producto)
    {
        $datos = $this->validar($request, $producto);
        if ($imagen = $this->guardarImagen($request)) {
            $datos['imagen'] = $imagen;
        }
        $producto->update($datos);

        return redirect()->route('productos.index')->with('ok', 'Producto actualizado.');
    }

    /** Eliminación lógica: el producto queda inactivo para conservar el historial. */
    public function destroy(Producto $producto)
    {
        $producto->update(['activo' => false]);

        return redirect()->route('productos.index')->with('ok', 'Producto desactivado correctamente.');
    }

    private function validar(Request $request, ?Producto $actual = null): array
    {
        return $request->validate([
            'codigo' => ['required', 'string', 'max:50', Rule::unique('productos', 'codigo')->ignore($actual?->id)],
            'nombre' => ['required', 'string', 'max:150'],
            'categoria' => ['required', Rule::in(Producto::CATEGORIAS)],
            'color' => ['required', Rule::in(Producto::COLORES)],
            'precio' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], ['codigo.unique' => 'El código del producto ya existe.']);
    }

    /** Guarda la imagen con nombre único en storage/app/public/productos. */
    private function guardarImagen(Request $request): ?string
    {
        if (!$request->hasFile('imagen')) {
            return null;
        }
        $archivo = $request->file('imagen');
        $nombre = $archivo->hashName();
        $archivo->storeAs('productos', $nombre, 'public');

        return $nombre;
    }
}
