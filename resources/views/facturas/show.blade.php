<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Factura Nº {{ $venta->id }} · Bordados T&amp;O</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}?v=7">
  <style>
    .hoja-wrap{min-height:100vh;background:var(--canvas);padding:28px 20px;}
    .hoja{
      max-width:780px;margin:0 auto;background:var(--surface);
      border:1px solid var(--line);border-radius:var(--r);padding:32px 34px;
    }
    .hoja-head{
      display:flex;justify-content:space-between;align-items:flex-start;gap:20px;flex-wrap:wrap;
      padding-bottom:20px;border-bottom:1px solid var(--line);
    }
    .hoja-marca{display:flex;align-items:center;gap:10px;color:var(--emerald);}
    .hoja-marca b{font-family:'Fraunces',Georgia,serif;font-size:17px;color:var(--ink);font-weight:600;}
    .hoja-meta{text-align:right;font-size:12.5px;color:var(--ink-soft);line-height:1.7;}
    .hoja-meta .n{font-family:'Fraunces',Georgia,serif;font-size:20px;color:var(--ink);font-weight:600;}
    .hoja-partes{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;padding:20px 0;}
    .hoja-parte .label{font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:var(--ink-muted);font-weight:600;margin-bottom:5px;}
    .hoja-parte .valor{font-size:13.5px;color:var(--ink);font-weight:600;}
    .hoja-parte .dato{font-size:12.5px;color:var(--ink-soft);}
    .hoja-total{
      display:flex;justify-content:flex-end;align-items:baseline;gap:18px;
      margin-top:16px;padding-top:16px;border-top:1px solid var(--line);
    }
    .hoja-total .label{font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:var(--ink-muted);font-weight:600;}
    .hoja-total .monto{font-family:'Fraunces',Georgia,serif;font-size:24px;font-weight:600;color:var(--ink);font-variant-numeric:tabular-nums;}
    .hoja-pie{margin-top:22px;font-size:12px;color:var(--ink-muted);}
    .hoja-acciones{display:flex;gap:8px;flex-wrap:wrap;margin-top:22px;padding-top:20px;border-top:1px solid var(--line-soft);}
    @media print{
      .hoja-wrap{padding:0;background:#FFFFFF;}
      .hoja{max-width:none;border:none;border-radius:0;padding:0;}
    }
    @media (max-width:560px){ .hoja{padding:22px 18px;} .hoja-partes{grid-template-columns:1fr;} }
  </style>
</head>
<body>
<div class="hoja-wrap">
  <div class="hoja">

    <div class="hoja-head">
      <div class="hoja-marca">
        {{ icono('hilo', 26) }}
        <div>
          <b>Bordados T&amp;O</b>
          <div style="font-size:10px;letter-spacing:.1em;text-transform:uppercase;color:var(--ink-muted);font-weight:600;">Gestión de ventas</div>
        </div>
      </div>
      <div class="hoja-meta">
        <div style="font-size:10px;letter-spacing:.08em;text-transform:uppercase;font-weight:600;">Factura de venta</div>
        <div class="n">Nº {{ $venta->id }}</div>
        <div>{{ fechaCorta($venta->fecha) }}</div>
      </div>
    </div>

    <div class="hoja-partes">
      <div class="hoja-parte">
        <div class="label">Cliente</div>
        <div class="valor">{{ $venta->cliente->nombre ?? 'Cliente' }}</div>
        @if ($venta->cliente?->telefono)
          <div class="dato">Tel. {{ $venta->cliente->telefono }}</div>
        @endif
      </div>
      <div class="hoja-parte">
        <div class="label">Atendido por</div>
        <div class="valor">{{ $venta->usuario->nombre ?? 'Sistema' }}</div>
      </div>
    </div>

    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>Producto</th>
            <th class="right">Cant.</th>
            <th class="right">P. unit.</th>
            <th class="right">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($venta->detalles as $row)
          <tr>
            <td>
              <strong>{{ $row->producto->nombre ?? 'Producto' }}</strong>
              @if ($row->producto?->codigo)
                <div style="font-size:11.5px;color:var(--ink-muted);">{{ $row->producto->codigo }}</div>
              @endif
            </td>
            <td class="right">{{ $row->cantidad }}</td>
            <td class="right">{{ dinero($row->precio_unitario) }}</td>
            <td class="right"><strong>{{ dinero($row->subtotal) }}</strong></td>
          </tr>
          @empty
          <tr class="table-empty"><td colspan="4">Esta venta no tiene detalle de productos.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="hoja-total">
      <span class="label">Total</span>
      <span class="monto">{{ dinero($venta->total) }}</span>
    </div>

    <div class="hoja-pie">Gracias por su compra.</div>

    <div class="hoja-acciones no-print">
      <a href="{{ route('ventas.index') }}" class="btn btn-outline">Volver a ventas</a>
      <button type="button" class="btn btn-outline" data-print>{{ icono('imprimir', 15) }} Imprimir</button>
      <a href="{{ route('facturas.pdf', $venta) }}" class="btn btn-primary">{{ icono('descargar', 15) }} Descargar PDF</a>
    </div>

  </div>
</div>

@if ($imprimir)<div data-autoprint hidden></div>@endif
<script src="{{ asset('js/app.js') }}?v=7" defer></script>
</body>
</html>
