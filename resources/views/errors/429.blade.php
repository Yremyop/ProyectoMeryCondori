<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Demasiadas solicitudes · Bordados T&amp;O</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}?v=7">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-brand">
      <h1>Demasiadas solicitudes</h1>
      <p>{{ $exception->getMessage() ?: 'Espera un momento e inténtalo de nuevo.' }}</p>
    </div>
    <a class="btn btn-primary btn-block" href="{{ url('/') }}">Volver al panel</a>
  </div>
</div>
</body>
</html>
