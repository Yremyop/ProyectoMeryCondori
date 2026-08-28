<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    public const UPDATED_AT = null;

    public const CATEGORIAS = [
        'Esp. Aventureros', 'Esp. Conquistadores', 'Emblema Aventureros',
        'Emblema Conquistadores', 'Parches Personalizados',
    ];

    public const COLORES = [
        'Rojo', 'Azul', 'Verde', 'Amarillo', 'Negro', 'Blanco',
        'violeta', 'ADRA', 'cafe', 'Guindo', 'Otro',
    ];

    protected $fillable = [
        'codigo', 'nombre', 'categoria', 'color', 'precio', 'stock', 'stock_minimo', 'imagen', 'activo',
    ];

    protected function casts(): array
    {
        return ['precio' => 'float', 'stock' => 'integer', 'stock_minimo' => 'integer', 'activo' => 'boolean'];
    }

    public function scopeActivos($q) { return $q->where('activo', true); }

    public function scopeStockBajo($q) { return $q->whereColumn('stock', '<', 'stock_minimo'); }

    public function scopeFiltrar($q, ?string $buscar, ?string $categoria, ?string $color, string $orden = 'asc')
    {
        if ($buscar !== null && $buscar !== '') {
            $like = '%' . $buscar . '%';
            $q->where(fn ($w) => $w->where('nombre', 'like', $like)->orWhere('codigo', 'like', $like));
        }
        if ($categoria) {
            $q->where('categoria', $categoria);
        }
        if ($color) {
            $q->where('color', $color);
        }
        return $q->orderBy('nombre', $orden === 'desc' ? 'desc' : 'asc');
    }

    public function getStockBajoAttribute(): bool
    {
        return $this->stock < $this->stock_minimo;
    }

    /** URL pública de la imagen (guardada en storage/app/public/productos). */
    public function getImagenUrlAttribute(): string
    {
        if (!$this->imagen || $this->imagen === 'sin-imagen.png') {
            return asset('img/sin-imagen.svg');
        }
        return asset('storage/productos/' . $this->imagen);
    }
}
