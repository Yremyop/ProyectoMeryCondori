<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuarios';

    public const UPDATED_AT = null;

    protected $fillable = ['nombre', 'email', 'password', 'rol', 'estado'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    // La tabla original no tiene remember_token.
    public function getRememberTokenName(): string
    {
        return '';
    }

    public function esAdmin(): bool
    {
        return $this->rol === 'Administrador';
    }

    public function activo(): bool
    {
        return $this->estado === 'Activo';
    }
}
