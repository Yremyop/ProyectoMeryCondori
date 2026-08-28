<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['nombre', 'documento', 'telefono', 'email'];

    public function ventas() { return $this->hasMany(Venta::class); }

    public function pedidos() { return $this->hasMany(Pedido::class); }

    public function scopeBuscar($q, ?string $texto)
    {
        if ($texto === null || $texto === '') {
            return $q;
        }
        $like = '%' . $texto . '%';
        return $q->where(fn ($w) => $w->where('nombre', 'like', $like)
            ->orWhere('documento', 'like', $like)
            ->orWhere('telefono', 'like', $like)
            ->orWhere('email', 'like', $like));
    }
}
