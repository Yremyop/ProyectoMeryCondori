<?php

namespace App\Support;

use App\Models\Venta;
use Illuminate\Support\Collection;

class Pdfs
{
    private static function bs($n): string
    {
        return 'Bs ' . number_format((float) $n, 2, '.', ',');
    }

    public static function factura(Venta $venta): string
    {
        $venta->loadMissing(['cliente', 'usuario', 'detalles.producto']);
        $pdf = new SimplePdf();

        $pdf->texto(50, 760, 'Factura de venta', 16);
        $pdf->texto(50, 740, 'Nº: #' . $venta->id);
        $pdf->texto(50, 725, 'Fecha: ' . $venta->fecha->format('Y-m-d'));
        $pdf->texto(380, 740, 'Bordados T&O');
        $pdf->texto(380, 725, 'Atendido por: ' . ($venta->usuario->nombre ?? 'Sistema'));
        $pdf->texto(50, 690, 'Cliente: ' . ($venta->cliente->nombre ?? 'Cliente'));
        if ($venta->cliente?->telefono) {
            $pdf->texto(50, 675, 'Tel: ' . $venta->cliente->telefono);
        }

        $encabezado = function () use ($pdf, &$y) {
            $pdf->linea(50, $y + 22, 560, $y + 22);
            $pdf->texto(50, $y + 8, 'Producto');
            $pdf->texto(230, $y + 8, 'Cant.');
            $pdf->texto(320, $y + 8, 'P. unit.');
            $pdf->texto(470, $y + 8, 'Subtotal');
            $pdf->linea(50, $y - 4, 560, $y - 4);
        };

        $y = 640;
        $encabezado();
        $y -= 24;

        foreach ($venta->detalles as $d) {
            if ($y < 110) {
                $pdf->nuevaPagina();
                $y = 740;
                $encabezado();
                $y -= 24;
            }
            $pdf->texto(50, $y, mb_substr($d->producto->nombre ?? 'Producto', 0, 48));
            $pdf->texto(230, $y, (string) $d->cantidad);
            $pdf->texto(320, $y, self::bs($d->precio_unitario));
            $pdf->texto(470, $y, self::bs($d->subtotal));
            $y -= 16;
        }

        $pdf->linea(50, $y - 6, 560, $y - 6);
        $pdf->texto(430, $y - 24, 'Total: ' . self::bs($venta->total), 12);
        $pdf->texto(50, $y - 24, 'Gracias por su compra.');

        return $pdf->salida();
    }

    /** @param Collection $ventas ventas con cliente y detalles.producto cargados */
    public static function historial(Collection $ventas): string
    {
        $pdf = new SimplePdf();
        $cabecera = function () use ($pdf) {
            $pdf->texto(50, 790, 'Historial de ventas', 16);
            $pdf->texto(50, 770, 'Generado: ' . now()->format('d/m/Y H:i'));
            $pdf->linea(50, 750, 560, 750);
        };
        $cabecera();

        $y = 725;
        foreach ($ventas as $v) {
            if ($y < 70) {
                $pdf->nuevaPagina();
                $cabecera();
                $y = 725;
            }
            $items = $v->detalles->map(fn ($d) => $d->cantidad . 'x ' . ($d->producto->nombre ?? ''))->implode(', ');
            $linea = $v->id . ' | ' . ($v->cliente->nombre ?? '') . ' | ' . $v->fecha->format('Y-m-d') . ' | ' . $items;
            $pdf->texto(50, $y, mb_substr($linea, 0, 100), 8);
            $pdf->texto(470, $y, self::bs($v->total), 8);
            $y -= 15;
        }

        $pdf->linea(50, 60, 560, 60);
        $pdf->texto(50, 40, 'Total de ventas registradas: ' . $ventas->count());

        return $pdf->salida();
    }
}
