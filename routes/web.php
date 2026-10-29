<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BusquedaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

// --- Acceso ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// --- Módulos (usuario autenticado) ---
Route::middleware(['auth', 'activo', 'throttle:app'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/buscar', [BusquedaController::class, 'index'])->name('buscar');

    Route::resource('clientes', ClienteController::class)->except(['create', 'show', 'destroy']);
    Route::resource('productos', ProductoController::class)->only(['index']);

    Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');

    Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
    Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');
    Route::post('/ventas/carrito', [VentaController::class, 'agregar'])->name('ventas.carrito.agregar');
    Route::delete('/ventas/carrito/{indice}', [VentaController::class, 'quitar'])->whereNumber('indice')->name('ventas.carrito.quitar');
    Route::post('/ventas/pedido/{pedido}', [VentaController::class, 'venderPedido'])->name('ventas.pedido');
    Route::post('/ventas/pedido/{pedido}/item', [VentaController::class, 'agregarItemPedido'])->name('ventas.pedido.item');
    Route::get('/ventas/historial.pdf', [VentaController::class, 'pdf'])->name('ventas.pdf');
    Route::get('/ventas/{venta}/factura', [FacturaController::class, 'show'])->name('facturas.show');
    Route::get('/ventas/{venta}/factura.pdf', [FacturaController::class, 'pdf'])->name('facturas.pdf');

    Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
    Route::post('/pedidos', [PedidoController::class, 'store'])->name('pedidos.store');
    Route::post('/pedidos/carrito', [PedidoController::class, 'agregar'])->name('pedidos.carrito.agregar');
    Route::delete('/pedidos/carrito/{indice}', [PedidoController::class, 'quitar'])->whereNumber('indice')->name('pedidos.carrito.quitar');

    // --- Solo Administrador ---
    Route::middleware('admin')->group(function () {
        Route::patch('/pedidos/{pedido}/estado', [PedidoController::class, 'cambiarEstado'])->name('pedidos.estado');

        Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
        Route::resource('productos', ProductoController::class)->only(['edit', 'store', 'update', 'destroy']);
        Route::post('/inventario/{producto}/ajustar', [InventarioController::class, 'ajustar'])->name('inventario.ajustar');

        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');

        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::patch('/usuarios/{usuario}/estado', [UsuarioController::class, 'cambiarEstado'])->name('usuarios.estado');
        Route::patch('/usuarios/{usuario}/rol', [UsuarioController::class, 'cambiarRol'])->name('usuarios.rol');
    });
});
