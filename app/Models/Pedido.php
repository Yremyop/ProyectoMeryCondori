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

    /** Estados en producción: cuentan como pedidos activos y pueden atrasarse. */
    public const ACTIVOS = ['Pendiente', 'EnProceso'];

    /** Estados cerrados: el pedido ya no cambia de estado ni puede venderse. */
    public const FINALES = ['Entregado', 'Cancelado'];

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

    public function scopeActivos($q) { return $q->whereIn('estado', self::ACTIVOS); }

    /** @return string[] líneas "3x Parche logo" */
    public function getItemsListaAttribute(): array
    {
        return $this->items->map(fn ($i) => $i->cantidad . 'x ' . ($i->producto_nombre ?: 'Producto'))->all();
    }

    public function getCerradoAttribute(): bool
    {
        return in_array($this->estado, self::FINALES, true);
    }

    public function getSaldoAttribute(): float
    {
        return $this->total_pedido - $this->adelanto;
    }

    public function getAtrasadoAttribute(): bool
    {
        return in_array($this->estado, self::ACTIVOS, true)
            && $this->fecha_entrega && $this->fecha_entrega->lt(today());
    }
}
