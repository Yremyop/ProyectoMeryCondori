<?php

namespace Tests\Unit;

use App\Support\SimplePdf;
use PHPUnit\Framework\TestCase;

class SimplePdfTest extends TestCase
{
    public function test_genera_pdf_valido_de_varias_paginas(): void
    {
        $pdf = new SimplePdf();
        $pdf->texto(50, 700, 'Ñandú (prueba) \\ ok');
        $pdf->nuevaPagina();
        $pdf->texto(50, 700, 'Página 2');
        $salida = $pdf->salida();

        $this->assertStringStartsWith('%PDF-1.4', $salida);
        $this->assertStringContainsString('/Count 2', $salida);
        $this->assertStringEndsWith('%%EOF', $salida);
    }
}
