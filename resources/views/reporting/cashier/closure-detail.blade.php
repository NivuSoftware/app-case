<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-blue-900 leading-tight">
                Detalle cierre caja #{{ $session->caja_id }}
            </h2>

            <div class="flex items-center gap-3">
                <button
                    type="button"
                    id="btnPrintCashClosureDetail"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold shadow hover:bg-black transition"
                >
                    Imprimir resumen
                </button>

                <button
                    onclick="window.location.href='{{ route('reporteria.cashier.closures.daily', ['fecha' => optional($session->closed_at)->toDateString()]) }}'"
                    class="text-blue-700 hover:text-blue-900 transition text-sm"
                >
                    Volver a cierres diarios
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-6">
            <div class="bg-white border border-blue-100 rounded-2xl shadow-sm p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-[11px] text-blue-700 uppercase font-semibold">Apertura</p>
                        <p class="font-semibold text-blue-900">{{ $session->opened_at?->format('d/m/Y H:i') ?? 'N/D' }}</p>
                        <p class="text-blue-800">Monto: ${{ number_format((float) $session->opening_amount, 2) }}</p>
                        <p class="text-blue-800">Por: {{ $session->opener?->name ?? 'N/D' }}</p>
                    </div>

                    <div>
                        <p class="text-[11px] text-blue-700 uppercase font-semibold">Cierre</p>
                        <p class="font-semibold text-blue-900">{{ $session->closed_at?->format('d/m/Y H:i') ?? 'N/D' }}</p>
                        <p class="text-blue-800">Duración: {{ $durationText }}</p>
                        <p class="text-blue-800">Por: {{ $session->closer?->name ?? 'N/D' }}</p>
                        <p class="text-blue-800">Resultado: <span class="font-bold">{{ $resultLabel }}</span></p>
                    </div>

                    <div>
                        <p class="text-[11px] text-blue-700 uppercase font-semibold">Totales</p>
                        <p class="text-blue-800">Esperado: <span class="font-semibold">${{ number_format((float) $session->expected_amount, 2) }}</span></p>
                        <p class="text-blue-800">Declarado: <span class="font-semibold">${{ number_format((float) $session->declared_amount, 2) }}</span></p>
                        <p class="text-blue-800">Diferencia: <span class="font-semibold">${{ number_format((float) $session->difference_amount, 2) }}</span></p>
                    </div>
                </div>

                <hr class="my-5 border-blue-100">

                <h3 class="text-sm font-semibold text-blue-900 mb-3">Movimientos (Ingresos / Retiros)</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-blue-50">
                            <tr>
                                <th class="text-left px-3 py-2 text-[11px] uppercase text-blue-700">Fecha</th>
                                <th class="text-left px-3 py-2 text-[11px] uppercase text-blue-700">Tipo</th>
                                <th class="text-right px-3 py-2 text-[11px] uppercase text-blue-700">Monto</th>
                                <th class="text-left px-3 py-2 text-[11px] uppercase text-blue-700">Motivo</th>
                                <th class="text-left px-3 py-2 text-[11px] uppercase text-blue-700">Usuario</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-blue-100">
                            @forelse($session->movements as $movement)
                                @php
                                    $typeLabel = $movement->type === 'IN'
                                        ? 'Ingreso'
                                        : ($movement->type === 'OUT' ? 'Retiro' : $movement->type);
                                @endphp
                                <tr>
                                    <td class="px-3 py-2 text-blue-900">{{ $movement->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-2 text-blue-900 font-semibold">{{ $typeLabel }}</td>
                                    <td class="px-3 py-2 text-right text-blue-900">${{ number_format((float) $movement->amount, 2) }}</td>
                                    <td class="px-3 py-2 text-blue-900">{{ $movement->reason }}</td>
                                    <td class="px-3 py-2 text-blue-900">{{ $movement->creator?->name ?? 'N/D' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-6 text-center text-blue-700">
                                        Sin movimientos registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($session->notes)
                    <div class="mt-4 text-sm text-blue-800">
                        <p class="text-[11px] uppercase font-semibold text-blue-700">Notas</p>
                        <p>{{ $session->notes }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <iframe
        id="cashClosureDetailPrintFrame"
        style="position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;"
    ></iframe>

    <script>
    (function () {
        const btn = document.getElementById('btnPrintCashClosureDetail');
        const frame = document.getElementById('cashClosureDetailPrintFrame');

        if (!btn || !frame) return;

        btn.addEventListener('click', () => {
            const url = @json(route('reporteria.cashier.closures.detail.print', ['id' => $session->id]));
            frame.src = url + (url.includes('?') ? '&' : '?') + '_t=' + Date.now();
        });

        window.addEventListener('message', (e) => {
            if (e?.data?.type === 'cash-summary-printed') {
                frame.src = 'about:blank';
            }
        });
    })();
    </script>
</x-app-layout>
