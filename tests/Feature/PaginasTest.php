<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Recorre todas las pantallas con datos de demostración para detectar errores de vistas/consultas. */
class PaginasTest extends TestCase
{
    use RefreshDatabase;

    public function test_todas_las_pantallas_cargan_para_el_administrador(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(Usuario::where('rol', 'Administrador')->first());
        $venta = Venta::first();

        $rutas = [
            '/', '/clientes', '/clientes?buscar=Juan', '/clientes/1/edit',
            '/productos', '/productos?buscar=PAR&categoria=Parches%20Personalizados&color=Rojo&orden=desc', '/productos/1/edit',
            '/inventario', '/inventario?buscar=ESP',
            '/ventas', '/ventas?modo_venta=pedido', '/ventas?modo_venta=pedido&editar_pedido_id=1',
            '/pedidos', '/reportes', '/reportes?periodo=semanal&desde=2020-01-01&hasta=2099-12-31',
            '/reportes?periodo=diario', '/reportes?pdf=1', '/cotizador', '/usuarios',
            '/buscar', '/buscar?q=Juan', '/buscar?q=1', '/buscar?q=zzzzzz',
            "/ventas/{$venta->id}/factura", "/ventas/{$venta->id}/factura?print=1",
        ];
        foreach ($rutas as $ruta) {
            $this->get($ruta)->assertOk();
        }
    }

    public function test_dashboard_muestra_alertas_del_seeder(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(Usuario::where('rol', 'Vendedor')->first());

        $this->get('/')->assertOk()
            ->assertSee('Requiere atención')
            ->assertSee('bajo el mínimo')
            ->assertDontSee('Usuarios'); // el vendedor no ve el menú de administración
    }

    public function test_pdfs_se_descargan(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(Usuario::first());
        $venta = Venta::first();

        foreach (["/ventas/{$venta->id}/factura.pdf", '/ventas/historial.pdf'] as $ruta) {
            $r = $this->get($ruta)->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-1.4', $r->getContent());
        }
    }
}
