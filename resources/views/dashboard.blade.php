@extends('layouts.app')
@section('titulo', 'Dashboard')
@section('nav', 'dashboard')
@section('sub', 'Resumen de la operación · ' . $mesActual)

@section('contenido')
<div class="kpi-strip">
  <div class="kpi">
    <div class="label">Ventas del mes</div>
    <div class="value">{{ dinero($ventasMes) }}</div>
    @if ($variacionMes === null)
      <div class="meta">sin comparativo del mes previo</div>
    @elseif ($variacionMes >= 0)
      <div class="meta meta-up">+{{ $variacionMes }}% vs. mes anterior</div>
    @else
      <div class="meta meta-alert">{{ $variacionMes }}% vs. mes anterior</div>
    @endif
  </div>
  <div class="kpi">
    <div class="label">Ventas de hoy</div>
    <div class="value">{{ dinero($ventasHoy->total) }}</div>
    <div class="meta">{{ (int) $ventasHoy->n }} {{ (int) $ventasHoy->n === 1 ? 'venta' : 'ventas' }}</div>
  </div>
  <div class="kpi">
    <div class="label">Venta promedio (últimos 30 días)</div>
    <div class="value">{{ dinero($ticketPromedio) }}</div>
    <div class="meta">últimos 30 días</div>
  </div>
  <div class="kpi">
    <div class="label">Pedidos activos</div>
    <div class="value">{{ $pedidosActivos }}</div>
    @if ($porVencer->count() > 0)
      <div class="meta meta-warn">{{ $porVencer->count() }} vence{{ $porVencer->count() === 1 ? '' : 'n' }} esta semana</div>
    @else
      <div class="meta">sin entregas próximas</div>
    @endif
  </div>
  <div class="kpi">
    <div class="label">Clientes</div>
    <div class="value">{{ $clientesTotal }}</div>
    <div class="meta">{{ $clientesNuevos > 0 ? '+' . $clientesNuevos . ' este mes' : 'sin altas este mes' }}</div>
  </div>
  <div class="kpi{{ $stockBajoTotal > 0 ? ' kpi-alert' : '' }}">
    <div class="label">Stock bajo</div>
    <div class="value">{{ $stockBajoTotal }}</div>
    <div class="meta">@if ($stockBajoTotal > 0)<a href="{{ route('inventario.index') }}" style="color:var(--wine);font-weight:600;">Reponer</a>@else todo por encima del mínimo @endif</div>
  </div>
</div>

