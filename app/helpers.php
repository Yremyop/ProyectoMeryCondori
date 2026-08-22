<?php

use Illuminate\Support\HtmlString;

/** Iconos SVG de trazo (24x24), heredan el color del texto. */
if (!function_exists('icono')) {
    function icono(string $nombre, int $tam = 19): HtmlString
    {
        static $paths = null;
        if ($paths === null) {
            $paths = [
            'dashboard'  => '<rect x="3" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.5"/>',
            'clientes'   => '<path d="M15.5 19.5v-1.2a3.4 3.4 0 0 0-3.4-3.4H7.4A3.4 3.4 0 0 0 4 18.3v1.2"/><circle cx="9.75" cy="8" r="3.5"/><path d="M16.5 11.3a3.3 3.3 0 0 0 0-6.6"/><path d="M20 19.5v-1.2a3.2 3.2 0 0 0-2.4-3.1"/>',
            'catalogo'   => '<path d="M20.5 12.4 12.4 20.5 3.5 11.6V4.5a1 1 0 0 1 1-1h7.1z"/><circle cx="8" cy="8" r="1.3"/>',
            'inventario' => '<path d="M20.5 7.6 12 3.5 3.5 7.6v8.8L12 20.5l8.5-4.1z"/><path d="M3.5 7.6 12 11.7l8.5-4.1"/><path d="M12 11.7v8.8"/>',
            'ventas'     => '<path d="M6.5 3.5h11v17l-2.75-1.6-2.75 1.6-2.75-1.6L6.5 20.5z"/><path d="M9.5 8.5h5M9.5 12.5h5"/>',
            'pedidos'    => '<rect x="5" y="5" width="14" height="15.5" rx="2"/><path d="M9.25 3.5h5.5v3h-5.5z"/><path d="M9 11.5h6M9 15.5h4"/>',
            'reportes'   => '<path d="M4 3.5v17h16"/><path d="M7.75 16.5v-4.5M12 16.5v-8M16.25 16.5v-6"/>',
            'cotizador'  => '<rect x="5" y="3" width="14" height="18" rx="2"/><rect x="8.25" y="6.5" width="7.5" height="3" rx="1"/><path d="M8.5 13.5h.01M12 13.5h.01M15.5 13.5h.01M8.5 17h.01M12 17h.01M15.5 17h.01"/>',
            'usuarios'   => '<circle cx="10" cy="7.75" r="3.5"/><path d="M3.75 19v-1.2a3.4 3.4 0 0 1 3.4-3.4h4.6"/><circle cx="17.25" cy="17.25" r="2.5"/><path d="M17.25 13.5v.9M17.25 20.1v.9M14.1 15.5l.8.45M19.6 18.6l.8.45M14.1 19l.8-.45M19.6 15.9l.8-.45"/>',
            'salir'      => '<path d="M14.5 3.5h3a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2h-3"/><path d="M10 16.5 14.5 12 10 7.5"/><path d="M14.5 12H4"/>',
            'hilo'       => '<path d="M2 13h4l2.4-6 3.2 12 3.2-9 2.2 3h5"/>',
            'buscar'     => '<circle cx="10.75" cy="10.75" r="6.25"/><path d="M15.5 15.5 20 20"/>',
            'mas'        => '<path d="M12 5v14M5 12h14"/>',
            'menu'       => '<path d="M4 7h16M4 12h16M4 17h16"/>',
            'alerta'     => '<path d="M12 4 2.5 20.5h19z"/><path d="M12 10v4M12 17.5h.01"/>',
            'reloj'      => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
            'sobre'      => '<rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="M3.5 7 12 13l8.5-6"/>',
            'check'      => '<circle cx="12" cy="12" r="8.5"/><path d="M8.5 12.2l2.4 2.4 4.6-4.8"/>',
            'flecha'     => '<path d="M4.5 12h15"/><path d="M13.5 6 19.5 12l-6 6"/>',
            'descargar'  => '<path d="M12 15.5V4"/><path d="M7.5 11 12 15.5 16.5 11"/><path d="M4.5 19.5h15"/>',
            'imprimir'   => '<path d="M7 9V3.5h10V9"/><rect x="3.5" y="9" width="17" height="7" rx="2"/><path d="M7 14h10v6.5H7z"/>',
            'documento'  => '<path d="M13.5 3.5H7a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9z"/><path d="M13.5 3.5V9H19"/><path d="M9 13h6M9 16.5h4"/>',
            'caja'       => '<path d="M20.5 7.6 12 3.5 3.5 7.6v8.8L12 20.5l8.5-4.1z"/><path d="M3.5 7.6 12 11.7l8.5-4.1"/>',
            'cerrar'     => '<path d="M6.5 6.5l11 11M17.5 6.5l-11 11"/>',
            'vacio'      => '<path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5z"/><path d="M4 8.5 12 13l8-4.5M12 13v7"/>',
        ];        }

        if (!isset($paths[$nombre])) {
            return new HtmlString('');
        }

        return new HtmlString('<svg width="' . $tam . '" height="' . $tam . '" viewBox="0 0 24 24" fill="none" '
            . 'stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" '
            . 'aria-hidden="true" focusable="false">' . $paths[$nombre] . '</svg>');
    }
}

/** Importe en bolivianos con separador de miles. */
if (!function_exists('dinero')) {
    function dinero($monto): string
    {
        return 'Bs ' . number_format((float) $monto, 2);
    }
}

/** Iniciales para el avatar de la barra superior. */
if (!function_exists('iniciales')) {
    function iniciales(?string $nombre): string
    {
        $texto = '';
        foreach (preg_split('/\s+/', trim((string) $nombre)) as $parte) {
            if ($parte !== '') {
                $texto .= mb_strtoupper(mb_substr($parte, 0, 1));
            }
            if (mb_strlen($texto) >= 2) {
                break;
            }
        }
        return $texto !== '' ? $texto : '?';
    }
}

/** Fecha corta legible (2026-08-19 -> 19 ago 2026). */
if (!function_exists('fechaCorta')) {
    function fechaCorta($fecha): string
    {
        if (!$fecha) {
            return '—';
        }
        $c = $fecha instanceof \DateTimeInterface ? \Carbon\Carbon::instance($fecha) : \Carbon\Carbon::parse($fecha);
        $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        return $c->day . ' ' . $meses[$c->month - 1] . ' ' . $c->year;
    }
}

/** Importe con moneda configurable (cotizador). */
if (!function_exists('bs')) {
    function bs($monto, string $moneda = 'Bs'): string
    {
        return $moneda . ' ' . number_format((float) $monto, 2);
    }
}

/** Importe con la moneda del cotizador (config/cotizador.php). */
if (!function_exists('bs')) {
    function bs($monto, string $moneda = 'Bs'): string
    {
        return $moneda . ' ' . number_format((float) $monto, 2, '.', ',');
    }
}
