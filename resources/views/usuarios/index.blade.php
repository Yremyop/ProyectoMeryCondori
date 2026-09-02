@extends('layouts.app')
@section('titulo', 'Usuarios')
@section('nav', 'usuarios')
@section('sub', 'Administración de accesos al sistema (solo Administrador)')

@section('contenido')
<div class="grid grid-2">

  <div class="card card-flush">
    <div class="card-head">
      <h2>Usuarios del sistema</h2>
      <span class="chip">{{ $usuarios->count() }} usuario(s)</span>
    </div>
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Email</th>
            <th>Rol</th>
            <th>Estado</th>
            <th class="nowrap">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($usuarios as $u)
          @php $esActivo = $u->activo(); $esYo = $u->id === auth()->id(); @endphp
          <tr>
            <td>
              <strong>{{ $u->nombre }}</strong>
              @if ($esYo)<span class="muted" style="font-size:11.5px;"> · tú</span>@endif
            </td>
            <td class="muted">{{ $u->email }}</td>
            <td class="muted">{{ $u->rol }}</td>
            <td><span class="stock-badge {{ $esActivo ? 'stock-ok' : 'stock-low' }}">{{ $u->estado }}</span></td>
            <td class="nowrap">
              <div class="row-actions">
                <form method="POST" action="{{ route('usuarios.estado', $u) }}">
                  @csrf @method('PATCH')
                  <button type="submit" class="btn btn-outline btn-small{{ $esYo ? ' is-disabled' : '' }}"
                          @if ($esYo) disabled title="No puedes desactivar tu propio usuario" @endif
                          @if ($esActivo && !$esYo) data-confirm="¿Desactivar el acceso de {{ $u->nombre }}?" @endif>
                    {{ $esActivo ? 'Desactivar' : 'Activar' }}
                  </button>
                </form>
              </div>
            </td>
          </tr>
          @empty
          <tr class="table-empty"><td colspan="5">No hay usuarios registrados.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="modal-form">
    <h3>Nuevo usuario</h3>
    <form method="POST" action="{{ route('usuarios.store') }}">
      @csrf
      <div class="field"><label>Nombre</label><input type="text" name="nombre" value="{{ old('nombre') }}" required></div>
      <div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required></div>
      <div class="field">
        <label>Contraseña</label>
        <input type="password" name="password" minlength="8" required>
        <div class="hint">Mínimo 8 caracteres. Se guarda cifrada con bcrypt.</div>
      </div>
      <div class="field">
        <label>Rol</label>
        <select name="rol">
          <option value="Vendedor">Vendedor</option>
          <option value="Administrador">Administrador</option>
        </select>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-block">Crear usuario</button>
      </div>
    </form>
  </div>

</div>
@endsection
