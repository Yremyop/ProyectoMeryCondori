<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['cliente_id', 'usuario_id', 'fecha', 'total'];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'total' => 'float'];
    }

    public function cliente() { return $this->belongsTo(Cliente::class); }

    public function usuario() { return $this->belongsTo(Usuario::class); }

    public function detalles() { return $this->hasMany(VentaDetalle::class); }

    public function getItemsTextoAttribute(): string
    {
        return $this->detalles->map(fn ($d) => $d->cantidad . 'x ' . ($d->producto->nombre ?? ''))->implode(', ');
    }
}
