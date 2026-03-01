<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Reporte Diario Forma de Pago</title>
  <style>
    @page { size: 80mm auto; margin: 0; }
    html, body { margin: 0; padding: 0; }
    body { width: 80mm; font-family: Arial, sans-serif; font-size: 11px; color: #000; }
    .wrap { padding: 8px 6px; }
    .center { text-align: center; }
    .right { text-align: right; }
    .bold { font-weight: 700; }
    .small { font-size: 10px; }
    .hr { border-top: 1px dashed #000; margin: 6px 0; }
    .row { display: flex; justify-content: space-between; gap: 8px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 3px 0; vertical-align: top; }
    th { font-size: 9px; border-bottom: 1px solid #000; text-transform: uppercase; }
    .no-print { margin-top: 8px; }
    @media print { .no-print { display: none !important; } }
  </style>
</head>
<body>
  @php
    $bodegaNombre = 'Todas las bodegas';
    if (!empty($bodegaId ?? null)) {
        $match = ($bodegas ?? collect())->firstWhere('id', $bodegaId);
        if ($match) $bodegaNombre = (string) $match->nombre;
    }
  @endphp

  <div class="wrap">
    <div class="center bold" style="font-size: 13px;">Reporte Diario</div>
    <div class="center bold">Forma de Pago</div>
    <div class="hr"></div>

    <div class="row small"><div>Fecha</div><div class="right">{{ $fecha ?? '' }}</div></div>
    <div class="row small"><div>Bodega</div><div class="right">{{ $bodegaNombre }}</div></div>
    <div class="row small"><div>Total facturado</div><div class="right">${{ number_format((float)($totalVentas ?? 0), 2) }}</div></div>
    <div class="hr"></div>

    <table>
      <thead>
        <tr>
          <th>Forma</th>
          <th class="right">Pagos</th>
          <th class="right">Ventas</th>
          <th class="right">Total</th>
        </tr>
      </thead>
      <tbody>
        @forelse(($rows ?? collect()) as $row)
          <tr>
            <td>{{ $row->metodo ?? 'N/D' }}</td>
            <td class="right">{{ (int)($row->pagos ?? 0) }}</td>
            <td class="right">{{ (int)($row->ventas ?? 0) }}</td>
            <td class="right">${{ number_format((float)($row->total_monto ?? 0), 2) }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="center small">No hay datos para imprimir.</td>
          </tr>
        @endforelse
      </tbody>
      @if(($rows ?? collect())->count())
        <tfoot>
          <tr>
            <td class="bold">Total</td>
            <td class="right bold">{{ (int)(($rows ?? collect())->sum('pagos')) }}</td>
            <td class="right bold">{{ (int)(($rows ?? collect())->sum('ventas')) }}</td>
            <td class="right bold">${{ number_format((float)(($rows ?? collect())->sum('total_monto')), 2) }}</td>
          </tr>
        </tfoot>
      @endif
    </table>

    <div class="hr"></div>
    <div class="center small">Generado: {{ now()->format('Y-m-d H:i:s') }}</div>

    <div class="no-print center">
      <button onclick="window.print()">Imprimir</button>
      <button onclick="window.close()">Cerrar</button>
    </div>
  </div>

  <script>
    window.addEventListener('load', () => {
      setTimeout(() => {
        window.focus();
        window.print();
      }, 150);
    });

    window.addEventListener('afterprint', () => {
      window.close();
    });
  </script>
</body>
</html>

