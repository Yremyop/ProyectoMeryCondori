<div class="grid grid-4">
  <div class="stat-card">
    <div class="label">Nº de pedidos</div>
    <div class="value">{{ $pedidos->count() }}</div>
  </div>
  <div class="stat-card">
    <div class="label">Total pedidos (sin cancelados)</div>
    <div class="value">{{ dinero($totalPedidos) }}</div>
  </div>
  <div class="stat-card">
    <div class="label">Adelantos cobrados</div>
    <div class="value">{{ dinero($adelantos) }}</div>
  </div>
  <div class="stat-card">
    <div class="label">Saldo por cobrar</div>
    <div class="value">{{ dinero($saldoPendiente) }}</div>
  </div>
</div>

<div class="card card-flush">
  <div class="card-head"><h2>Pedidos por estado</h2></div>
  <div class="table-responsive">
    <table>
      <thead><tr><th>Estado</th><th class="right">Pedidos</th><th class="right">Total</th></tr></thead>
      <tbody>
        @foreach ($porEstado as $row)
        <tr>
          <td><strong>{{ $row['nombre'] }}</strong></td>
          <td class="right">{{ $row['pedidos'] }}</td>
          <td class="right">{{ dinero($row['total']) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<div class="card card-flush">
  <div class="card-head">
    <h2>Detalle de pedidos</h2>
    <span class="chip">{{ $pedidos->count() }} pedido(s)</span>
  </div>
  <div class="table-responsive">
    <table>
      <thead>
        <tr>
          <th>Nº</th><th class="nowrap">Pedido</th><th class="nowrap">Entrega</th><th>Cliente</th><th>Items</th>
          <th class="right">Total</th><th class="right">Adelanto</th><th class="right">Saldo</th><th>Estado</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($pedidos as $p)
        <tr>
          <td class="num muted">{{ $p->id }}</td>
          <td class="num muted nowrap">{{ fechaCorta($p->created_at) }}</td>
          <td class="num muted nowrap">{{ fechaCorta($p->fecha_entrega) }}</td>
          <td><strong>{{ $p->cliente->nombre ?? '—' }}</strong></td>
          <td class="muted">{{ implode(' · ', $p->items_lista) ?: 'Sin items' }}</td>
          <td class="right">{{ dinero($p->total_pedido) }}</td>
          <td class="right muted">{{ dinero($p->adelanto) }}</td>
          <td class="right">{{ dinero($p->saldo) }}</td>
          <td class="nowrap"><span class="estado-tag estado-{{ $p->estado }}">{{ \App\Models\Pedido::ESTADOS[$p->estado] ?? $p->estado }}</span></td>
        </tr>
        @empty
        <tr class="table-empty"><td colspan="9">No hay pedidos con los filtros seleccionados.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
