@extends('layouts.app')
@section('titulo', 'Catálogo')
@section('nav', 'catalogo')
@section('sub', 'Administración de productos bordados')

@section('contenido')
@php
    $categoriasCatalogo = \App\Models\Producto::CATEGORIAS;
    $coloresCatalogo = \App\Models\Producto::COLORES;
    $esAdmin = auth()->user()->esAdmin();
@endphp


<div class="{{ $esAdmin ? 'grid grid-2' : '' }}">

  <div class="stack">

    <div class="card">
      <form method="GET" action="{{ route('productos.index') }}" class="filters">
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
          <a href="{{ route('productos.index') }}" class="btn btn-outline">Limpiar</a>
        </div>
      </form>
      @if ($hayFiltros)
      <div class="filters-result"><strong>{{ $productos->count() }}</strong> producto(s) con los filtros aplicados.</div>
      @endif
    </div>

    <div class="card card-flush">
      <div class="card-head">
        <h2>Productos</h2>
        <span class="chip">{{ $productos->count() }} activo(s)</span>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th class="nowrap">Imagen</th>
              <th>Código</th>
              <th>Nombre</th>
              <th>Categoría</th>
              <th>Color</th>
              <th class="right">Stock</th>
              @if ($esAdmin)<th class="nowrap">Acciones</th>@endif
            </tr>
          </thead>
          <tbody>
          @forelse ($productos as $p)
            <tr>
              <td>
                <img src="{{ $p->imagen_url }}"
                     alt="{{ $p->nombre }}"
                     style="width:44px;height:44px;object-fit:cover;border-radius:var(--r-sm);border:1px solid var(--line-soft);">
              </td>
              <td class="muted">{{ $p->codigo ?: '—' }}</td>
              <td><strong>{{ $p->nombre }}</strong></td>
              <td class="muted">{{ $p->categoria ?? 'Otros' }}</td>
              <td>
                <span class="color-badge">
                  <span class="color-dot color-{{ $p->color ?? 'Otro' }}"></span>
                  {{ $p->color ?? 'Otro' }}
                </span>
              </td>
              <td class="right">
                <span class="stock-badge {{ $p->stock_bajo ? 'stock-low' : 'stock-ok' }}">{{ $p->stock }} u.</span>
              </td>
              @if ($esAdmin)
              <td class="nowrap">
                <div class="row-actions">
                  <a class="btn btn-outline btn-small" href="{{ route('productos.edit', $p) }}">Editar</a>
                  <form method="POST" action="{{ route('productos.destroy', $p) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-wine btn-small"
                            data-confirm="¿Desactivar “{{ $p->nombre }}” del catálogo?">Desactivar</button>
                  </form>
                </div>
              </td>
              @endif
            </tr>
          @empty
            <tr class="table-empty"><td colspan="{{ $esAdmin ? 7 : 6 }}">No hay productos que coincidan con los filtros.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

  </div>

  @if ($esAdmin)
  <div class="modal-form">
    <h3>{{ $editando ? 'Editar producto' : 'Nuevo producto' }}</h3>
    <form method="POST" action="{{ $editando ? route('productos.update', $editando) : route('productos.store') }}" enctype="multipart/form-data">
      @csrf
      @if ($editando) @method('PUT') @endif

      <div class="field">
        <label>Código</label>
        @if ($editando)
          <input type="text" value="{{ $editando->codigo }}" disabled>
        @else
          <div class="hint">Se genera automáticamente según la categoría y el color (ej. EAROJ001).</div>
        @endif
      </div>

      <div class="field">
        <label>Nombre del producto</label>
        <input type="text" name="nombre" value="{{ old('nombre', $editando->nombre ?? '') }}" placeholder="Ej. Camisa Aventureros" required>
      </div>

      <div class="field">
        <label>Categoría</label>
        <select name="categoria">
          @foreach ($categoriasCatalogo as $cat)
          <option value="{{ $cat }}" {{ (old('categoria', $editando->categoria ?? '') === $cat) ? 'selected' : '' }}>{{ $cat }}</option>
          @endforeach
        </select>
      </div>

      <div class="field">
        <label>Color</label>
        <select name="color">
          @foreach ($coloresCatalogo as $clr)
          <option value="{{ $clr }}" {{ (old('color', $editando->color ?? '') === $clr) ? 'selected' : '' }}>{{ $clr }}</option>
          @endforeach
        </select>
        <div class="hint">El precio lo asigna el administrador en Inventario.</div>
      </div>

      <div class="form-row">
        <div class="field">
          <label>Stock {{ $editando ? 'actual' : 'inicial' }}</label>
          <input type="number" name="stock" min="0" value="{{ old('stock', $editando->stock ?? 0) }}" required>
        </div>
        <div class="field">
          <label>Stock mínimo (alerta)</label>
          <input type="number" name="stock_minimo" min="0" value="{{ old('stock_minimo', $editando->stock_minimo ?? 5) }}" required>
        </div>
      </div>

      <div class="field">
        <label>Imagen del producto</label>
        <input type="file" name="imagen" accept=".jpg,.jpeg,.png,.webp">
        @if ($editando && $editando->imagen)
          <img src="{{ $editando->imagen_url }}" alt="{{ old('nombre', $editando->nombre ?? '') }}"
               style="width:88px;height:88px;object-fit:cover;border-radius:var(--r-sm);border:1px solid var(--line-soft);margin-top:8px;">
        @endif
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-block">{{ $editando ? 'Guardar cambios' : 'Agregar producto' }}</button>
        @if ($editando)
          <a href="{{ route('productos.index') }}" class="btn btn-outline btn-block">Cancelar edición</a>
        @endif
      </div>
    </form>
  </div>
  @endif

</div>
@endsection
