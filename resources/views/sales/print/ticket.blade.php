<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Ticket {{ $sale->num_factura ?? $sale->id }}</title>

  <style>
    @page { size: 80mm auto; margin: 0; }
    html, body { margin:0; padding:0; }
    body { width: 80mm; font-family: Arial, sans-serif; font-size: 12px; color:#000; }
    .wrap { padding: 10px 8px; }
    .center { text-align:center; }
    .bold { font-weight: 700; }
    .hr { border-top: 1px dashed #000; margin: 8px 0; }
    .row { display:flex; justify-content:space-between; gap:10px; }
    .totals { margin-left:auto; width: 62%; }
    .totals .row > div:first-child { flex:1; text-align:right; }
    .small { font-size: 10px; }
    table { width:100%; border-collapse:collapse; }
    th, td { padding: 3px 0; vertical-align:top; }
    th { font-size:10px; text-transform:uppercase; border-bottom: 1px solid #000; }
    .right { text-align:right; }
    .muted { color:#111; opacity:.9; }
    .note { font-size:10px; line-height:1.35; }
    @media print { .no-print { display:none !important; } }
  </style>
</head>
<body>
@php
  $clientNombre = $sale->client->nombre ?? $sale->cliente_nombre ?? 'Consumidor final';
  $clientIdent  = $sale->client->identificacion ?? $sale->cliente_identificacion ?? '9999999999';
  $clientTel    = $sale->client->telefono ?? $sale->cliente_telefono ?? '-';
  $clientDir    = $sale->client->direccion ?? $sale->cliente_direccion ?? '-';

  $clientEmail  =
      $sale->email_destino
      ?? ($sale->clientEmail->email ?? null)
      ?? ($sale->client->email ?? null)
      ?? ($sale->cliente_email ?? null);

  $atendidoPor  = $sale->user->name ?? $sale->user->nombre ?? '-';
  $invoice = !empty($sale->id)
      ? \App\Models\Sri\ElectronicInvoice::where('sale_id', $sale->id)->first()
      : null;
  $claveAcceso = $sale->clave_acceso ?? $invoice?->clave_acceso ?? null;

  $sriConfig = \App\Models\Sri\SriConfig::query()->first();
  $ambienteTicket = (int)($sriConfig->ambiente ?? 1) === 2
      ? 'AMBIENTE PRODUCCION'
      : 'PRUEBAS';

  $items = collect($sale->items ?? []);
  $payments = collect($sale->payments ?? []);

  $subtotal0 = 0.0;
  $subtotal15 = 0.0;

  foreach ($items as $itemTotal) {
      $ivaPctItemTotal = $itemTotal->iva_porcentaje ?? null;
      $ivaPctProdTotal = $itemTotal->producto?->iva_porcentaje ?? null;
      $ivaPctTotal = is_numeric($ivaPctItemTotal)
          ? (float)$ivaPctItemTotal
          : (is_numeric($ivaPctProdTotal) ? (float)$ivaPctProdTotal : 0);
      $baseTotal = round((float)($itemTotal->total ?? 0), 2);

      if ($ivaPctTotal > 0) {
          $subtotal15 += $baseTotal;
      } else {
          $subtotal0 += $baseTotal;
      }
  }

  $entrega = $payments->sum(function ($payment) {
      return is_null($payment->monto_recibido ?? null)
          ? (float)($payment->monto ?? 0)
          : (float)($payment->monto_recibido ?? 0);
  });

  $cambio = $payments->sum(fn ($payment) => (float)($payment->cambio ?? 0));

  $metodosPago = $payments
      ->map(fn ($payment) => $payment->paymentMethod?->nombre ?? $payment->metodo ?? null)
      ->filter()
      ->unique()
      ->implode(', ');
@endphp


  <div class="wrap">
    <div class="center bold" style="font-size:16px;">Papeleria y Bazar "El estudiante"</div>
    <div class="center" style="font-size:12px;">SIMBAÑA GALARZA JOSE SALOMON</div>
    <div class="center" style="font-size:12px;">RUC: 1710177245001</div>
    <div class="center" style="font-size:12px;">Dirección: José Miguel Guarderas 2245 y Carapungo</div>
    <div class="center" style="font-size:12px;">Teléfono: 095 906 1258</div>
    <div class="center" style="font-size:12px;">Correo: facturas@papeleriaybazarelestudiante.com</div>

    <div class="hr"></div>

    <div>
      <div class="center bold" style="font-size:14px;">Factura Electronica N°</div>
      <div  class="center bold" style="font-size:14px;">{{ $sale->num_factura ?? ('#'.$sale->id) }}</div>
      <div class="center" style="font-size:10px;">Ambiente: {{ $ambienteTicket }}</div>
    </div>

    <div class="hr"></div>

    <div class="row small">
      <div>Fecha:</div>
      <div class="right">{{ \Carbon\Carbon::parse($sale->fecha_venta)->format('d/m/Y H:i') }}</div>
    </div>

    <div class="row small">
      <div>Cliente:</div>
      <div class="right">{{ $clientNombre }}</div>
    </div>

    <div class="row small">
      <div>Identificación:</div>
      <div class="right">{{ $clientIdent }}</div>
    </div>

    <div class="row small">
      <div>Teléfono:</div>
      <div class="right">{{ $clientTel }}</div>
    </div>

    <div class="row small">
      <div>Dirección:</div>
      <div class="right">{{ $clientDir }}</div>
    </div>

    <div class="hr"></div>

    <table>
      <thead>
        <tr>
          <th class="left">Cant.</th>
          <th>Producto</th>
          <th class="right">P.Unitario</th>
          <th class="right">Total</th>
        </tr>
      </thead>
      <tbody>
        @foreach($items as $it)
          @php
            $ivaPctItem = $it->iva_porcentaje ?? null;
            $ivaPctProd = $it->producto?->iva_porcentaje ?? null;
            $ivaPct     = is_numeric($ivaPctItem) ? (float)$ivaPctItem : (is_numeric($ivaPctProd) ? (float)$ivaPctProd : 0);
            $gravaIva   = $ivaPct > 0;
            $baseLinea  = round((float)($it->total ?? 0), 2);
            $ivaLinea   = round($baseLinea * ($ivaPct / 100), 2);
            $totalLinea = round($baseLinea + $ivaLinea, 2);
            $qtyLinea   = max(1, (float)($it->cantidad ?? 1));
            $puBase     = round($baseLinea / $qtyLinea, 2);
          @endphp
          <tr>
            <td class="center">{{ $it->cantidad }}</td>
            <td>
              {{ $it->descripcion }}
              <div class="small muted">
                @if(($it->descuento ?? 0) > 0)
                  Desc ${{ number_format($it->descuento, 2) }}
                @endif
              </div>
            </td>
            <td class="right">${{ number_format($puBase, 2) }}</td>
            <td class="right">${{ number_format($totalLinea, 2) }}@if($gravaIva) * @endif</td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <div class="hr"></div>

    <div class="totals">
      <div class="row"><div>Subtotal</div><div class="right">${{ number_format($sale->subtotal, 2) }}</div></div>
      <div class="row"><div>Subtotal 0</div><div class="right">${{ number_format($subtotal0, 2) }}</div></div>
      <div class="row"><div>Subtotal 15</div><div class="right">${{ number_format($subtotal15, 2) }}</div></div>
      <div class="row"><div>Descuento</div><div class="right">${{ number_format($sale->descuento, 2) }}</div></div>
      <div class="row"><div>Iva 15</div><div class="right">${{ number_format($sale->iva, 2) }}</div></div>
      <div class="row bold" style="font-size:14px;"><div>TOTAL</div><div class="right">${{ number_format($sale->total, 2) }}</div></div>
      <div class="row"><div>Entrega</div><div class="right">${{ number_format($entrega, 2) }}</div></div>
      <div class="row bold"><div>CAMBIO</div><div class="right">${{ number_format($cambio, 2) }}</div></div>
    </div>

    <div class="hr"></div>
    <div class="row small"><div>Método de pago</div><div class="right">{{ $metodosPago ?: '-' }}</div></div>
    <div class="row small"> <div>Atendido por</div> <div class="right">{{ $atendidoPor }}</div></div>
    <div class="hr"></div>

    {{-- Texto comprobante electrónico --}}
    <div class="note">
      <div class="bold">Comprobante electrónico</div>
      <div>
        Enviado al correo: <span>{{ $clientEmail ?? 'N/D' }}</span>.
      </div>
      <div style="margin-top:6px;">
        Recuerde también que puede consultar su comprobante en el portal del SRI:
        srienlinea.sri.gob.ec
      </div>
      <div style="margin-top:6px;">
        Clave de acceso:
        <span class="bold">{{ $claveAcceso ?? 'No generada' }}</span>
      </div>
    </div>

    <div class="hr"></div>
    <div class="center bold">¡Gracias por su compra!</div>
    <div class="hr"></div>
    <div class="center bold" style="font-size:10px;">Desarrollado por Nivusoftware</div>

    <div class="no-print" style="margin-top:10px;">
      <button onclick="window.print()">Imprimir</button>
      <button onclick="window.close()">Cerrar</button>
    </div>
  </div>

  @if($auto)
  <script>
    window.addEventListener('load', () => {
      setTimeout(() => {
        window.focus();
        window.print();
      }, 200);
    });

    window.addEventListener('afterprint', () => {
      const qs = new URLSearchParams(location.search);
      if (qs.get('embed') === '1' && window.parent) {
        window.parent.postMessage({ type: 'ticket-printed', id: {{ $sale->id }} }, '*');
      }
    });
  </script>
  @endif

</body>
</html>
