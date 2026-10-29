<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
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
        $datos = ['nombre' => 'Camisa', 'categoria' => 'Esp. Aventureros', 'color' => 'Azul', 'stock' => 7, 'stock_minimo' => 3];

        $this->post('/productos', $datos + ['imagen' => UploadedFile::fake()->createWithContent('foto.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGP4z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg=='))])->assertSessionHasNoErrors();
        $p = Producto::first();
        $this->assertSame('EAAZU001', $p->codigo);
        $this->assertSame(7, $p->stock);
        $this->assertSame(3, $p->stock_minimo);
        $this->assertEquals(0.0, $p->precio); // el precio se asigna en Inventario
        $this->assertNotNull($p->imagen);
        Storage::disk('public')->assertExists('productos/' . $p->imagen);

        // Reemplazar la imagen borra la anterior.
        $vieja = $p->imagen;
        $this->put("/productos/{$p->id}", $datos + ['imagen' => UploadedFile::fake()->createWithContent('otra.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGP4z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg=='))])->assertSessionHasNoErrors();
        $p->refresh();
        $this->assertNotSame($vieja, $p->imagen);
        Storage::disk('public')->assertMissing('productos/' . $vieja);
        Storage::disk('public')->assertExists('productos/' . $p->imagen);

        // Código consecutivo por categoría + color; no cambia al editar.
        $this->post('/productos', $datos)->assertSessionHasNoErrors();
        $this->assertTrue(Producto::where('codigo', 'EAAZU002')->exists());
        $this->post('/productos', ['categoria' => 'Emblema Aventureros', 'color' => 'cafe'] + $datos);
        $this->assertTrue(Producto::where('codigo', 'EMACAF001')->exists());
        $this->put("/productos/{$p->id}", ['nombre' => 'Camisa v2', 'color' => 'Rojo', 'codigo' => 'HACK'] + $datos)->assertSessionHasNoErrors();
        $this->assertSame('Camisa v2', $p->fresh()->nombre);
        $this->assertSame('EAAZU001', $p->fresh()->codigo);

        $this->post('/productos', ['categoria' => 'Inventada'] + $datos)->assertSessionHasErrors('categoria');
        $this->post('/productos', ['imagen' => UploadedFile::fake()->create('virus.php', 5)] + $datos)->assertSessionHasErrors('imagen');

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
    public function test_admin_modifica_stock_y_precio_sin_negativos(): void
    {
        $p = $this->producto(['stock' => 3, 'precio' => 10]);
        $this->post("/inventario/{$p->id}/ajustar", ['stock' => 8, 'precio' => '12.50'])->assertSessionHasNoErrors();
        $this->assertSame(8, $p->fresh()->stock);
        $this->assertEquals(12.5, $p->fresh()->precio);
        $this->post("/inventario/{$p->id}/ajustar", ['stock' => -1, 'precio' => -5])->assertSessionHasErrors(['stock', 'precio']);
        $this->post("/inventario/{$p->id}/ajustar", ['stock' => 'abc', 'precio' => 1])->assertSessionHasErrors('stock');
        $this->assertSame(8, $p->fresh()->stock);

        $this->post("/inventario/{$p->id}/ajustar", ['stock' => 0, 'precio' => 12.5]);
        $this->get('/inventario')->assertSee('Sin stock');
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

    public function test_venta_cobra_el_precio_vigente_al_confirmar(): void
    {
        $p = $this->producto(['precio' => 10, 'stock' => 5]);
        $this->post('/ventas/carrito', ['producto_id' => $p->id, 'cantidad' => 2]);
        $p->update(['precio' => 12]);

        $this->post('/ventas', ['cliente_id' => $this->cliente()->id]);
        $venta = Venta::with('detalles')->firstOrFail();
        $this->assertEquals(24.0, $venta->total);
        $this->assertEquals(12.0, $venta->detalles->first()->precio_unitario);
    }

    public function test_error_de_base_en_venta_no_se_muestra_al_usuario(): void
    {
        $p = $this->producto(['stock' => 5]);
        $this->post('/ventas/carrito', ['producto_id' => $p->id, 'cantidad' => 2]);
        Schema::drop('venta_detalle');

        $this->post('/ventas', ['cliente_id' => $this->cliente()->id])->assertStatus(500)->assertSessionMissing('error');
        $this->assertSame(0, Venta::count());
        $this->assertSame(5, $p->fresh()->stock);
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

    public function test_adelanto_igual_al_total_con_centavos_se_acepta(): void
    {
        // 0.7 + 0.1 en coma flotante da 0.7999…; sin redondeo, un adelanto de 0.80 parecía mayor al total.
        $this->post('/pedidos/carrito', ['producto_id' => $this->producto(['precio' => 0.7])->id, 'cantidad' => 1]);
        $this->post('/pedidos/carrito', ['producto_id' => $this->producto(['precio' => 0.1])->id, 'cantidad' => 1]);
        $this->post('/pedidos', ['cliente_id' => $this->cliente()->id, 'fecha_entrega' => today()->toDateString(), 'estado' => 'Pendiente', 'adelanto' => '0.80'])
            ->assertSessionMissing('error');
        $this->assertEquals(0.0, Pedido::firstOrFail()->saldo);
    }

    public function test_fecha_de_entrega_no_puede_ser_anterior_al_pedido(): void
    {
        $this->post('/pedidos/carrito', ['producto_id' => $this->producto()->id, 'cantidad' => 1]);
        $this->post('/pedidos', ['cliente_id' => $this->cliente()->id, 'fecha_pedido' => '2026-03-10', 'fecha_entrega' => '2026-03-09', 'estado' => 'Pendiente'])
            ->assertSessionHasErrors('fecha_entrega');
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
        $this->assertSame($venta->id, $pedido->fresh()->venta_id);
        $this->get('/pedidos')->assertSee('✓ Facturado');

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

    public function test_pedido_entregado_o_cancelado_no_se_reabre(): void
    {
        $p = $this->producto(['stock' => 10]);
        $entregado = $this->crearPedido($this->cliente(), $p, 2);
        $this->post("/ventas/pedido/{$entregado->id}", [])->assertSessionHasNoErrors();

        // Reabrirlo permitiría venderlo y descontar stock otra vez.
        $this->patch("/pedidos/{$entregado->id}/estado", ['estado' => 'Listo'])->assertSessionHas('error');
        $this->assertSame('Entregado', $entregado->fresh()->estado);
        $this->post("/ventas/pedido/{$entregado->id}", [])->assertSessionHas('error');
        $this->assertSame(1, Venta::count());
        $this->assertSame(8, $p->fresh()->stock);

        $cancelado = $this->crearPedido(Cliente::first(), $p, 1);
        $this->patch("/pedidos/{$cancelado->id}/estado", ['estado' => 'Cancelado'])->assertSessionHas('ok');
        $this->patch("/pedidos/{$cancelado->id}/estado", ['estado' => 'Pendiente'])->assertSessionHas('error');
        $this->assertSame('Cancelado', $cancelado->fresh()->estado);
    }

    public function test_pedido_entregado_tarde_muestra_el_retraso(): void
    {
        $p = $this->producto(['stock' => 10]);
        $pedido = $this->crearPedido($this->cliente(), $p, 1);
        $pedido->update(['fecha_entrega' => today()->subDays(3)]);

        $this->post("/ventas/pedido/{$pedido->id}", [])->assertSessionHasNoErrors();
        $this->assertSame(3, $pedido->fresh()->dias_retraso);
        $this->get('/pedidos')->assertSee('con retraso (3 días)');

        $aTiempo = $this->crearPedido(Cliente::first(), $p, 1);
        $aTiempo->update(['fecha_entrega' => today()->addDay()]);
        $this->patch("/pedidos/{$aTiempo->id}/estado", ['estado' => 'Entregado']);
        $this->assertSame(0, $aTiempo->fresh()->dias_retraso);
        $this->assertNotNull($aTiempo->fresh()->entregado_at);
    }

    public function test_venta_por_pedido_permite_anadir_items(): void
    {
        $p = $this->producto(['precio' => 10, 'stock' => 10]);
        $extra = $this->producto(['codigo' => 'EXTRA', 'precio' => 4, 'stock' => 10]);
        $pedido = $this->crearPedido($this->cliente(), $p, 2);

        $this->post("/ventas/pedido/{$pedido->id}/item", ['producto_id' => $extra->id, 'cantidad' => 3])
            ->assertRedirect(route('ventas.index', ['modo_venta' => 'pedido', 'editar_pedido_id' => $pedido->id]));
        $this->assertCount(2, $pedido->fresh()->items);
        $this->assertEquals(32.0, $pedido->fresh()->total_pedido);
        $this->post("/ventas/pedido/{$pedido->id}/item", ['producto_id' => $extra->id, 'cantidad' => 0])->assertSessionHasErrors('cantidad');

        $this->post("/ventas/pedido/{$pedido->id}", [])->assertSessionHasNoErrors();
        $this->assertEquals(32.0, Venta::firstOrFail()->total);
        $this->assertSame(7, $extra->fresh()->stock);

        // Pedido ya facturado: no admite más items.
        $this->post("/ventas/pedido/{$pedido->id}/item", ['producto_id' => $extra->id, 'cantidad' => 1])->assertSessionHas('error');
        $this->assertCount(2, $pedido->fresh()->items);
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
        $this->assertSame([$a->id], $r->viewData('detalle')->pluck('producto_id')->unique()->values()->all());
        $this->assertSame(4, $r->viewData('detalle')->sum('cantidad'));

        // Fuera de rango: sin ventas.
        $r = $this->get('/reportes?desde=2000-01-01&hasta=2000-12-31')->assertOk();
        $this->assertSame(0, $r->viewData('numVentas'));
        // Fechas inválidas se ignoran sin error.
        $this->get('/reportes?desde=basura&hasta=2026-99-99')->assertOk();
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

        $this->patch("/usuarios/{$u->id}/rol")->assertSessionHas('ok');
        $this->assertSame('Administrador', $u->fresh()->rol);
        $this->patch("/usuarios/{$u->id}/rol");
        $this->assertSame('Vendedor', $u->fresh()->rol);
        $this->patch("/usuarios/{$this->admin->id}/rol")->assertSessionHas('error'); // no se puede cambiar su propio rol
        $this->assertSame('Administrador', $this->admin->fresh()->rol);
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
