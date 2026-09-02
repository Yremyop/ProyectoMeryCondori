<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar sesión · Bordados T&amp;O</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/style.css') }}?v=7">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-brand">
      {{ icono('hilo', 34) }}
      <h1>Bordados T&amp;O</h1>
      <p>Sistema de Gestión de Ventas</p>
    </div>

    @if ($errors->any())
      <div class="alert alert-error">{{ icono('alerta', 16) }}<span>{{ $errors->first() }}</span></div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" autocomplete="off">
      @csrf
      <div class="field">
        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="admin@bordadosteo.com" required autofocus>
      </div>
      <div class="field">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg">Ingresar al sistema</button>
    </form>

    <div class="login-note">
      Tras {{ $maxIntentos }} intentos fallidos la cuenta se bloquea {{ $bloqueoMin }} minutos.
    </div>
  </div>
</div>
</body>
</html>
