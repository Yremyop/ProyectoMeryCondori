<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Bitácora de eventos de seguridad (equivale a registrarEventoSeguridad()). */
class LogSeguridad extends Model
{
    protected $table = 'log_seguridad';

    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = null;

    protected $fillable = ['tipo_evento', 'detalle', 'ip'];

    public static function registrar(string $tipo, string $detalle = ''): void
    {
        try {
            static::create([
                'tipo_evento' => $tipo,
                'detalle' => mb_substr($detalle, 0, 255),
                'ip' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            // La bitácora nunca debe interrumpir la aplicación.
        }
    }
}
