@extends('layouts.app')
@section('titulo', 'Búsqueda')
@section('nav', '')
@section('sub', $q !== '' ? 'Resultados para “' . $q . '”' : 'Busca clientes, productos, ventas o pedidos')

@section('contenido')
@if ($q === '')

  <div class="card">
    <div class="empty">
      {{ icono('buscar', 30) }}
      Escribe en el buscador de la barra superior para encontrar clientes, productos, ventas o pedidos.
      <span style="font-size:11.5px;">También puedes buscar por número de venta o de pedido.</span>
    </div>
  </div>

@elseif ($total === 0)

  <div class="card">
    <div class="empty">
      {{ icono('vacio', 30) }}
      No se encontró nada para “{{ $q }}”.
      <span style="font-size:11.5px;">Prueba con parte del nombre, el código del producto o el número de venta.</span>
    </div>
  </div>

@else

  @if ($res['clientes']->isNotEmpty())
  <div class="card card-flush">
    <div class="card-head">
      <h2>Clientes</h2>
      <span class="chip">{{ $res['clientes']->count() }}</span>
    </div>
    <div class="table-responsive">
      <table>
        <thead><tr><th>Nombre</th><th>Carnet/NIT</th><th>Teléfono</th><th>Email</th><th class="nowrap">Acciones</th></tr></thead>
        <tbody>
          @foreach ($res['clientes'] as $c)
          <tr>
            <td><strong>{{ $c->nombre }}</strong></td>
            <td class="muted">{{ $c->documento ?: '—' }}</td>
            <td class="muted">{{ $c->telefono ?: '—' }}</td>
            <td class="muted">{{ $c->email ?: '—' }}</td>
            <td class="nowrap"><div class="row-actions">
              <a class="btn btn-outline btn-small" href="{{ route('clientes.edit', $c) }}">Abrir</a>
            </div></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  @if ($res['productos']->isNotEmpty())
  <div class="card card-flush">
    <div class="card-head">
      <h2>Productos</h2>
      <span class="chip">{{ $res['productos']->count() }}</span>
    </div>
    <div class="table-responsive">
      <table>
        <thead><tr><th>Código</th><th>Nombre</th><th>Categoría</th><th>Color</th><th class="right">Precio</th><th class="right">Stock</th><th class="nowrap">Acciones</th></tr></thead>
        <tbody>
          @foreach ($res['productos'] as $p)
          <tr>
            <td class="muted">{{ $p->codigo ?: '—' }}</td>
            <td><strong>{{ $p->nombre }}</strong></td>
            <td class="muted">{{ $p->categoria ?? 'Otros' }}</td>
            <td><span class="color-badge"><span class="color-dot color-{{ $p->color ?? 'Otro' }}"></span>{{ $p->color ?? 'Otro' }}</span></td>
            <td class="right">{{ dinero($p->precio) }}</td>
            <td class="right">
              <span class="stock-badge {{ $p->stock_bajo ? 'stock-low' : 'stock-ok' }}">{{ $p->stock }}</span>
            </td>
            <td class="nowrap"><div class="row-actions">
              <a class="btn btn-outline btn-small" href="{{ route('productos.edit', $p) }}">Abrir</a>
            </div></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  @if ($res['ventas']->isNotEmpty())
  <div class="card card-flush">
    <div class="card-head">
      <h2>Ventas</h2>
      <span class="chip">{{ $res['ventas']->count() }}</span>
    </div>
    <div class="table-responsive">
      <table>
        <thead><tr><th>Nº</th><th>Cliente</th><th>Fecha</th><th class="right">Total</th><th class="nowrap">Acciones</th></tr></thead>
        <tbody>
          @foreach ($res['ventas'] as $v)
          <tr>
            <td class="num muted">{{ $v->id }}</td>
            <td><strong>{{ $v->cliente->nombre ?? '—' }}</strong></td>
            <td class="num muted nowrap">{{ fechaCorta($v->fecha) }}</td>
            <td class="right"><strong>{{ dinero($v->total) }}</strong></td>
            <td class="nowrap"><div class="row-actions">
              <a class="btn btn-outline btn-small" href="{{ route('facturas.show', $v) }}">{{ icono('documento', 14) }} Factura</a>
            </div></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  @if ($res['pedidos']->isNotEmpty())
  <div class="card card-flush">
    <div class="card-head">
      <h2>Pedidos</h2>
      <span class="chip">{{ $res['pedidos']->count() }}</span>
    </div>
    <div class="table-responsive">
      <table>
        <thead><tr><th>Nº</th><th>Cliente</th><th>Detalle</th><th>Entrega</th><th class="right">Saldo</th><th>Estado</th><th class="nowrap">Acciones</th></tr></thead>
        <tbody>
          @foreach ($res['pedidos'] as $p)
          <tr>
            <td class="num muted">{{ $p->id }}</td>
            <td><strong>{{ $p->cliente->nombre ?? '—' }}</strong></td>
            <td class="muted">{{ mb_strimwidth((string) $p->diseno, 0, 44, '…') }}</td>
            <td class="num muted nowrap">{{ fechaCorta($p->fecha_entrega) }}</td>
            <td class="right">@if ($p->saldo > 0){{ dinero($p->saldo) }}@else<span class="muted">—</span>@endif</td>
            <td><span class="estado-tag estado-{{ $p->estado }}">{{ \App\Models\Pedido::ESTADOS[$p->estado] ?? $p->estado }}</span></td>
            <td class="nowrap"><div class="row-actions">
              <a class="btn btn-outline btn-small" href="{{ route('pedidos.index') }}">Abrir</a>
            </div></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

@endif
@endsection
