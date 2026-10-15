<?php

namespace Tests\Unit;

use App\Support\Cotizador;
use PHPUnit\Framework\TestCase;

class CotizadorTest extends TestCase
{
    private function entrada(array $extra = []): array
    {
        return $extra + ['ancho' => 8, 'alto' => 6, 'cantidad' => 100, 'densidad' => 'Media', 'respaldo' => 'Velcro', 'diseno_nuevo' => 'Si', 'urgencia' => 'Normal'];
    }

    private function config(): array
    {
        return require __DIR__ . '/../../config/cotizador.php';
    }

    public function test_calculo_con_descuento_por_volumen_y_matriz(): void
    {
        $r = Cotizador::calcular($this->config(), $this->entrada());

        $this->assertEqualsWithDelta(48.0, $r['area'], 0.0001);
        $this->assertEqualsWithDelta(0.10, $r['descuento'], 0.0001);
        $this->assertEqualsWithDelta(8.5176, $r['precio_final_unit'], 0.0001);
        $this->assertEqualsWithDelta(851.76, $r['subtotal_produccion'], 0.001);
        $this->assertEqualsWithDelta(901.76, $r['total'], 0.001);
    }

    public function test_recargo_express_y_sin_matriz(): void
    {
        $r = Cotizador::calcular($this->config(), $this->entrada(['urgencia' => 'Express', 'diseno_nuevo' => 'No']));

        $this->assertEqualsWithDelta(10.647, $r['precio_final_unit'], 0.0001);
        $this->assertEqualsWithDelta(0.0, $r['costo_matriz'], 0.0001);
        $this->assertEqualsWithDelta(1064.7, $r['total'], 0.001);
    }

    public function test_tramos_de_volumen(): void
    {
        foreach ([1 => 0.0, 49 => 0.0, 50 => 0.05, 249 => 0.10, 250 => 0.15, 500 => 0.20, 5000 => 0.20] as $cantidad => $esperado) {
            $r = Cotizador::calcular($this->config(), $this->entrada(['cantidad' => $cantidad]));
            $this->assertEqualsWithDelta($esperado, $r['descuento'], 0.0001, "cantidad $cantidad");
        }
    }
}
