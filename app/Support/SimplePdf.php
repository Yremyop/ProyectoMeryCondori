<?php

namespace App\Support;

/**
 * Generador mínimo de PDF (Helvetica, texto y líneas) sin dependencias.
 * Es el mismo enfoque del sistema original, ahora con varias páginas
 * y codificación WinAnsi para tildes y ñ.
 */
class SimplePdf
{
    /** @var string[] contenido de cada página */
    private array $paginas = [];
    private string $actual = '';

    public function __construct()
    {
        $this->nuevaPagina();
    }

    public function nuevaPagina(): void
    {
        if ($this->paginas !== [] || $this->actual !== '') {
            $this->paginas[] = $this->actual;
        }
        $this->actual = '';
    }

    public function texto(float $x, float $y, string $texto, int $tam = 10): void
    {
        $this->actual .= "BT /F1 $tam Tf $x $y Td (" . $this->escapar($texto) . ") Tj ET\n";
    }

    public function linea(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->actual .= "$x1 $y1 m $x2 $y2 l S\n";
    }

    private function escapar(string $t): string
    {
        $t = trim($t);
        if (function_exists('iconv')) {
            $c = @iconv('UTF-8', 'windows-1252//TRANSLIT', $t);
            if ($c !== false) {
                $t = $c;
            }
        }
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $t);
    }

    public function salida(): string
    {
        $paginas = array_merge($this->paginas, [$this->actual]);
        $n = count($paginas);

        // Objetos: 1 catálogo, 2 páginas, 3 fuente, luego (página, contenido) por cada página.
        $kids = [];
        for ($i = 0; $i < $n; $i++) {
            $kids[] = (4 + $i * 2) . ' 0 R';
        }

        $objs = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $n . ' >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
        ];
        foreach ($paginas as $i => $contenido) {
            $idContenido = 5 + $i * 2;
            $objs[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents $idContenido 0 R /Resources << /Font << /F1 3 0 R >> >> >>";
            $objs[] = '<< /Length ' . strlen($contenido) . " >>\nstream\n" . $contenido . "\nendstream";
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objs as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }
        $pdf .= "trailer\n<< /Size " . (count($objs) + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";

        return $pdf;
    }
}
