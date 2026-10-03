<?php

namespace App\Support;

/** Cálculo del precio de una cotización de parches bordados (calcular_cotizacion() del original). */
class Cotizador
{
    public static function calcular(array $c, array $input): array
    {
        $ancho = (float) $input['ancho'];
        $alto = (float) $input['alto'];
        $cantidad = max(1, (int) $input['cantidad']);
        $disenoNuevo = ($input['diseno_nuevo'] ?? 'No') === 'Si';
        $urgente = ($input['urgencia'] ?? 'Normal') === 'Express';

        $area = $ancho * $alto;
        $costoProdUnit = $area * ($c['densidad'][$input['densidad']] ?? 0);
        $costoRespUnit = $c['respaldo'][$input['respaldo']] ?? 0;
        $subtotalUnit = $costoProdUnit + $costoRespUnit;
        $precioMargen = $subtotalUnit * (1 + $c['margen']);

        // Descuento por volumen: el tramo más alto que la cantidad todavía cumple.
        $descuento = 0.0;
        foreach ($c['volumen'] as $minCantidad => $desc) {
            if ($cantidad >= $minCantidad) {
                $descuento = $desc;
            }
        }

        $precioConDescuento = $precioMargen * (1 - $descuento);
        $recargo = $urgente ? $precioConDescuento * $c['recargo_urgencia'] : 0.0;
        $precioFinalUnit = $precioConDescuento + $recargo;
        $subtotalProduccion = $precioFinalUnit * $cantidad;
        $costoMatriz = $disenoNuevo ? $c['matriz_fija'] : 0.0;

        return [
            'area' => $area,
            'costo_prod_unit' => $costoProdUnit,
            'costo_resp_unit' => $costoRespUnit,
            'subtotal_unit' => $subtotalUnit,
            'precio_margen' => $precioMargen,
            'descuento' => $descuento,
            'precio_con_descuento' => $precioConDescuento,
            'recargo_urgencia' => $recargo,
            'precio_final_unit' => $precioFinalUnit,
            'subtotal_produccion' => $subtotalProduccion,
            'costo_matriz' => $costoMatriz,
            'total' => $subtotalProduccion + $costoMatriz,
            'cantidad' => $cantidad,
        ];
    }
}
