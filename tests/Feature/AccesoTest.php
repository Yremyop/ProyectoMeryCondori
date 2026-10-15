<?php

namespace Tests\Feature;

use App\Models\LogSeguridad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccesoTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_es_enviado_al_login(): void
    {
        foreach (['/', '/clientes', '/ventas', '/reportes', '/usuarios'] as $ruta) {
            $this->get($ruta)->assertRedirect(route('login'));
        }
    }

    public function test_login_correcto_y_cabeceras_de_seguridad(): void
    {
        $this->admin();
        $this->get('/login')->assertOk()->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Content-Security-Policy');

        $this->post('/login', ['email' => 'ADMIN@test.com', 'password' => 'admin12345'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_login_incorrecto_mensaje_generico_y_bitacora(): void
    {
        $this->admin();
        $this->post('/login', ['email' => 'admin@test.com', 'password' => 'mala'])
            ->assertSessionHasErrors(['email' => 'Correo o contraseña incorrectos.']);
        $this->assertGuest();
        $this->assertDatabaseHas('log_seguridad', ['tipo_evento' => 'login_fallido']);
    }

    public function test_usuario_inactivo_no_puede_entrar(): void
    {
        $this->admin(['estado' => 'Inactivo']);
        $this->post('/login', ['email' => 'admin@test.com', 'password' => 'admin12345'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_bloqueo_tras_cinco_intentos_fallidos(): void
    {
        $this->admin();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'admin@test.com', 'password' => 'mala']);
        }
        // Aun con la contraseña correcta, la cuenta sigue bloqueada.
        $this->post('/login', ['email' => 'admin@test.com', 'password' => 'admin12345'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertTrue(LogSeguridad::where('tipo_evento', 'fuerza_bruta_bloqueo')->exists());
    }

    public function test_contrasena_en_texto_plano_no_rompe_el_login(): void
    {
        \DB::table('usuarios')->insert(['nombre' => 'Legacy', 'email' => 'legacy@test.com', 'password' => 'admin123', 'rol' => 'Vendedor', 'estado' => 'Activo']);
        $this->post('/login', ['email' => 'legacy@test.com', 'password' => 'admin123'])->assertSessionHasErrors('email');

        $this->artisan('usuarios:hashear-legacy')->assertSuccessful();
        $this->post('/login', ['email' => 'legacy@test.com', 'password' => 'admin123'])->assertRedirect(route('dashboard'));
    }

    public function test_logout_requiere_post_y_cierra_sesion(): void
    {
        $this->actingAs($this->admin());
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_vendedor_no_accede_a_modulos_de_administrador(): void
    {
        $this->actingAs($this->vendedor());
        foreach (['/reportes', '/cotizador', '/usuarios'] as $ruta) {
            $this->get($ruta)->assertForbidden();
        }
        $this->assertDatabaseHas('log_seguridad', ['tipo_evento' => 'acceso_no_autorizado']);
    }

    public function test_peticion_sin_csrf_es_rechazada(): void
    {
        $this->actingAs($this->admin());
        // Laravel omite la verificación CSRF cuando el entorno es "testing"; se activa para esta prueba.
        $this->app['env'] = 'local';

        $this->post('/clientes', ['nombre' => 'X', 'documento' => '1'])->assertStatus(419);
        $this->assertDatabaseHas('log_seguridad', ['tipo_evento' => 'csrf_invalido']);
        $this->assertDatabaseMissing('clientes', ['nombre' => 'X']);
    }
}
