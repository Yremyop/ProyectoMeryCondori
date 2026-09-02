<?php

/** Parámetros de seguridad ajustables desde .env */
return [
    // Peticiones por minuto (por usuario autenticado o IP)
    'limite_peticiones' => (int) env('LIMITE_PETICIONES_MINUTO', 60),
    'limite_login' => (int) env('LIMITE_LOGIN_MINUTO', 7),

    // Bloqueo por fuerza bruta: intentos fallidos seguidos y duración del bloqueo
    'max_intentos_login' => (int) env('MAX_INTENTOS_LOGIN', 5),
    'bloqueo_segundos' => (int) env('BLOQUEO_LOGIN_SEGUNDOS', 600),
];