<div class="grid grid-2">

  <div class="stack">

    <div class="card">
      <div class="section-head">
        <h2>Pedidos en curso</h2>
        <a href="{{ route('pedidos.index') }}" class="chip">Abrir tablero</a>
      </div>
      <div class="pipeline">
        @foreach ($estados as $clave => $etiqueta)
        <div class="pipe-col pipe-{{ $clave }}">
          <div class="pipe-head">
            <span class="name">{{ $etiqueta }}</span>
            <span class="count">{{ $conteoEstados[$clave] }}</span>
          </div>
          @foreach ($tarjetas[$clave] as $pedido)
            <div class="pipe-card">
              <div class="title">{{ $pedido->cliente->nombre ?? '—' }}</div>
              <div class="meta">
                @php $resumen = trim((string) $pedido->diseno); @endphp
                {{ $resumen !== '' ? mb_strimwidth($resumen, 0, 34, '…') : 'Sin detalle' }}
                · {{ fechaCorta($pedido->fecha_entrega) }}
              </div>
            </div>
          @endforeach
          @php $restantes = $conteoEstados[$clave] - $tarjetas[$clave]->count(); @endphp
          @if ($restantes > 0)
            <div class="pipe-more">+{{ $restantes }} más</div>
          @elseif ($conteoEstados[$clave] === 0)
            <div class="pipe-more">Sin pedidos</div>
          @endif
        </div>
        @endforeach
      </div>
    </div>

    <div class="card card-flush">
      <div class="card-head">
        <h2>Últimas ventas</h2>
        <a href="{{ route('ventas.index') }}" class="chip">Ver las {{ $ventasTotales }}</a>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Nº</th>
              <th>Cliente</th>
              <th>Vendedor</th>
              <th>Fecha</th>
              <th class="right">Total</th>
              <th class="nowrap">Comprobante</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($ultimasVentas as $v)
            <tr>
              <td class="num muted">{{ $v->id }}</td>
              <td><strong>{{ $v->cliente->nombre ?? '—' }}</strong></td>
              <td class="muted">{{ $v->usuario->nombre ?? '—' }}</td>
              <td class="num muted nowrap">{{ fechaCorta($v->fecha) }}</td>
              <td class="right"><strong>{{ dinero($v->total) }}</strong></td>
              <td class="nowrap">
                <div class="row-actions">
                  <a class="btn btn-outline btn-small" href="{{ route('facturas.show', $v) }}">{{ icono('documento', 14) }} Factura</a>
                </div>
              </td>
            </tr>
            @empty
            <tr class="table-empty"><td colspan="6">Aún no hay ventas registradas.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <div class="stack" id="atencion">

    <div class="card">
      <div class="section-head">
        <h2>Requiere atención</h2>
      </div>

      @if ($productosBajos->isEmpty() && $porVencer->isEmpty() && $conSaldo->isEmpty())
        <div class="empty">
          {{ icono('check', 30) }}
          No hay pendientes: stock por encima del mínimo y sin entregas próximas.
        </div>
      @else
      <div class="attn">

        @if ($productosBajos->isNotEmpty())
        <div class="attn-item is-alert">
          <div class="attn-head">
            <span style="color:var(--wine);">{{ icono('alerta', 16) }}</span>
            <div>
              <div class="attn-title">{{ $stockBajoTotal }} producto{{ $stockBajoTotal === 1 ? '' : 's' }} bajo el mínimo</div>
              <div class="attn-meta">
                @php $codigos = $productosBajos->map(fn ($p) => $p->codigo ?: $p->nombre)->all(); @endphp
                {{ implode(', ', $codigos) }}@if ($stockBajoTotal > count($codigos)) y {{ $stockBajoTotal - count($codigos) }} más @endif
              </div>
            </div>
          </div>
          <a href="{{ route('inventario.index') }}" class="btn btn-wine btn-small btn-block">Ver inventario</a>
        </div>
        @endif

        @if ($porVencer->isNotEmpty())
        <div class="attn-item is-warn">
          <div class="attn-head">
            <span style="color:var(--gold);">{{ icono('reloj', 16) }}</span>
            <div>
              <div class="attn-title">{{ $porVencer->count() }} pedido{{ $porVencer->count() === 1 ? '' : 's' }} vence{{ $porVencer->count() === 1 ? '' : 'n' }} esta semana</div>
              <div class="attn-meta">El más próximo: {{ $porVencer->first()->cliente->nombre ?? '—' }}, {{ fechaCorta($porVencer->first()->fecha_entrega) }}</div>
            </div>
          </div>
          <a href="{{ route('pedidos.index') }}" class="btn btn-outline btn-small btn-block">Revisar pedidos</a>
        </div>
        @endif

        @if ($conSaldo->isNotEmpty())
        <div class="attn-item">
          <div class="attn-head">
            <span style="color:var(--ink-soft);">{{ icono('sobre', 16) }}</span>
            <div>
              <div class="attn-title">{{ $conSaldo->count() }} pedido{{ $conSaldo->count() === 1 ? '' : 's' }} con saldo por cobrar</div>
              <div class="attn-meta">{{ dinero($saldoTotal) }} en total · mayor: {{ $conSaldo->first()->cliente->nombre ?? '—' }}</div>
            </div>
          </div>
          <a href="{{ route('pedidos.index') }}" class="btn btn-outline btn-small btn-block">Ver saldos</a>
        </div>
        @endif

      </div>
      @endif
    </div>

    <div class="card">
      <div class="section-head"><h2>Accesos rápidos</h2></div>
      <div class="quick-grid">
        <a href="{{ route('ventas.index') }}" class="btn btn-outline btn-small">Nueva venta</a>
        <a href="{{ route('pedidos.index') }}" class="btn btn-outline btn-small">Nuevo pedido</a>
        <a href="{{ route('clientes.index') }}" class="btn btn-outline btn-small">Nuevo cliente</a>
        <a href="{{ route('productos.index') }}" class="btn btn-outline btn-small">Catálogo</a>
      </div>
    </div>

  </div>
</div>
@endsection
