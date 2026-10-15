<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModulosTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->admin();
        $this->actingAs($this->admin);
    }

    // ---------- Clientes ----------
    public function test_clientes_crud_y_documento_unico(): void
    {
        $this->post('/clientes', ['nombre' => ' Ana ', 'documento' => '123 456', 'telefono' => '7', 'email' => 'a@a.com'])
            ->assertRedirect(route('clientes.index'));
        $ana = Cliente::first();
        $this->assertSame('Ana', $ana->nombre);
        $this->assertSame('123456', $ana->documento);

        // Mismo documento (con espacios / minúsculas) para otro cliente: rechazado.
        $this->post('/clientes', ['nombre' => 'Otra', 'documento' => '123  456'])->assertSessionHasErrors('documento');
        $this->assertSame(1, Cliente::count());

        // Editar al mismo cliente conservando su documento: permitido.
        $this->put("/clientes/{$ana->id}", ['nombre' => 'Ana María', 'documento' => '123456'])->assertSessionHasNoErrors();
        $this->assertSame('Ana María', $ana->fresh()->nombre);

        $this->delete("/clientes/{$ana->id}")->assertSessionHas('ok');
        $this->assertSame(0, Cliente::count());
    }

    public function test_no_se_elimina_cliente_con_ventas(): void
    {
        $c = $this->cliente();
        Venta::create(['cliente_id' => $c->id, 'usuario_id' => $this->admin->id, 'fecha' => today(), 'total' => 10]);

        $this->delete("/clientes/{$c->id}")->assertSessionHas('error');
        $this->assertSame(1, Cliente::count());
    }

    // ---------- Productos ----------
    public function test_productos_alta_con_imagen_codigo_unico_y_desactivar(): void
    {
        Storage::fake('public');
        $datos = ['codigo' => 'AV001', 'nombre' => 'Camisa', 'categoria' => 'Esp. Aventureros', 'color' => 'Azul', 'precio' => '25.50', 'stock' => 7];

        $this->post('/productos', $datos + ['imagen' => UploadedFile::fake()->createWithContent('foto.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGP4z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg=='))])->assertSessionHasNoErrors();
        $p = Producto::first();
        $this->assertSame(7, $p->stock);
        $this->assertNotNull($p->imagen);
        Storage::disk('public')->assertExists('productos/' . $p->imagen);

        $this->post('/productos', $datos)->assertSessionHasErrors('codigo');          // duplicado
        $otro = $this->producto(['codigo' => 'OTRO1']);
        $this->put("/productos/{$otro->id}", ['codigo' => 'AV001'] + $datos)->assertSessionHasErrors('codigo'); // choca con otro producto
        $this->put("/productos/{$p->id}", ['nombre' => 'Camisa v2'] + $datos)->assertSessionHasNoErrors();      // mismo producto: ok
        $this->assertSame('Camisa v2', $p->fresh()->nombre);

        $this->post('/productos', ['codigo' => 'X', 'categoria' => 'Inventada'] + $datos)->assertSessionHasErrors('categoria');
        $this->post('/productos', ['codigo' => 'Y', 'imagen' => UploadedFile::fake()->create('virus.php', 5)] + $datos)->assertSessionHasErrors('imagen');

        $this->delete("/productos/{$p->id}");
        $this->assertFalse($p->fresh()->activo);
        $this->get('/productos')->assertDontSee('Camisa v2');
    }

    public function test_filtros_de_catalogo_se_aplican(): void
    {
        $this->producto(['codigo' => 'R1', 'nombre' => 'Parche Rojo', 'color' => 'Rojo']);
        $this->producto(['codigo' => 'A1', 'nombre' => 'Parche Azul', 'color' => 'Azul']);

        $this->get('/productos?color=Azul')->assertSee('Parche Azul')->assertDontSee('Parche Rojo');
        $this->get('/productos?buscar=R1')->assertSee('Parche Rojo')->assertDontSee('Parche Azul');
        $this->get('/inventario?color=Rojo')->assertSee('Parche Rojo')->assertDontSee('Parche Azul');
    }

    // ---------- Inventario ----------
    public function test_ajuste_de_stock_nunca_queda_negativo(): void
    {
        $p = $this->producto(['stock' => 3]);
        $this->post("/inventario/{$p->id}/ajustar", ['delta' => 5]);
        $this->assertSame(8, $p->fresh()->stock);
        $this->post("/inventario/{$p->id}/ajustar", ['delta' => -100]);
        $this->assertSame(0, $p->fresh()->stock);
        $this->post("/inventario/{$p->id}/ajustar", ['delta' => 'abc'])->assertSessionHasErrors('delta');
    }

    // ---------- Ventas ----------
    public function test_venta_directa_descuenta_stock_y_genera_factura(): void
    {
        $c = $this->cliente();
        $a = $this->producto(['codigo' => 'A', 'precio' => 10, 'stock' => 10]);
        $b = $this->producto(['codigo' => 'B', 'precio' => 5, 'stock' => 4]);

        $this->post('/ventas/carrito', ['producto_id' => $a->id, 'cantidad' => 3])->assertSessionHas('ok');
        $this->post('/ventas/carrito', ['producto_id' => $b->id, 'cantidad' => 2]);
        $this->post('/ventas/carrito', ['producto_id' => $b->id, 'cantidad' => 3])->assertSessionHas('error'); // 2+3 > 4 en stock

        $r = $this->post('/ventas', ['cliente_id' => $c->id]);
        $venta = Venta::with('detalles')->firstOrFail();
        $r->assertRedirect(route('facturas.show', $venta));

        $this->assertEquals(40.0, $venta->total);
        $this->assertCount(2, $venta->detalles);
        $this->assertSame(7, $a->fresh()->stock);
        $this->assertSame(2, $b->fresh()->stock);
        $this->assertSame($this->admin->id, $venta->usuario_id);

        // El carrito se vacía tras confirmar.
        $this->post('/ventas', ['cliente_id' => $c->id])->assertSessionHas('error');
        $this->assertSame(1, Venta::count());
    }

    public function test_venta_valida_cliente_y_carrito_vacio(): void
    {
        $this->post('/ventas', [])->assertSessionHasErrors('cliente_id');
        $this->post('/ventas', ['cliente_id' => $this->cliente()->id])->assertSessionHas('error');
    }

    public function test_carrito_no_permite_productos_inactivos_ni_cantidades_invalidas(): void
    {
        $p = $this->producto(['activo' => false]);
        $this->post('/ventas/carrito', ['producto_id' => $p->id, 'cantidad' => 1])->assertSessionHas('error');
        $ok = $this->producto();
        $this->post('/ventas/carrito', ['producto_id' => $ok->id, 'cantidad' => 0])->assertSessionHasErrors('cantidad');
    }

    public function test_quitar_del_carrito(): void
    {
        $p = $this->producto();
        $this->post('/ventas/carrito', ['producto_id' => $p->id, 'cantidad' => 1]);
        $this->post('/ventas/carrito', ['producto_id' => $p->id, 'cantidad' => 2]);
        $this->delete('/ventas/carrito/0');
        $this->assertCount(1, session('carrito_venta'));
        $this->assertSame(2, session('carrito_venta')[0]['cantidad']);
    }

    // ---------- Pedidos ----------
    private function crearPedido(Cliente $c, Producto $p, int $cant, float $adelanto = 0): Pedido
    {
        $this->post('/pedidos/carrito', ['producto_id' => $p->id, 'cantidad' => $cant]);
        $this->post('/pedidos', [
            'cliente_id' => $c->id, 'fecha_pedido' => '2026-01-10', 'fecha_entrega' => today()->addDays(3)->toDateString(),
            'estado' => 'Pendiente', 'adelanto' => $adelanto,
        ])->assertSessionHasNoErrors()->assertRedirect(route('pedidos.index'));

        return Pedido::with('items')->latest('id')->first();
    }

    public function test_pedido_se_registra_con_items_y_total(): void
    {
        $p = $this->producto(['precio' => 12, 'stock' => 1]); // el pedido no reserva stock
        $pedido = $this->crearPedido($this->cliente(), $p, 5, 20);

        $this->assertEquals(60.0, $pedido->total_pedido);
        $this->assertEquals(40.0, $pedido->saldo);
        $this->assertCount(1, $pedido->items);
        $this->assertSame('2026-01-10', $pedido->created_at->toDateString());
        $this->assertSame(1, $p->fresh()->stock);
    }

    public function test_adelanto_mayor_al_total_se_rechaza(): void
    {
        $p = $this->producto(['precio' => 10]);
        $this->post('/pedidos/carrito', ['producto_id' => $p->id, 'cantidad' => 1]);
        $this->post('/pedidos', ['cliente_id' => $this->cliente()->id, 'fecha_entrega' => '2026-12-01', 'estado' => 'Pendiente', 'adelanto' => 99])
            ->assertSessionHas('error');
        $this->assertSame(0, Pedido::count());
    }

    public function test_pedido_se_convierte_en_venta_una_sola_vez(): void
    {
        $p = $this->producto(['precio' => 10, 'stock' => 10]);
        $pedido = $this->crearPedido($this->cliente(), $p, 4);
        $item = $pedido->items->first();

        // Edita la cantidad de 4 a 3 y vende.
        $this->post("/ventas/pedido/{$pedido->id}", ['cantidades' => [$item->id => 3]])->assertSessionHasNoErrors();
        $venta = Venta::with('detalles')->firstOrFail();
        $this->assertEquals(30.0, $venta->total);
        $this->assertSame(7, $p->fresh()->stock);
        $this->assertSame('Entregado', $pedido->fresh()->estado);
        $this->assertEquals(30.0, $pedido->fresh()->total_pedido);

        // Intentar venderlo otra vez no duplica la venta ni el descuento de stock.
        $this->post("/ventas/pedido/{$pedido->id}", [])->assertSessionHas('error');
        $this->assertSame(1, Venta::count());
        $this->assertSame(7, $p->fresh()->stock);
    }

    public function test_pedido_sin_stock_suficiente_no_se_vende_y_no_modifica_nada(): void
    {
        $p = $this->producto(['precio' => 10, 'stock' => 2]);
        $pedido = $this->crearPedido($this->cliente(), $p, 5);

        $this->post("/ventas/pedido/{$pedido->id}", [])->assertSessionHas('error');
        $this->assertSame(0, Venta::count());
        $this->assertSame(2, $p->fresh()->stock);
        $this->assertSame('Pendiente', $pedido->fresh()->estado);
    }

    public function test_quitar_todos_los_items_revierte_la_edicion(): void
    {
        $p = $this->producto(['stock' => 10]);
        $pedido = $this->crearPedido($this->cliente(), $p, 2);

        $this->post("/ventas/pedido/{$pedido->id}", ['eliminar' => [$pedido->items->first()->id]])->assertSessionHas('error');
        $this->assertCount(1, $pedido->fresh()->items); // la transacción se revirtió
    }

    public function test_solo_el_admin_cambia_el_estado_del_pedido(): void
    {
        $pedido = $this->crearPedido($this->cliente(), $this->producto(), 1);
        $this->patch("/pedidos/{$pedido->id}/estado", ['estado' => 'Listo']);
        $this->assertSame('Listo', $pedido->fresh()->estado);
        $this->patch("/pedidos/{$pedido->id}/estado", ['estado' => 'Inventado'])->assertSessionHasErrors('estado');

        $this->actingAs($this->vendedor());
        $this->patch("/pedidos/{$pedido->id}/estado", ['estado' => 'Cancelado'])->assertForbidden();
        $this->assertSame('Listo', $pedido->fresh()->estado);
    }

    // ---------- Reportes ----------
    public function test_reportes_no_duplican_importes_en_ventas_con_varias_lineas(): void
    {
        $c = $this->cliente();
        $a = $this->producto(['codigo' => 'A', 'precio' => 10, 'stock' => 100, 'categoria' => 'Emblema Aventureros', 'color' => 'Rojo']);
        $b = $this->producto(['codigo' => 'B', 'precio' => 5, 'stock' => 100, 'categoria' => 'Esp. Aventureros', 'color' => 'Azul']);

        foreach ([[$a, 3, $b, 2], [$a, 1, $b, 1]] as [$p1, $q1, $p2, $q2]) {
            $this->post('/ventas/carrito', ['producto_id' => $p1->id, 'cantidad' => $q1]);
            $this->post('/ventas/carrito', ['producto_id' => $p2->id, 'cantidad' => $q2]);
            $this->post('/ventas', ['cliente_id' => $c->id]);
        }
        $this->assertSame(2, Venta::count()); // totales: 40 y 15 = 55

        $r = $this->get('/reportes?periodo=mensual')->assertOk();
        $d = $r->viewData('ingresos');
        $this->assertEquals(55.0, $d);
        $this->assertSame(2, $r->viewData('numVentas'));
        $this->assertEquals(55.0, $r->viewData('porCliente')->first()['total']);
        $this->assertEquals(55.0, $r->viewData('ventasPeriodo')->first()['total']);
        $this->assertSame([4, 3], $r->viewData('porProducto')->pluck('cantidad')->all()); // A=3+1, B=2+1

        // Filtros por color / producto.
        $r = $this->get('/reportes?color=Azul')->assertOk();
        $this->assertSame(3, $r->viewData('porColor')->first()['cantidad']);
        $this->assertCount(1, $r->viewData('porColor'));
        $r = $this->get('/reportes?producto=' . $a->id)->assertOk();
        $this->assertSame(4, $r->viewData('porProducto')->first()['cantidad']);

        // Fuera de rango: sin ventas.
        $r = $this->get('/reportes?desde=2000-01-01&hasta=2000-12-31')->assertOk();
        $this->assertSame(0, $r->viewData('numVentas'));
        // Fechas inválidas se ignoran sin error.
        $this->get('/reportes?desde=basura&hasta=2026-99-99')->assertOk();
    }

    // ---------- Cotizador ----------
    public function test_cotizador_calcula_y_valida(): void
    {
        $base = ['ancho' => 8, 'alto' => 6, 'cantidad' => 100, 'densidad' => 'Media', 'respaldo' => 'Velcro', 'diseno_nuevo' => 'Si', 'urgencia' => 'Normal'];

        $r = $this->post('/cotizador', $base)->assertOk();
        $this->assertEqualsWithDelta(901.76, $r->viewData('r')['total'], 0.001);
        $r->assertSee('Bs 901.76');

        $this->post('/cotizador', ['ancho' => 0] + $base)->assertSessionHasErrors('ancho');
        $this->post('/cotizador', ['densidad' => 'Ultra'] + $base)->assertSessionHasErrors('densidad');
    }

    // ---------- Usuarios ----------
    public function test_usuarios_alta_cifrada_y_activacion(): void
    {
        $this->post('/usuarios', ['nombre' => 'Nuevo', 'email' => 'n@n.com', 'password' => 'clave-larga-1', 'rol' => 'Vendedor'])
            ->assertSessionHasNoErrors();
        $u = Usuario::where('email', 'n@n.com')->first();
        $this->assertNotSame('clave-larga-1', $u->getAuthPassword());
        $this->assertTrue(\Hash::check('clave-larga-1', $u->getAuthPassword()));

        $this->post('/usuarios', ['nombre' => 'Dup', 'email' => 'n@n.com', 'password' => 'clave-larga-1', 'rol' => 'Vendedor'])->assertSessionHasErrors('email');
        $this->post('/usuarios', ['nombre' => 'Corta', 'email' => 'c@c.com', 'password' => '123', 'rol' => 'Vendedor'])->assertSessionHasErrors('password');
        $this->post('/usuarios', ['nombre' => 'Rol', 'email' => 'r@r.com', 'password' => 'clave-larga-1', 'rol' => 'Dios'])->assertSessionHasErrors('rol');

        $this->patch("/usuarios/{$u->id}/estado");
        $this->assertSame('Inactivo', $u->fresh()->estado);
        $this->patch("/usuarios/{$this->admin->id}/estado")->assertSessionHas('error'); // no se puede desactivar a sí mismo
        $this->assertSame('Activo', $this->admin->fresh()->estado);
    }

    // ---------- Búsqueda ----------
    public function test_busqueda_global(): void
    {
        $c = $this->cliente(['nombre' => 'Zulema Quispe']);
        $p = $this->producto(['nombre' => 'Zapato bordado', 'codigo' => 'ZZ1']);
        $v = Venta::create(['cliente_id' => $c->id, 'usuario_id' => $this->admin->id, 'fecha' => today(), 'total' => 5]);

        $this->get('/buscar?q=Zulema')->assertSee('Zulema Quispe')->assertDontSee('Zapato bordado');
        $this->get('/buscar?q=ZZ1')->assertSee('Zapato bordado');
        $this->get('/buscar?q=' . $v->id)->assertOk();
        $this->get('/buscar?q=%25')->assertOk(); // comodín: no rompe
        $this->get('/buscar?q=' . urlencode("'; DROP TABLE clientes;--"))->assertOk();
        $this->assertSame(1, Cliente::count());
    }

    public function test_html_se_escapa_en_las_vistas(): void
    {
        $this->cliente(['nombre' => '<script>alert(1)</script>', 'documento' => '9']);
        $this->get('/clientes')->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
