@extends('layouts.app')
@section('titulo', 'Clientes')
@section('nav', 'clientes')
@section('sub', 'Registro y administración de clientes de la empresa')

@section('contenido')
<div class="clientes-grid">

  <div class="card card-flush">
    <div class="card-head">
      <h2>Listado de clientes</h2>
      <span class="chip">{{ $clientes->count() }} cliente(s)</span>
    </div>

    <div class="card-body" style="border-bottom:1px solid var(--line-soft);">
      <form method="GET" action="{{ route('clientes.index') }}" class="filters">
        <div class="field">
          <label>Buscar</label>
          <input type="text" name="buscar" placeholder="Nombre, carnet/NIT, teléfono o email…" value="{{ $buscar }}">
        </div>
        <div class="filters-actions">
          <button class="btn btn-primary" type="submit">Buscar</button>
          @if ($buscar !== '')
            <a href="{{ route('clientes.index') }}" class="btn btn-outline">Limpiar</a>
          @endif
        </div>
      </form>
      @if ($buscar !== '')
      <div class="filters-result">
        <strong>{{ $clientes->count() }}</strong> resultado(s) para “{{ $buscar }}”.
      </div>
      @endif
    </div>

    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Carnet/NIT</th>
            <th>Teléfono</th>
            <th>Email</th>
            <th class="nowrap">Registrado</th>
            <th class="nowrap">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($clientes as $c)
          <tr>
            <td><strong>{{ $c->nombre }}</strong></td>
            <td class="muted">{{ $c->documento ?: '—' }}</td>
            <td class="muted">{{ $c->telefono ?: '—' }}</td>
            <td class="muted">{{ $c->email ?: '—' }}</td>
            <td class="num muted nowrap">{{ fechaCorta($c->created_at) }}</td>
            <td class="nowrap">
              <div class="row-actions">
                <a class="btn btn-outline btn-small" href="{{ route('clientes.edit', $c) }}">Editar</a>
                @if (auth()->user()->esAdmin())
                <form method="POST" action="{{ route('clientes.destroy', $c) }}" style="display:inline">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-wine btn-small"
                          data-confirm="¿Eliminar a {{ $c->nombre }}? Esta acción no se puede deshacer.">Eliminar</button>
                </form>
                @endif
              </div>
            </td>
          </tr>
          @empty
          <tr class="table-empty">
            <td colspan="6">{{ $buscar !== '' ? 'No se encontraron clientes para esa búsqueda.' : 'Aún no hay clientes registrados.' }}</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="modal-form">
    <h3>{{ $editando ? 'Editar cliente' : 'Nuevo cliente' }}</h3>
    <form method="POST" action="{{ $editando ? route('clientes.update', $editando) : route('clientes.store') }}">
      @csrf
      @if ($editando) @method('PUT') @endif

      <div class="field">
        <label>Nombre completo</label>
        <input type="text" name="nombre" value="{{ old('nombre', $editando->nombre ?? '') }}" required>
      </div>

      <div class="field">
        <label>Carnet / NIT</label>
        <input type="text" name="documento" value="{{ old('documento', $editando->documento ?? '') }}" required>
      </div>

      <div class="field">
        <label>Teléfono</label>
        <input type="text" name="telefono" value="{{ old('telefono', $editando->telefono ?? '') }}">
      </div>

      <div class="field">
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email', $editando->email ?? '') }}">
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-block">{{ $editando ? 'Guardar cambios' : 'Registrar cliente' }}</button>
        @if ($editando)
          <a href="{{ route('clientes.index') }}" class="btn btn-outline btn-block">Cancelar edición</a>
        @endif
      </div>
    </form>
  </div>

</div>
@endsection
