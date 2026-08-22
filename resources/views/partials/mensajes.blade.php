@if (session('ok'))
<div class="alert alert-ok">{{ icono('check', 16) }}<span>{{ session('ok') }}</span></div>
@endif
@if (session('error'))
<div class="alert alert-error">{{ icono('alerta', 16) }}<span>{{ session('error') }}</span></div>
@endif
@if ($errors->any())
<div class="alert alert-error">{{ icono('alerta', 16) }}<span>@foreach ($errors->all() as $e){{ $e }}@if (!$loop->last)<br>@endif @endforeach</span></div>
@endif
