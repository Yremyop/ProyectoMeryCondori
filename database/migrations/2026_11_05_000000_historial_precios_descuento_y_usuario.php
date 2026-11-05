<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Historial de precios, descuento en Bs por venta y login con nombre de usuario en vez de correo. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('precios_historial', function (Blueprint $t) {
            $t->id();
            $t->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $t->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $t->decimal('precio_anterior', 10, 2);
            $t->decimal('precio_nuevo', 10, 2);
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::table('ventas', fn (Blueprint $t) => $t->decimal('descuento', 10, 2)->default(0));

        Schema::table('usuarios', fn (Blueprint $t) => $t->string('usuario', 50)->nullable());
        // Usuario = parte del correo antes de la @ (solo letras, números, _ y -), con sufijo si se repite.
        $usados = [];
        foreach (DB::table('usuarios')->orderBy('id')->get(['id', 'email']) as $u) {
            $base = preg_replace('/[^a-z0-9_-]/', '', strtolower(strstr($u->email, '@', true) ?: $u->email));
            $base = strlen($base) >= 3 ? substr($base, 0, 45) : 'usuario' . $u->id;
            $nombre = $base;
            for ($i = 2; in_array($nombre, $usados, true); $i++) {
                $nombre = $base . $i;
            }
            $usados[] = $nombre;
            DB::table('usuarios')->where('id', $u->id)->update(['usuario' => $nombre]);
        }
        Schema::table('usuarios', function (Blueprint $t) {
            $t->string('usuario', 50)->nullable(false)->unique()->change();
            $t->dropUnique(['email']);
            $t->dropColumn('email');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', fn (Blueprint $t) => $t->string('email', 150)->nullable());
        foreach (DB::table('usuarios')->get(['id', 'usuario']) as $u) {
            DB::table('usuarios')->where('id', $u->id)->update(['email' => $u->usuario . '@bordadosteo.com']);
        }
        Schema::table('usuarios', function (Blueprint $t) {
            $t->string('email', 150)->nullable(false)->unique()->change();
            $t->dropUnique(['usuario']);
            $t->dropColumn('usuario');
        });
        Schema::table('ventas', fn (Blueprint $t) => $t->dropColumn('descuento'));
        Schema::dropIfExists('precios_historial');
    }
};
