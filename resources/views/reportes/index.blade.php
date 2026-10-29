@extends('layouts.app')
@section('titulo', 'Reportes')
@section('nav', 'reportes')
@section('sub', 'Filtra por fecha, cliente, producto, categoría y color')

@section('contenido')
<form method="get" action="{{ route('reportes.index') }}" class="card report-toolbar no-print">
  <div class="report-filters">
    <div class="field"><label>Desde</label><input type="date" name="desde" value="{{ $desde }}"></div>
    <div class="field"><label>Hasta</label><input type="date" name="hasta" value="{{ $hasta }}"></div>
    <div class="field">
      <label>Periodo</label>
      <select name="periodo">
        <option value="">Todos</option>
        <option value="diario" {{ ($periodo === 'diario') ? 'selected' : '' }}>Diario</option>
        <option value="semanal" {{ ($periodo === 'semanal') ? 'selected' : '' }}>Semanal</option>
        <option value="mensual" {{ ($periodo === 'mensual') ? 'selected' : '' }}>Mensual</option>
      </select>
    </div>
    <div class="field">
      <label>Cliente</label>
      <select name="cliente">
        <option value="">Todos los clientes</option>
        @foreach ($clientes as $cliente)
          <option value="{{ $cliente->id }}" {{ $clienteId === $cliente->id ? 'selected' : '' }}>{{ $cliente->nombre }}</option>
        @endforeach
      </select>
    </div>
    <div class="field">
      <label>Producto</label>
      <select name="producto">
        <option value="">Todos los productos</option>
        @foreach ($productos as $producto)
          <option value="{{ $producto->id }}" {{ $productoId === $producto->id ? 'selected' : '' }}>{{ $producto->nombre }}</option>
        @endforeach
      </select>
    </div>
    <div class="field">
      <label>Categoría</label>
      <select name="categoria">
        <option value="">Todas las categorías</option>
        @foreach ($categorias as $categoria)
          <option value="{{ $categoria }}" {{ ($categoriaFiltro === $categoria) ? 'selected' : '' }}>{{ $categoria }}</option>
        @endforeach
      </select>
    </div>
    <div class="field">
      <label>Color</label>
      <select name="color">
        <option value="">Todos los colores</option>
        @foreach ($colores as $color)
          <option value="{{ $color }}" {{ ($colorFiltro === $color) ? 'selected' : '' }}>{{ $color }}</option>
        @endforeach
      </select>
    </div>
  </div>

  <div class="report-actions">
    <button type="submit" class="btn btn-primary">Generar reporte</button>
    <button type="button" class="btn btn-outline" data-print>{{ icono('imprimir', 15) }} Imprimir</button>
    @if (!$esPdf)
      <a class="btn btn-outline" href="{{ $pdfLink }}">{{ icono('descargar', 15) }} Descargar PDF</a>
    @endif
    <a href="{{ route('reportes.index') }}" class="btn btn-outline">Limpiar filtros</a>
  </div>
</form>

<div class="grid grid-4">
  <div class="stat-card">
    <div class="label">Ingresos del rango</div>
    <div class="value">{{ dinero($ingresos) }}</div>
  </div>
  <div class="stat-card">
    <div class="label">Nº de ventas</div>
    <div class="value">{{ $numVentas }}</div>
  </div>
  <div class="stat-card">
    <div class="label">Producto más vendido</div>
    <div class="value value-sm" style="font-size:15px;line-height:1.35;">{{ $topProducto }}</div>
  </div>
  <div class="stat-card">
    <div class="label">Pedidos pendientes</div>
    <div class="value">{{ $pendientes }}</div>
  </div>
</div>

