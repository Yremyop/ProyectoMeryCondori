<?php

/**
 * Parámetros de costos del cotizador de parches.
 * Ajusta estos números a tus costos reales de producción
 * (antes vivían en $CONFIG al inicio de cotizador.php).
 */
return [
    // Costo en Bs por cm² según densidad de bordado
    'densidad' => ['Baja' => 0.08, 'Media' => 0.12, 'Alta' => 0.18],

    // Costo en Bs por unidad según tipo de respaldo/acabado
    'respaldo' => ['Para coser' => 0.00, 'Velcro' => 1.00, 'Adhesivo termosellable' => 0.60],

    'matriz_fija' => 50.00,      // Bs, cargo único por digitalización/diseño nuevo
    'margen' => 0.40,            // 40% de margen comercial sobre el costo
    'recargo_urgencia' => 0.25,  // 25% de recargo si el pedido es express
    'moneda' => 'Bs',

    // Descuento por volumen: cantidad mínima => descuento
    'volumen' => [1 => 0.00, 50 => 0.05, 100 => 0.10, 250 => 0.15, 500 => 0.20],
];
