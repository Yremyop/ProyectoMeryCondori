<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Support\Pdfs;
use Illuminate\Http\Request;

class FacturaController extends Controller
{
    public function show(Request $request, Venta $venta)
    {
        $venta->load(['cliente', 'usuario', 'detalles.producto']);

        return view('facturas.show', ['venta' => $venta, 'imprimir' => $request->boolean('print')]);
    }

    public function pdf(Venta $venta)
    {
        return response(Pdfs::factura($venta), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="factura_' . $venta->id . '.pdf"',
        ]);
    }
}
