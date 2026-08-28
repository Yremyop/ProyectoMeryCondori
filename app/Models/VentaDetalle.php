<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaDetalle extends Model
{
    protected $table = 'venta_detalle';

    public $timestamps = false;

    protected $fillable = ['venta_id', 'producto_id', 'cantidad', 'precio_unitario', 'subtotal'];

    protected function casts(): array
    {
        return ['precio_unitario' => 'float', 'subtotal' => 'float', 'cantidad' => 'integer'];
    }

    public function venta() { return $this->belongsTo(Venta::class); }

    public function producto() { return $this->belongsTo(Producto::class); }
}
