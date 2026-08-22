<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('titulo', 'Bordados Teo') · Bordados T&amp;O</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/style.css') }}?v=7">
</head>
<body>
@php
    $user = auth()->user();
    $esAdmin = $user->esAdmin();
    $activo = trim($__env->yieldContent('nav'));
    $navPrincipal = [
        ['dashboard',  'Dashboard',  route('dashboard'),        'dashboard',  false],
        ['clientes',   'Clientes',   route('clientes.index'),   'clientes',   false],
        ['catalogo',   'Catálogo',   route('productos.index'),  'catalogo',   false],
        ['inventario', 'Inventario', route('inventario.index'), 'inventario', $alertas['stock_bajo'] > 0],
        ['ventas',     'Ventas',     route('ventas.index'),     'ventas',     false],
        ['pedidos',    'Pedidos',    route('pedidos.index'),    'pedidos',    $alertas['por_vencer'] > 0],
    ];
    $navAdmin = [
        ['reportes',  'Reportes',  route('reportes.index'),  'reportes'],
        ['cotizador', 'Cotizador', route('cotizador.index'), 'cotizador'],
        ['usuarios',  'Usuarios',  route('usuarios.index'),  'usuarios'],
    ];
@endphp
<div class="shell">

  <aside class="rail no-print" id="rail">
    <a class="rail-brand" href="{{ route('dashboard') }}">
      {{ icono('hilo', 22) }}
      <span class="rail-label">Bordados T&amp;O</span>
    </a>

    <nav class="rail-nav">
      @foreach ($navPrincipal as [$ic, $texto, $href, $clave, $conAviso])
      <a class="rail-item{{ $activo === $clave ? ' is-active' : '' }}" href="{{ $href }}" title="{{ $texto }}">
        {{ icono($ic) }}
        <span class="rail-label">{{ $texto }}</span>
        @if ($conAviso)<span class="rail-dot"></span>@endif
      </a>
      @endforeach

      @if ($esAdmin)
      <div class="rail-sep"></div>
      @foreach ($navAdmin as [$ic, $texto, $href, $clave])
      <a class="rail-item{{ $activo === $clave ? ' is-active' : '' }}" href="{{ $href }}" title="{{ $texto }}">
        {{ icono($ic) }}
        <span class="rail-label">{{ $texto }}</span>
      </a>
      @endforeach
      @endif
    </nav>

    <div class="rail-foot">
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="rail-item" title="Cerrar sesión">
          {{ icono('salir') }}
          <span class="rail-label">Cerrar sesión</span>
        </button>
      </form>
    </div>
  </aside>

  <div class="main">
    <header class="appbar no-print">
      <button class="appbar-burger" type="button" aria-label="Abrir menú" aria-expanded="false">{{ icono('menu', 18) }}</button>

      <div class="appbar-title"><h1>@yield('titulo', 'Bordados Teo')</h1></div>

      <form class="appbar-search" method="GET" action="{{ route('buscar') }}" role="search">
        {{ icono('buscar', 15) }}
        <input type="search" name="q" placeholder="Buscar cliente, producto o pedido…"
               value="{{ request('q') }}" aria-label="Buscar en el sistema">
        <kbd>Ctrl K</kbd>
      </form>

      <div class="appbar-actions">
        @if ($alertas['total'] > 0)
          <a class="chip chip-alert" href="{{ route('dashboard') }}#atencion" title="Pendientes que requieren atención">
            <span class="dot"></span>{{ $alertas['total'] }} {{ $alertas['total'] === 1 ? 'alerta' : 'alertas' }}
          </a>
        @endif
        <a class="btn btn-primary btn-small" href="{{ route('ventas.index') }}">{{ icono('mas', 15) }}<span>Nueva venta</span></a>
        <div class="avatar" title="{{ $user->nombre }} · {{ $user->rol }}">{{ iniciales($user->nombre) }}</div>
      </div>
    </header>

    <main class="content">
      @hasSection('sub')
      <div class="page-head">
        <div class="sub">@yield('sub')</div>
        @hasSection('acciones')<div class="page-head-actions">@yield('acciones')</div>@endif
      </div>
      @endif

      @include('partials.mensajes')

      @yield('contenido')
    </main>
  </div>
</div>
<script src="{{ asset('js/app.js') }}?v=7" defer></script>
</body>
</html>
