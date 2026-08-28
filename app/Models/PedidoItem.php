<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoItem extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'pedido_id', 'producto_id', 'producto_codigo', 'producto_nombre', 'cantidad', 'precio_unitario', 'subtotal',
    ];

    protected function casts(): array
    {
        return ['precio_unitario' => 'float', 'subtotal' => 'float', 'cantidad' => 'integer'];
    }

    public function pedido() { return $this->belongsTo(Pedido::class); }

    public function producto() { return $this->belongsTo(Producto::class); }
}
