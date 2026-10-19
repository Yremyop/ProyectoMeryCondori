<?php

use App\Http\Middleware\EsAdministrador;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\UsuarioActivo;
use App\Models\LogSeguridad;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['admin' => EsAdministrador::class, 'activo' => UsuarioActivo::class]);
        $middleware->web(append: [SecurityHeaders::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Deja evidencia de tokens CSRF inválidos (HTTP 419) en la bitácora de seguridad.
        $exceptions->render(function (HttpException $e) {
            if ($e->getStatusCode() === 419) {
                LogSeguridad::registrar('csrf_invalido', 'Token CSRF inválido o ausente');
            }
            return null;
        });
    })->create();
