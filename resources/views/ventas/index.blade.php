@extends('layouts.app')
@section('titulo', 'Ventas')
@section('nav', 'ventas')
@section('sub', 'Registro de ventas directas y ventas a partir de un pedido')

@section('contenido')
<div class="card">
  <div class="section-head">
    <h2>Tipo de venta</h2>
    <a href="{{ route('clientes.index') }}" class="chip">+ Nuevo cliente</a>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <a href="{{ route('ventas.index', ['modo_venta' => 'directa']) }}" class="chip{{ $modo === 'directa' ? ' is-on' : '' }}">Venta directa</a>
    <a href="{{ route('ventas.index', ['modo_venta' => 'pedido']) }}" class="chip{{ $modo === 'pedido' ? ' is-on' : '' }}">Venta por pedido</a>
  </div>
</div>

@if ($modo === 'pedido')

  @if ($pedidoEdicion && $pedidoEdicion->items->isNotEmpty())
  <div class="card card-flush">
    <div class="card-head">
      <h2>Editar ítems del pedido Nº {{ $pedidoEdicion->id }}</h2>
      <a href="{{ route('ventas.index', ['modo_venta' => 'pedido']) }}" class="chip">Cancelar</a>
    </div>
    <form method="POST" action="{{ route('ventas.pedido', $pedidoEdicion) }}">
      @csrf
      <div class="table-responsive">
        <table>
          <thead>
            <tr><th>Producto</th><th class="right">Cantidad</th><th class="nowrap">Quitar</th></tr>
          </thead>
          <tbody>
            @foreach ($pedidoEdicion->items as $item)
            <tr>
              <td><strong>{{ $item->producto_nombre ?: 'Producto' }}</strong></td>
              <td class="right">
                <input type="number" name="cantidades[{{ $item->id }}]" value="{{ $item->cantidad }}"
                       min="0" style="width:88px;height:var(--h-sm);text-align:right;">
              </td>
              <td class="nowrap">
                <label style="display:inline-flex;align-items:center;gap:7px;font-size:12.5px;color:var(--ink-soft);">
                  <input type="checkbox" name="eliminar[]" value="{{ $item->id }}" style="width:auto;height:auto;">
                  Quitar
                </label>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="card-body" style="border-top:1px solid var(--line-soft);display:flex;justify-content:flex-end;">
        <button type="submit" class="btn btn-primary">Guardar cambios y vender</button>
      </div>
    </form>
  </div>
  @endif

  <div class="card card-flush">
    <div class="card-head">
      <h2>Pedidos registrados</h2>
      <span class="chip">{{ $pedidos->count() }} pedido(s)</span>
    </div>
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>Nº</th>
            <th>Cliente</th>
            <th>Items</th>
            <th class="nowrap">Entrega</th>
            <th class="right">Total</th>
            <th class="right">Saldo</th>
            <th class="nowrap">Estado</th>
            <th class="nowrap">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($pedidos as $pedido)
          <tr>
            <td class="num muted">{{ $pedido->id }}</td>
            <td><strong>{{ $pedido->cliente->nombre ?? '—' }}</strong></td>
            <td class="muted">{{ $pedido->items->isNotEmpty() ? implode(' · ', $pedido->items_lista) : 'Sin items' }}</td>
            <td class="num muted nowrap">{{ fechaCorta($pedido->fecha_entrega) }}</td>
            <td class="right">{{ dinero($pedido->total_pedido) }}</td>
            <td class="right">@if ($pedido->saldo > 0)<strong>{{ dinero($pedido->saldo) }}</strong>@else<span class="muted">Pagado</span>@endif</td>
            <td class="nowrap"><span class="estado-tag estado-{{ $pedido->estado }}">{{ \App\Models\Pedido::ESTADOS[$pedido->estado] ?? $pedido->estado }}</span></td>
            <td class="nowrap">
              <div class="row-actions">
                <a href="{{ route('ventas.index', ['modo_venta' => 'pedido', 'editar_pedido_id' => $pedido->id]) }}" class="btn btn-outline btn-small">Editar y vender</a>
              </div>
            </td>
          </tr>
          @empty
          <tr class="table-empty"><td colspan="8">Aún no hay pedidos registrados.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

