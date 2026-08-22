<?php

namespace Tests;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function admin(array $extra = []): Usuario
    {
        return Usuario::create($extra + ['nombre' => 'Admin Prueba', 'email' => 'admin@test.com', 'password' => 'admin12345', 'rol' => 'Administrador', 'estado' => 'Activo']);
    }

    protected function vendedor(array $extra = []): Usuario
    {
        return Usuario::create($extra + ['nombre' => 'Vendedor Prueba', 'email' => 'vend@test.com', 'password' => 'vend12345', 'rol' => 'Vendedor', 'estado' => 'Activo']);
    }

    protected function cliente(array $extra = []): Cliente
    {
        return Cliente::create($extra + ['nombre' => 'Cliente Uno', 'documento' => '1234567', 'telefono' => '70000000']);
    }

    protected function producto(array $extra = []): Producto
    {
        return Producto::create($extra + [
            'codigo' => 'P' . random_int(1000, 9999), 'nombre' => 'Parche Prueba', 'categoria' => 'Parches Personalizados',
            'color' => 'Rojo', 'precio' => 10, 'stock' => 20,
        ]);
    }
}
