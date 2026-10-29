@extends('layouts.app')
@section('titulo', 'Pedidos')
@section('nav', 'pedidos')
@section('sub', 'Pedidos personalizados: registro, entrega y estado de producción')

@section('contenido')
<div class="grid grid-2">

  <div class="card card-flush">
    <div class="card-head">
      <h2>Pedidos registrados</h2>
      <span class="chip">{{ $pedidos->total() }} pedido(s)</span>
    </div>
    <div class="table-responsive">
    <table>
      <thead>
        <tr>
          <th>Nº</th>
          <th>Cliente</th>
          <th>Items</th>
          <th class="nowrap">Pedido</th>
          <th class="nowrap">Entrega</th>
          <th class="right">Total</th>
          <th class="right">Adelanto</th>
          <th class="right">Saldo</th>
          <th class="nowrap">Estado</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($pedidos as $p)
        @php $items = $p->items_lista; $atrasado = $p->atrasado; @endphp
        <tr>
          <td class="num muted">{{ $p->id }}</td>
          <td><strong>{{ $p->cliente->nombre ?? '—' }}</strong></td>
          <td>
            @if ($items)
              @foreach ($items as $item)<div>{{ $item }}</div>@endforeach
            @else
              <span class="muted">Sin items</span>
            @endif
          </td>
          <td class="num muted nowrap">{{ fechaCorta($p->created_at) }}</td>
          <td class="num nowrap{{ $atrasado ? '' : ' muted' }}" {{ $atrasado ? 'style="color:var(--wine);font-weight:600;"' : '' }}>
            {{ fechaCorta($p->fecha_entrega) }}
            @if ($atrasado)<br><span style="font-size:11px;">atrasado</span>@endif
          </td>
          <td class="right">{{ dinero($p->total_pedido) }}</td>
          <td class="right muted">{{ dinero($p->adelanto) }}</td>
          <td class="right">@if ($p->saldo > 0)<strong>{{ dinero($p->saldo) }}</strong>@else<span class="muted">Pagado</span>@endif</td>
          <td class="nowrap">
            @if (auth()->user()->esAdmin() && !$p->cerrado)
              <div class="row-actions">
                <form method="POST" action="{{ route('pedidos.estado', $p) }}">
                  @csrf @method('PATCH')
                  <select name="estado" data-autosubmit aria-label="Estado del pedido {{ $p->id }}" style="height:var(--h-sm);font-size:12px;">
                    @foreach (\Illuminate\Support\Arr::except($estados, 'Cancelado') as $k => $l)
                      <option value="{{ $k }}" {{ $k === $p->estado ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                  </select>
                </form>
                <form method="POST" action="{{ route('pedidos.estado', $p) }}">
                  @csrf @method('PATCH')
                  <input type="hidden" name="estado" value="Cancelado">
                  <button type="submit" class="btn btn-wine btn-small"
                          data-confirm="¿Cancelar el pedido Nº {{ $p->id }} de {{ $p->cliente->nombre ?? 'este cliente' }}? No se podrá reabrir.">Cancelar</button>
                </form>
              </div>
            @else
              <span class="estado-tag estado-{{ $p->estado }}">{{ $estados[$p->estado] ?? $p->estado }}</span>
              @if ($p->dias_retraso > 0)
                <br><span style="font-size:11px;color:var(--wine);font-weight:600;">con retraso ({{ $p->dias_retraso }} {{ $p->dias_retraso === 1 ? 'día' : 'días' }})</span>
              @endif
              @if ($p->venta_id)
                <br><a href="{{ route('facturas.show', $p->venta_id) }}" style="font-size:11px;">✓ Facturado</a>
              @endif
            @endif
          </td>
        </tr>
        @empty
        <tr class="table-empty"><td colspan="9">Aún no hay pedidos registrados.</td></tr>
        @endforelse
      </tbody>
    </table>
    </div>
    {{ $pedidos->links('partials.paginacion') }}
  </div>

  <div class="modal-form">
    <h3>Nuevo pedido</h3>

    <form method="POST" action="{{ route('pedidos.carrito.agregar') }}">
      @csrf
      <div class="field">
        <label>Producto</label>
        <select name="producto_id" required>
          @foreach ($productos as $p)
            <option value="{{ $p->id }}">{{ $p->codigo }} — {{ $p->nombre }} ({{ $p->stock }} disp.)</option>
          @endforeach
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

    <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--line-soft);">
      <div class="section-head"><h2>Carrito del pedido</h2></div>
      @if (empty($carrito))
        <div class="empty" style="padding:18px 10px;">{{ icono('caja', 26) }}El carrito está vacío.</div>
      @else
        @foreach ($carrito as $i => $l)
          <div class="cart-line">
            <span>{{ (int)$l['cantidad'] }} × {{ $l['nombre'] }}</span>
            <span class="cart-amount">
              {{ dinero($l['cantidad'] * $l['precio']) }}
              <form method="POST" action="{{ route('pedidos.carrito.quitar', $i) }}">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-wine btn-small btn-icon" title="Quitar del carrito" aria-label="Quitar del carrito">{{ icono('cerrar', 13) }}</button>
              </form>
            </span>
          </div>
        @endforeach
        <div class="cart-total"><span>Total</span><span>{{ dinero($totalCarrito) }}</span></div>
      @endif
    </div>

    <form method="POST" action="{{ route('pedidos.store') }}" style="margin-top:16px;padding-top:16px;border-top:1px solid var(--line-soft);">
      @csrf
      <div class="field">
        <label>Cliente</label>
        <select name="cliente_id" required>
          @foreach ($clientes as $c)
            <option value="{{ $c->id }}" {{ (int) old('cliente_id') === $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-row">
        <div class="field"><label>Fecha de pedido</label><input type="date" name="fecha_pedido" value="{{ old('fecha_pedido', date('Y-m-d')) }}"></div>
        <div class="field"><label>Fecha de entrega</label><input type="date" name="fecha_entrega" value="{{ old('fecha_entrega') }}" required></div>
      </div>
      <div class="form-row">
        <div class="field"><label>Adelanto (Bs)</label><input type="number" step="0.01" min="0" name="adelanto" value="{{ old('adelanto', 0) }}"></div>
        <div class="field">
          <label>Estado</label>
          <select name="estado">
            @foreach ($estados as $k => $l)
              <option value="{{ $k }}" {{ $k === old('estado', 'Pendiente') ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-block">Confirmar pedido</button>
      </div>
    </form>
  </div>

</div>
@endsection
