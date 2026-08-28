<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Database\Seeder;

/**
 * Datos de demostración. NO ejecutar en producción tal cual:
 * cambia las contraseñas de los usuarios de ejemplo antes de usarlos.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Usuario::create(['nombre' => 'Mery Condori Mamani', 'email' => 'admin@bordadosteo.com', 'password' => 'admin123', 'rol' => 'Administrador', 'estado' => 'Activo']);
        $vend = Usuario::create(['nombre' => 'José Fernández', 'email' => 'vendedor@bordadosteo.com', 'password' => 'vendedor123', 'rol' => 'Vendedor', 'estado' => 'Activo']);

        $clientes = collect([
            ['Juan Pérez', '1234567', '70000001', 'juan@example.com'],
            ['María Gómez', '7654321', '70000002', 'maria@example.com'],
            ['Club Conquistadores Esperanza', '9988776', '70000003', null],
        ])->map(fn ($c) => Cliente::create(['nombre' => $c[0], 'documento' => $c[1], 'telefono' => $c[2], 'email' => $c[3]]));

        $productos = collect([
            ['PAR001', 'Parche bordado logo', 'Parches Personalizados', 'Rojo', 15, 120],
            ['ESP001', 'Especialidad Aventureros — Artesanía', 'Esp. Aventureros', 'Azul', 12, 40],
            ['ESP002', 'Especialidad Conquistadores — Campismo', 'Esp. Conquistadores', 'Verde', 14, 4],
            ['EMB001', 'Emblema Conquistadores', 'Emblema Conquistadores', 'Guindo', 20, 25],
            ['EMB002', 'Emblema Aventureros', 'Emblema Aventureros', 'Amarillo', 18, 3],
        ])->map(fn ($p) => Producto::create([
            'codigo' => $p[0], 'nombre' => $p[1], 'categoria' => $p[2], 'color' => $p[3], 'precio' => $p[4], 'stock' => $p[5],
        ]));

        // Dos ventas de ejemplo (el stock ya está descontado en los números de arriba).
        foreach ([[0, 0, now()->subDays(2), [[0, 20], [3, 5]]], [1, 1, now()->subDays(1), [[1, 10]]]] as [$ci, $u, $fecha, $lineas]) {
            $venta = Venta::create(['cliente_id' => $clientes[$ci]->id, 'usuario_id' => [$admin, $vend][$u]->id, 'fecha' => $fecha->toDateString(), 'total' => 0]);
            $total = 0;
            foreach ($lineas as [$pi, $cant]) {
                $precio = (float) $productos[$pi]->precio;
                VentaDetalle::create(['venta_id' => $venta->id, 'producto_id' => $productos[$pi]->id, 'cantidad' => $cant, 'precio_unitario' => $precio, 'subtotal' => $cant * $precio]);
                $total += $cant * $precio;
            }
            $venta->update(['total' => $total]);
        }

        // Pedidos de ejemplo con sus productos.
        foreach ([[2, 5, 'Pendiente', 100, [[0, 30]]], [1, 20, 'EnProceso', 50, [[2, 10], [4, 6]]]] as [$ci, $dias, $estado, $adelanto, $lineas]) {
            $total = collect($lineas)->sum(fn ($l) => $l[1] * $productos[$l[0]]->precio);
            $pedido = Pedido::create([
                'cliente_id' => $clientes[$ci]->id,
                'diseno' => collect($lineas)->map(fn ($l) => $l[1] . 'x ' . $productos[$l[0]]->nombre)->implode(', '),
                'fecha_entrega' => today()->addDays($dias), 'adelanto' => $adelanto, 'total_pedido' => $total, 'estado' => $estado,
            ]);
            foreach ($lineas as [$pi, $cant]) {
                $pedido->items()->create([
                    'producto_id' => $productos[$pi]->id, 'producto_codigo' => $productos[$pi]->codigo,
                    'producto_nombre' => $productos[$pi]->nombre, 'cantidad' => $cant,
                    'precio_unitario' => $productos[$pi]->precio, 'subtotal' => $cant * $productos[$pi]->precio,
                ]);
            }
        }
    }
}
