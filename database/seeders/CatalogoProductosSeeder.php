<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Catálogo real del almacén (Excel "ALMACEN 2024": hojas ALM CQS, ALM AV y ALM EMBL)
 * con la foto de cada especialidad/emblema (PDFs de IMPRIMIR PRODUCTOS).
 *
 *   Datos:    database/seeders/catalogo/productos.json
 *   Imágenes: database/seeders/catalogo/imagenes/*.webp  → se copian a storage/app/public/productos
 *
 * Solo:  php artisan db:seed --class=CatalogoProductosSeeder
 * Se puede correr varias veces: un producto que ya existe (mismo nombre y categoría) no se toca.
 */
class CatalogoProductosSeeder extends Seeder
{
    public function run(): void
    {
        $dir = database_path('seeders/catalogo');
        $productos = json_decode(File::get($dir . '/productos.json'), true, flags: JSON_THROW_ON_ERROR);
        $disco = Storage::disk('public');
        $creados = 0;

        foreach ($productos as $p) {
            // Las fotos se reponen siempre: en hostings sin disco persistente se borran en cada despliegue.
            if ($p['imagen'] && !$disco->exists('productos/' . $p['imagen'])) {
                $disco->put('productos/' . $p['imagen'], File::get($dir . '/imagenes/' . $p['imagen']));
            }

            if (Producto::where('nombre', $p['nombre'])->where('categoria', $p['categoria'])->exists()) {
                continue;
            }

            // El código del JSON sigue el formato del sistema (EC + 3 letras del color + correlativo).
            // Si ya está ocupado (p. ej. por un producto cargado a mano), se pide el siguiente libre.
            $codigo = Producto::where('codigo', $p['codigo'])->exists()
                ? Producto::siguienteCodigo($p['categoria'], $p['color'])
                : $p['codigo'];

            Producto::create([
                'codigo' => $codigo,
                'nombre' => $p['nombre'],
                'categoria' => $p['categoria'],
                'color' => $p['color'],
                'precio' => $p['precio'],
                'stock' => $p['stock'],
                'imagen' => $p['imagen'],
                'activo' => true,
            ]);
            $creados++;
        }

        $this->command?->info("Catálogo: {$creados} productos nuevos (" . count($productos) . ' en el archivo).');
    }
}
