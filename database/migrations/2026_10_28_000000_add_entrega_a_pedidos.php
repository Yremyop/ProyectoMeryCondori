<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Fecha real de entrega (para medir retrasos) y la venta con la que se facturó el pedido. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $t) {
            $t->date('entregado_at')->nullable();
            $t->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $t) {
            $t->dropConstrainedForeignId('venta_id');
            $t->dropColumn('entregado_at');
        });
    }
};
