<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-blue-900 leading-tight">
                {{ __('Cierres de caja diarios') }}
            </h2>
            <button onclick="window.location.href='{{ route('reporteria.menu') }}'"
                class="text-blue-700 hover:text-blue-900 transition flex items-center space-x-1" title="Regresar">
                <x-heroicon-s-arrow-left class="w-5 h-5" />
                <span>Atras</span>
            </button>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="bg-white border border-blue-100 rounded-xl p-4 mb-6 overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-[1.2fr_1.1fr_1.7fr] gap-4 items-center">
                    <div class="flex flex-row flex-wrap gap-3">
                        <div class="text-sm text-blue-900 font-semibold">Resumen del dia</div>
                        <div class="flex flex-row flex-wrap gap-4 text-xs text-blue-800">
                            <div>Fecha: <span class="font-semibold">{{ $fecha }}</span></div>
                            <div>Total cierres: <span class="font-semibold">{{ $sessions->count() }}</span></div>
                        </div>
                    </div>
                    <div class="text-xs text-blue-800 lg:order-2 lg:mr-0 lg:pr-2">
                        Muestra cierres por fecha de cierre. Formas de pago entre apertura y cierre (con tolerancia de 5 minutos).
                    </div>
                    <div class="min-w-0 lg:order-3">
                        <div class="text-sm text-blue-900 font-semibold mb-2">Filtro</div>
                        @php
                            $exportParams = [];
                            if (!empty($fecha)) {
                                $exportParams['fecha'] = $fecha;
                            }
                        @endphp
                        <form method="GET" action="{{ route('reporteria.cashier.closures.daily') }}" class="flex flex-wrap gap-2 items-center">
                            <input type="date" name="fecha" value="{{ $fecha ?? '' }}"
                                class="border border-blue-100 rounded px-2 py-1 text-xs min-w-[160px]" />
                            <button type="submit" class="text-xs px-3 py-1 rounded bg-blue-600 text-white hover:bg-blue-700 shrink-0">Aplicar</button>
                            <a href="{{ route('reporteria.cashier.closures.daily.export', $exportParams) }}"
                                class="text-xs px-3 py-1 rounded bg-blue-100 text-blue-800 hover:bg-blue-200 text-center whitespace-nowrap shrink-0">
                                Exportar Excel
                            </a>
                        </form>
                    </div>
                    
                </div>
            </div>

            <div class="bg-white shadow-sm border border-blue-100 rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-blue-100 text-sm">
                        <thead class="bg-blue-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-blue-900">Caja</th>
                                <th class="px-4 py-3 text-left font-semibold text-blue-900">Apertura</th>
                                <th class="px-4 py-3 text-left font-semibold text-blue-900">Cierre</th>
                                <th class="px-4 py-3 text-left font-semibold text-blue-900">Horas</th>
                                <th class="px-4 py-3 text-left font-semibold text-blue-900">Usuario apertura</th>
                                <th class="px-4 py-3 text-left font-semibold text-blue-900">Usuario cierre</th>
                                <th class="px-4 py-3 text-right font-semibold text-blue-900">Esperado</th>
                                <th class="px-4 py-3 text-right font-semibold text-blue-900">Declarado</th>
                                <th class="px-4 py-3 text-right font-semibold text-blue-900">Diferencia</th>
                                <th class="px-4 py-3 text-left font-semibold text-blue-900">Resultado</th>
                                <th class="px-4 py-3 text-left font-semibold text-blue-900">Formas de pago</th>
                                <th class="px-4 py-3 text-left font-semibold text-blue-900">Notas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-blue-100">
                            @forelse ($sessions as $s)
                                <tr>
                                    <td class="px-4 py-3 text-blue-900">#{{ $s->caja_id }}</td>
                                    <td class="px-4 py-3 text-xs text-blue-900">
                                        {{ $s->opened_at?->format('Y-m-d H:i') ?? 'N/D' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-blue-900">
                                        {{ $s->closed_at?->format('Y-m-d H:i') ?? 'N/D' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-blue-900">{{ $s->duration_text ?? 'N/D' }}</td>
                                    <td class="px-4 py-3 text-xs text-blue-900">{{ $s->opener?->name ?? 'N/D' }}</td>
                                    <td class="px-4 py-3 text-xs text-blue-900">{{ $s->closer?->name ?? 'N/D' }}</td>
                                    <td class="px-4 py-3 text-right text-blue-900">${{ number_format((float) ($s->expected_amount ?? 0), 2) }}</td>
                                    <td class="px-4 py-3 text-right text-blue-900">${{ number_format((float) ($s->declared_amount ?? 0), 2) }}</td>
                                    <td class="px-4 py-3 text-right text-blue-900">${{ number_format((float) ($s->difference_amount ?? 0), 2) }}</td>
                                    @php
                                        $res = $s->result_label ?? 'N/D';
                                        $resClass = $res === 'CUADRA'
                                            ? 'bg-green-100 text-green-800'
                                            : ($res === 'FALTANTE'
                                                ? 'bg-red-100 text-red-800'
                                                : ($res === 'SOBRANTE'
                                                    ? 'bg-orange-100 text-orange-800'
                                                    : 'bg-slate-100 text-slate-800'
                                                )
                                            );
                                    @endphp
                                    <td class="px-4 py-3 text-xs text-blue-900 font-semibold">
                                        <span class="px-2 py-1 rounded text-xs {{ $resClass }}">{{ $res }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-blue-900">
                                        @if (($s->payment_methods ?? collect())->count())
                                            @foreach ($s->payment_methods as $idx => $metodo)
                                                <span class="inline-block mr-2">
                                                    {{ $metodo ?? 'N/D' }}
                                                </span>
                                            @endforeach
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-blue-900">
                                        {{ $s->notes ? $s->notes : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-6 text-center text-blue-700" colspan="13">
                                        No hay cierres registrados para este dia.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
