<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Índices para las columnas que filtran el dashboard, los reportes y las alertas. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', fn (Blueprint $t) => $t->index('fecha'));
        Schema::table('pedidos', function (Blueprint $t) {
            $t->index('estado');
            $t->index('fecha_entrega');
        });
        Schema::table('productos', fn (Blueprint $t) => $t->index('activo'));
    }

    public function down(): void
    {
        Schema::table('ventas', fn (Blueprint $t) => $t->dropIndex(['fecha']));
        Schema::table('pedidos', function (Blueprint $t) {
            $t->dropIndex(['estado']);
            $t->dropIndex(['fecha_entrega']);
        });
        Schema::table('productos', fn (Blueprint $t) => $t->dropIndex(['activo']));
    }
};