@if ($periodo && $ventasPeriodo->isNotEmpty())
<div class="card card-flush">
  <div class="card-head"><h2>Resumen {{ ucfirst($periodo) }}</h2></div>
  <div class="table-responsive">
    <table>
      <thead><tr><th>Periodo</th><th class="right">Ventas</th><th class="right">Total</th></tr></thead>
      <tbody>
        @foreach ($ventasPeriodo as $row)
        <tr>
          <td><strong>{{ $row['periodo'] }}</strong></td>
          <td class="right">{{ $row['ventas'] }}</td>
          <td class="right">{{ dinero($row['total']) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

<div class="card">
  <div class="section-head">
    <h2>Ventas por producto</h2>
    @if ($porProducto->isNotEmpty())<span class="sub">cantidad de unidades vendidas</span>@endif
  </div>
  @if ($porProducto->isNotEmpty())
    <div class="bars">
      @foreach ($porProducto as $row)
        <div class="bar-col">
          <div class="bar-value">{{ $row['cantidad'] }}</div>
          <div class="bar" style="height:{{ max(6, round(($row['cantidad'] / max(1, $maxCantidad)) * 130)) }}px;"></div>
          <div class="bar-label">{{ $row['nombre'] }}</div>
        </div>
      @endforeach
    </div>
  @else
    <div class="empty">{{ icono('reportes', 28) }}Sin datos para el rango seleccionado.</div>
  @endif
</div>

<div class="grid grid-2">
  <div class="card card-flush">
    <div class="card-head"><h2>Top clientes</h2></div>
    <div class="table-responsive">
      <table>
        <thead><tr><th>Cliente</th><th class="right">Ventas</th><th class="right">Total</th></tr></thead>
        <tbody>
          @forelse ($porCliente as $row)
          <tr>
            <td><strong>{{ $row['nombre'] }}</strong></td>
            <td class="right">{{ $row['ventas'] }}</td>
            <td class="right">{{ dinero($row['total']) }}</td>
          </tr>
          @empty
          <tr class="table-empty"><td colspan="3">No hay clientes en el rango seleccionado.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="card card-flush">
    <div class="card-head"><h2>Ventas por categoría</h2></div>
    <div class="table-responsive">
      <table>
        <thead><tr><th>Categoría</th><th class="right">Cantidad</th><th class="right">Total</th></tr></thead>
        <tbody>
          @forelse ($porCategoria as $row)
          <tr>
            <td><strong>{{ $row['nombre'] }}</strong></td>
            <td class="right">{{ $row['cantidad'] }}</td>
            <td class="right">{{ dinero($row['ingresos']) }}</td>
          </tr>
          @empty
          <tr class="table-empty"><td colspan="3">No hay categorías para el rango seleccionado.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card card-flush">
  <div class="card-head"><h2>Ventas por color</h2></div>
  <div class="table-responsive">
    <table>
      <thead><tr><th>Color</th><th class="right">Cantidad</th><th class="right">Total</th></tr></thead>
      <tbody>
        @forelse ($porColor as $row)
        <tr>
          <td>
            <span class="color-badge">
              <span class="color-dot color-{{ $row['nombre'] }}"></span>
              {{ $row['nombre'] }}
            </span>
          </td>
          <td class="right">{{ $row['cantidad'] }}</td>
          <td class="right">{{ dinero($row['ingresos']) }}</td>
        </tr>
        @empty
        <tr class="table-empty"><td colspan="3">No hay colores para el rango seleccionado.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="card card-flush">
  <div class="card-head">
    <h2>Detalle de ventas filtradas</h2>
    <span class="chip">{{ $detalle->count() }} línea(s)</span>
  </div>
  <div class="table-responsive">
    <table>
      <thead>
        <tr>
          <th class="nowrap">Fecha</th><th>Nº venta</th><th>Cliente</th><th>Producto</th><th>Categoría</th><th>Color</th>
          <th class="right">Cant.</th><th class="right">P. unit.</th><th class="right">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($detalle as $d)
        <tr>
          <td class="num muted nowrap">{{ fechaCorta($d->venta->fecha) }}</td>
          <td class="num muted">{{ $d->venta_id }}</td>
          <td><strong>{{ $d->venta->cliente->nombre ?? '—' }}</strong></td>
          <td>{{ $d->producto->nombre ?? '—' }}</td>
          <td class="muted">{{ $d->producto->categoria ?? '—' }}</td>
          <td class="muted">{{ $d->producto->color ?? '—' }}</td>
          <td class="right">{{ $d->cantidad }}</td>
          <td class="right">{{ dinero($d->precio_unitario) }}</td>
          <td class="right">{{ dinero($d->subtotal) }}</td>
        </tr>
        @empty
        <tr class="table-empty"><td colspan="9">No hay ventas con los filtros seleccionados.</td></tr>
        @endforelse
      </tbody>
      @if ($detalle->isNotEmpty())
      <tfoot>
        <tr>
          <td colspan="6"><strong>Total</strong></td>
          <td class="right"><strong>{{ $detalle->sum('cantidad') }}</strong></td>
          <td></td>
          <td class="right"><strong>{{ dinero($detalle->sum('subtotal')) }}</strong></td>
        </tr>
      </tfoot>
      @endif
    </table>
  </div>
</div>

@if ($esPdf)<div data-autoprint hidden></div>@endif
@endsection