@else

  <div class="grid grid-2">

    <div class="card">
      <div class="section-head"><h2>Agregar productos</h2></div>
      <form method="POST" action="{{ route('ventas.carrito.agregar') }}">
        @csrf
        <div class="field">
          <label>Producto</label>
          <select name="producto_id">
            @forelse ($productos as $p)
              <option value="{{ $p->id }}">{{ $p->nombre }} · {{ $p->categoria }} ({{ $p->stock }} disp.)</option>
            @empty
              <option value="">No hay productos disponibles</option>
            @endforelse
          </select>
        </div>
        <div class="form-row">
          <div class="field"><label>Cantidad</label><input type="number" name="cantidad" value="1" min="1"></div>
          <div class="field">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-outline btn-block">Añadir al carrito</button>
          </div>
        </div>
      </form>
    </div>

    <div class="card">
      <div class="section-head"><h2>Carrito de venta</h2></div>
      @if (empty($carrito))
        <div class="empty" style="padding:18px 10px;">{{ icono('caja', 26) }}El carrito está vacío.</div>
      @else
        @foreach ($carrito as $i => $l)
          <div class="cart-line">
            <span>{{ (int)$l['cantidad'] }} × {{ $l['nombre'] }}</span>
            <span class="cart-amount">
              {{ dinero($l['cantidad'] * $l['precio']) }}
              <form method="POST" action="{{ route('ventas.carrito.quitar', $i) }}">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-wine btn-small btn-icon" title="Quitar del carrito" aria-label="Quitar del carrito">{{ icono('cerrar', 13) }}</button>
              </form>
            </span>
          </div>
        @endforeach
        <div class="cart-total"><span>Total</span><span>{{ dinero($totalCarrito) }}</span></div>
      @endif

      <form method="POST" action="{{ route('ventas.store') }}" style="margin-top:16px;padding-top:16px;border-top:1px solid var(--line-soft);">
        @csrf
        <div class="field">
          <label>Cliente</label>
          <select name="cliente_id">
            <option value="">— Selecciona un cliente —</option>
            @foreach ($clientes as $c)
              <option value="{{ $c->id }}" {{ (int) old('cliente_id') === $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
            @endforeach
          </select>
        </div>
        <button type="submit" class="btn btn-primary btn-block"{{ empty($carrito) ? ' disabled' : '' }}>Confirmar venta</button>
      </form>
    </div>

  </div>

@endif

<div class="card card-flush">
  <div class="card-head">
    <h2>Historial de ventas</h2>
    <div style="display:flex;gap:8px;align-items:center;">
      <span class="chip">{{ $historial->total() }} venta(s)</span>
      <a class="btn btn-outline btn-small" href="{{ route('ventas.pdf') }}">{{ icono('descargar', 14) }} Descargar PDF</a>
    </div>
  </div>
  <div class="table-responsive">
    <table>
      <thead>
        <tr>
          <th>Nº</th>
          <th>Cliente</th>
          <th>Vendedor</th>
          <th class="nowrap">Fecha</th>
          <th>Productos</th>
          <th class="right">Total</th>
          <th class="nowrap">Comprobante</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($historial as $v)
        <tr>
          <td class="num muted">{{ $v->id }}</td>
          <td><strong>{{ $v->cliente->nombre ?? '—' }}</strong></td>
          <td class="muted">{{ $v->usuario->nombre ?? '—' }}</td>
          <td class="num muted nowrap">{{ fechaCorta($v->fecha) }}</td>
          <td class="muted">{{ $v->items_texto }}</td>
          <td class="right"><strong>{{ dinero($v->total) }}</strong></td>
          <td class="nowrap">
            <div class="row-actions">
              <a class="btn btn-outline btn-small" href="{{ route('facturas.show', $v) }}">{{ icono('documento', 14) }} Factura</a>
            </div>
          </td>
        </tr>
        @empty
        <tr class="table-empty"><td colspan="7">Aún no hay ventas registradas.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{ $historial->links('partials.paginacion') }}
</div>
@endsection
