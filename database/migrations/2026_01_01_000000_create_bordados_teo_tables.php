<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Esquema completo de Bordados T&O.
 * Incluye las columnas que el sistema PHP original agregaba "al vuelo"
 * (clientes.documento, productos.codigo/color/imagen/activo, pedidos.total_pedido,
 * tabla pedido_items) y el estado 'Cancelado' que el código usaba pero el SQL no declaraba.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 120);
            $t->string('email', 150)->unique();
            $t->string('password');
            $t->enum('rol', ['Administrador', 'Vendedor'])->default('Vendedor');
            $t->enum('estado', ['Activo', 'Inactivo'])->default('Activo');
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('clientes', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 150);
            $t->string('documento', 50)->default('');
            $t->string('telefono', 30)->nullable();
            $t->string('email', 150)->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('productos', function (Blueprint $t) {
            $t->id();
            $t->string('codigo', 50)->nullable()->unique();
            $t->string('nombre', 150);
            $t->string('categoria', 80)->default('Otros');
            $t->string('color', 40)->default('Otro');
            $t->decimal('precio', 10, 2)->default(0);
            $t->integer('stock')->default(0);
            $t->integer('stock_minimo')->default(5);
            $t->string('imagen')->nullable();
            $t->boolean('activo')->default(true);
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('ventas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $t->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $t->date('fecha');
            $t->decimal('total', 10, 2)->default(0);
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('venta_detalle', function (Blueprint $t) {
            $t->id();
            $t->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $t->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $t->integer('cantidad');
            $t->decimal('precio_unitario', 10, 2);
            $t->decimal('subtotal', 10, 2);
        });

        Schema::create('pedidos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $t->string('diseno', 200);
            $t->string('tamano', 80)->nullable();
            $t->string('colores', 150)->nullable();
            $t->date('fecha_entrega');
            $t->decimal('adelanto', 10, 2)->default(0);
            $t->decimal('total_pedido', 10, 2)->default(0);
            $t->enum('estado', ['Pendiente', 'EnProceso', 'Listo', 'Entregado', 'Cancelado'])->default('Pendiente');
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('pedido_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $t->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $t->string('producto_codigo', 50)->default('');
            $t->string('producto_nombre', 150)->default('');
            $t->integer('cantidad')->default(1);
            $t->decimal('precio_unitario', 10, 2)->default(0);
            $t->decimal('subtotal', 10, 2)->default(0);
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('log_seguridad', function (Blueprint $t) {
            $t->id();
            $t->string('tipo_evento', 60);
            $t->string('detalle', 255)->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('creado_en')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['log_seguridad', 'pedido_items', 'pedidos', 'venta_detalle', 'ventas', 'productos', 'clientes', 'usuarios'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
