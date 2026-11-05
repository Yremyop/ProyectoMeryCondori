<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrecioHistorial extends Model
{
    protected $table = 'precios_historial';

    public const UPDATED_AT = null;

    protected $fillable = ['producto_id', 'usuario_id', 'precio_anterior', 'precio_nuevo'];

    protected function casts(): array
    {
        return ['precio_anterior' => 'float', 'precio_nuevo' => 'float', 'created_at' => 'datetime'];
    }

    public function producto() { return $this->belongsTo(Producto::class); }

    public function usuario() { return $this->belongsTo(Usuario::class); }
}
