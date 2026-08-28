<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    public const UPDATED_AT = null;

    public const ESTADOS = [
        'Pendiente' => 'Pendiente', 'EnProceso' => 'En proceso', 'Listo' => 'Listo',
        'Entregado' => 'Entregado', 'Cancelado' => 'Cancelado',
    ];

    protected $fillable = [
        'cliente_id', 'diseno', 'tamano', 'colores', 'fecha_entrega', 'adelanto', 'total_pedido', 'estado', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha_entrega' => 'date', 'created_at' => 'datetime',
            'adelanto' => 'float', 'total_pedido' => 'float',
        ];
    }

    public function cliente() { return $this->belongsTo(Cliente::class); }

    public function items() { return $this->hasMany(PedidoItem::class); }

    public function scopeActivos($q) { return $q->whereIn('estado', ['Pendiente', 'EnProceso']); }

    /** @return string[] líneas "3x Parche logo" */
    public function getItemsListaAttribute(): array
    {
        return $this->items->map(fn ($i) => $i->cantidad . 'x ' . ($i->producto_nombre ?: 'Producto'))->all();
    }

    public function getSaldoAttribute(): float
    {
        return $this->total_pedido - $this->adelanto;
    }

    public function getAtrasadoAttribute(): bool
    {
        return in_array($this->estado, ['Pendiente', 'EnProceso'], true)
            && $this->fecha_entrega && $this->fecha_entrega->lt(today());
    }
}
