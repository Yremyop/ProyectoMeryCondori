@extends('layouts.app')
@section('titulo', 'Cotizador')
@section('nav', 'cotizador')
@section('sub', 'Estima el precio de producción según medidas, densidad y volumen')

@section('contenido')
<div class="grid grid-2">

  <div class="card">
    <div class="section-head"><h2>Datos de la cotización</h2></div>
    <form method="post" action="{{ route('cotizador.calcular') }}">
      @csrf

      <div class="form-row">
        <div class="field">
          <label for="cliente">Nombre del cliente</label>
          <input type="text" id="cliente" name="cliente" value="{{ $f['cliente'] ?? '' }}" placeholder="Ej. Colegio San Martín">
        </div>
        <div class="field">
          <label for="diseno">Nombre del diseño</label>
          <input type="text" id="diseno" name="diseno" value="{{ $f['diseno'] ?? '' }}" placeholder="Ej. Escudo institucional">
        </div>
      </div>

      <div class="form-row-3 form-row">
        <div class="field">
          <label for="ancho">Ancho (cm)</label>
          <input type="number" step="0.1" min="0.1" id="ancho" name="ancho" value="{{ $f['ancho'] }}" required>
        </div>
        <div class="field">
          <label for="alto">Alto (cm)</label>
          <input type="number" step="0.1" min="0.1" id="alto" name="alto" value="{{ $f['alto'] }}" required>
        </div>
        <div class="field">
          <label for="cantidad">Cantidad</label>
          <input type="number" step="1" min="1" id="cantidad" name="cantidad" value="{{ $f['cantidad'] }}" required>
        </div>
      </div>

      <div class="field">
        <label for="densidad">Densidad de bordado</label>
        <select id="densidad" name="densidad">
          @foreach ($c['densidad'] as $nombre => $costo)
            <option value="{{ $nombre }}" {{ $f['densidad'] === $nombre ? 'selected' : '' }}>
              {{ $nombre }} ({{ bs($costo, $c['moneda']) }}/cm²)
            </option>
          @endforeach
        </select>
      </div>

      <div class="field">
        <label for="respaldo">Tipo de respaldo</label>
        <select id="respaldo" name="respaldo">
          @foreach ($c['respaldo'] as $nombre => $costo)
            <option value="{{ $nombre }}" {{ $f['respaldo'] === $nombre ? 'selected' : '' }}>
              {{ $nombre }} (+{{ bs($costo, $c['moneda']) }}/u)
            </option>
          @endforeach
        </select>
      </div>

      <div class="form-row">
        <div class="field">
          <label for="diseno_nuevo">Diseño nuevo (matriz)</label>
          <select id="diseno_nuevo" name="diseno_nuevo">
            <option value="Si" {{ $f['diseno_nuevo'] === 'Si' ? 'selected' : '' }}>Sí, requiere digitalización</option>
            <option value="No" {{ $f['diseno_nuevo'] === 'No' ? 'selected' : '' }}>No, ya existe la matriz</option>
          </select>
        </div>
        <div class="field">
          <label for="urgencia">Urgencia</label>
          <select id="urgencia" name="urgencia">
            <option value="Normal" {{ $f['urgencia'] === 'Normal' ? 'selected' : '' }}>Normal</option>
            <option value="Express" {{ $f['urgencia'] === 'Express' ? 'selected' : '' }}>Express (+{{ (int)($c['recargo_urgencia'] * 100) }}%)</option>
          </select>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-block">Calcular cotización</button>
      </div>
    </form>
  </div>

  <div class="card card-flush">
    <div class="card-head"><h2>Desglose del precio</h2></div>

    @if (!$r)
      <div class="empty">
        {{ icono('cotizador', 30) }}
        Completa el formulario y presiona <strong>Calcular cotización</strong> para ver el desglose aquí.
      </div>
    @else
      <div class="table-responsive">
        <table>
          <thead><tr><th>Concepto</th><th class="right">Valor</th></tr></thead>
          <tbody>
            <tr><td>Área del diseño</td><td class="right">{{ number_format($r['area'], 2) }} cm²</td></tr>
            <tr><td>Costo de producción (unitario)</td><td class="right">{{ bs($r['costo_prod_unit'], $c['moneda']) }}</td></tr>
            <tr><td>Costo de respaldo (unitario)</td><td class="right">{{ bs($r['costo_resp_unit'], $c['moneda']) }}</td></tr>
            <tr><td>Subtotal costo (unitario)</td><td class="right">{{ bs($r['subtotal_unit'], $c['moneda']) }}</td></tr>
            <tr><td>Precio con margen (unitario)</td><td class="right">{{ bs($r['precio_margen'], $c['moneda']) }}</td></tr>
            <tr><td>Descuento por volumen</td><td class="right">{{ number_format($r['descuento'] * 100, 0) }}%</td></tr>
            <tr><td>Recargo por urgencia</td><td class="right">{{ bs($r['recargo_urgencia'], $c['moneda']) }}</td></tr>
            <tr><td><strong>Precio final (unitario)</strong></td><td class="right"><strong>{{ bs($r['precio_final_unit'], $c['moneda']) }}</strong></td></tr>
            <tr><td>Subtotal producción × {{ $r['cantidad'] }} u.</td><td class="right">{{ bs($r['subtotal_produccion'], $c['moneda']) }}</td></tr>
            <tr><td>Matriz / digitalización (cargo único)</td><td class="right">{{ bs($r['costo_matriz'], $c['moneda']) }}</td></tr>
          </tbody>
        </table>
      </div>

      <div class="card-body" style="border-top:1px solid var(--line-soft);">
        <div class="stat-card">
          <div class="label">Precio total de la cotización</div>
          <div class="value">{{ bs($r['total'], $c['moneda']) }}</div>
          <div class="sub">
            {{ ($f['diseno'] ?? '') !== '' ? $f['diseno'] : 'Diseño sin nombre' }}
            @if (($f['cliente'] ?? '') !== '') · {{ $f['cliente'] }}@endif
            · {{ $r['cantidad'] }} unidades
          </div>
        </div>
      </div>
    @endif
  </div>

</div>

<p style="font-size:11.5px;color:var(--ink-muted);margin:0;">
  Los valores de costo base (densidad, respaldo, matriz, margen, descuentos) se ajustan en <code>config/cotizador.php</code>.
</p>
@endsection
