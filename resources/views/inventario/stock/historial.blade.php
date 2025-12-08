<x-app-layout>

    {{-- HEADER --}}
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-2xl text-blue-900 leading-tight">
                    {{ __('Historial de movimientos de stock') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Producto: <span class="font-semibold">{{ $producto_nombre }}</span> |
                    Bodega: <span class="font-semibold">{{ $bodega_nombre }}</span>
                    @if($percha_codigo)
                        | Percha: <span class="font-semibold">{{ $percha_codigo }}</span>
                    @endif
                </p>
            </div>

            <a href="{{ route('inventario.stock') }}"
               class="text-blue-700 hover:text-blue-900 transition flex items-center">
                <x-heroicon-s-arrow-left class="w-6 h-6 mr-1" />
                Volver a Stock
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-6 lg:px-8">

            <div id="historial-root"
                 data-producto-id="{{ $producto_id }}"
                 data-bodega-id="{{ $bodega_id }}"
                 data-percha-id="{{ $percha_id }}">

                <div class="bg-white rounded-lg shadow border border-gray-200 p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-blue-900">
                            Movimientos registrados (ajustes de stock)
                        </h3>
                        <span class="text-xs text-gray-500">
                            *Por ahora solo se muestran ajustes manuales. Más adelante aquí
                            también podrán verse ventas y compras.
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-100 text-left">
                                    <th class="px-3 py-2 border-b">Fecha</th>
                                    <th class="px-3 py-2 border-b">Usuario</th>
                                    <th class="px-3 py-2 border-b text-right">Stock inicial</th>
                                    <th class="px-3 py-2 border-b text-right">Stock final</th>
                                    <th class="px-3 py-2 border-b text-right">Diferencia</th>
                                    <th class="px-3 py-2 border-b">Tipo</th>
                                    <th class="px-3 py-2 border-b">Motivo</th>
                                </tr>
                            </thead>
                            <tbody id="historial-body">
                                {{-- Se llena con JS --}}
                            </tbody>
                        </table>
                    </div>

                    <p id="historial-empty" class="text-gray-500 text-sm mt-4 hidden">
                        No se han registrado ajustes de stock para este producto en esta ubicación.
                    </p>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const root = document.getElementById('historial-root');
            const productoId = root.dataset.productoId;
            const bodegaId   = root.dataset.bodegaId;
            const perchaId   = root.dataset.perchaId || '';

            const tbody = document.getElementById('historial-body');
            const empty = document.getElementById('historial-empty');

            const params = new URLSearchParams({
                producto_id: productoId,
                bodega_id: bodegaId,
                percha_id: perchaId
            });

            fetch(`/inventario/historial/data?${params.toString()}`)
                .then(r => r.json())
                .then(data => {
                    tbody.innerHTML = '';

                    if (!data.length) {
                        empty.classList.remove('hidden');
                        return;
                    }

                    data.forEach(item => {
                        const diff = Number(item.diferencia);
                        const tipoLabel = item.tipo === 'positivo' ? 'Aumento' : 'Disminución';
                        const diffFormatted = (diff > 0 ? '+' : '') + diff;

                        const fecha = new Date(item.created_at);
                        const fechaStr = fecha.toLocaleString('es-EC', {
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit',
                            hour: '2-digit',
                            minute: '2-digit'
                        });

                        const usuario = item.usuario ? (item.usuario.name || item.usuario.email || '—') : '—';

                        const tr = document.createElement('tr');
                        tr.className = 'border-b hover:bg-gray-50';

                        tr.innerHTML = `
                            <td class="px-3 py-2 align-top">${fechaStr}</td>
                            <td class="px-3 py-2 align-top">${usuario}</td>
                            <td class="px-3 py-2 text-right align-top">${item.stock_inicial}</td>
                            <td class="px-3 py-2 text-right align-top">${item.stock_final}</td>
                            <td class="px-3 py-2 text-right align-top">
                                <span class="${diff > 0 ? 'text-green-700' : 'text-red-700'} font-semibold">
                                    ${diffFormatted}
                                </span>
                            </td>
                            <td class="px-3 py-2 align-top">
                                <span class="inline-flex px-2 py-1 rounded text-xs
                                    ${item.tipo === 'positivo'
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-red-100 text-red-800'}">
                                    ${tipoLabel}
                                </span>
                            </td>
                            <td class="px-3 py-2 align-top text-sm">
                                ${item.motivo ?? '—'}
                            </td>
                        `;

                        tbody.appendChild(tr);
                    });
                })
                .catch(err => {
                    console.error(err);
                    empty.textContent = 'Ocurrió un error al cargar el historial.';
                    empty.classList.remove('hidden');
                });
        });
    </script>

</x-app-layout>
