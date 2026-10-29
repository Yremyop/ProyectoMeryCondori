@extends('layouts.app')
@section('titulo', 'Inventario')
@section('nav', 'inventario')
@section('sub', 'Control de stock de productos e insumos')

@section('contenido')
@php
    $categoriasCatalogo = \App\Models\Producto::CATEGORIAS;
@endphp
<div class="card">
  <form method="GET" action="{{ route('inventario.index') }}" class="filters">
    <div class="field">
      <label>Buscar producto</label>
      <input type="text" name="buscar" placeholder="Código o nombre…" value="{{ $buscar }}">
    </div>
    <div class="field">
      <label>Categoría</label>
      <select name="categoria">
        <option value="">Todas las categorías</option>
        @foreach ($categoriasCatalogo as $cat)
        <option value="{{ $cat }}" {{ ($categoriaFiltro === $cat) ? 'selected' : '' }}>{{ $cat }}</option>
        @endforeach
      </select>
    </div>
    <div class="field">
      <label>Color</label>
      <select name="color">
        <option value="">Todos los colores</option>
        @foreach ($colores as $clr)
        <option value="{{ $clr }}" {{ ($colorFiltro === $clr) ? 'selected' : '' }}>{{ $clr }}</option>
        @endforeach
      </select>
    </div>
    <div class="field">
      <label>Ordenar</label>
      <select name="orden">
        <option value="asc" {{ ($orden !== 'desc') ? 'selected' : '' }}>Nombre A–Z</option>
        <option value="desc" {{ ($orden === 'desc') ? 'selected' : '' }}>Nombre Z–A</option>
      </select>
    </div>
    <div class="filters-actions">
      <button type="submit" class="btn btn-primary">Filtrar</button>
      <a href="{{ route('inventario.index') }}" class="btn btn-outline">Limpiar</a>
    </div>
  </form>
  @if ($hayFiltros)
  <div class="filters-result">
    <strong>{{ $productos->count() }}</strong> producto(s) con los filtros aplicados.
  </div>
  @endif
</div>

<div class="card card-flush">
  <div class="card-head">
    <h2>Existencias</h2>
    <span class="chip">{{ $productos->count() }} producto(s)</span>
  </div>
  <div class="table-responsive">
  <table>
    <thead>
      <tr>
        <th>Código</th>
        <th>Nombre</th>
        <th>Categoría</th>
        <th>Color</th>
        <th class="right">Precio unit.</th>
        <th class="right">Stock</th>
        <th class="right">Mínimo</th>
        <th>Estado</th>
        @if (auth()->user()->esAdmin())<th class="nowrap">Modificar stock y precio</th>@endif
      </tr>
    </thead>
    <tbody>
      @forelse ($productos as $p)
      <tr>
        <td class="muted">{{ $p->codigo ?: '—' }}</td>
        <td><strong>{{ $p->nombre }}</strong></td>
        <td class="muted">{{ $p->categoria ?? 'Otros' }}</td>
        <td>
          <span class="color-badge">
            <span class="color-dot color-{{ $p->color ?? 'Otro' }}"></span>
            {{ $p->color ?? 'Otro' }}
          </span>
        </td>
        <td class="right">{{ dinero($p->precio) }}</td>
        <td class="right"><strong>{{ $p->stock }}</strong></td>
        <td class="right muted">{{ $p->stock_minimo }}</td>
        <td>
          <span class="stock-badge {{ $p->stock_bajo || $p->stock === 0 ? 'stock-low' : 'stock-ok' }}">{{ $p->stock === 0 ? 'Sin stock' : ($p->stock_bajo ? 'Bajo' : 'OK') }}</span>
        </td>
        @if (auth()->user()->esAdmin())
        <td class="nowrap">
          <form method="POST" action="{{ route('inventario.ajustar', $p) }}" class="row-actions">
            @csrf
            <input type="number" name="stock" value="{{ $p->stock }}" min="0" required aria-label="Stock de {{ $p->nombre }}"
                   style="width:76px;height:var(--h-sm);text-align:right;">
            <input type="number" name="precio" value="{{ $p->precio }}" min="0" step="0.01" required aria-label="Precio de {{ $p->nombre }}"
                   style="width:92px;height:var(--h-sm);text-align:right;">
            <button type="submit" class="btn btn-outline btn-small">Guardar</button>
          </form>
        </td>
        @endif
      </tr>
      @empty
      <tr class="table-empty"><td colspan="{{ auth()->user()->esAdmin() ? 9 : 8 }}">No hay productos que coincidan con los filtros.</td></tr>
      @endforelse
    </tbody>
  </table>
  </div>
</div>
@endsection
