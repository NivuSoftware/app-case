<x-app-layout>

    {{-- HEADER --}}
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-blue-900 leading-tight">
                {{ __('Compras a Proveedores') }}
            </h2>

            {{-- Botón volver en la posición original de "Nueva compra" --}}
            <button 
                onclick="window.location.href='{{ route('proveedores.menu') }}'"
                class="text-blue-700 hover:text-blue-900 transition flex items-center"
            >
                <x-heroicon-s-arrow-left class="w-5 h-5 mr-1" />
                Volver al módulo
            </button>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">

            {{-- FILTROS --}}
            <div class="bg-white rounded-lg shadow border border-gray-200 p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            Proveedor
                        </label>
                        <input id="f-proveedor" type="text" class="border rounded w-full px-3 py-2 text-sm"
                               placeholder="Buscar por proveedor...">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            Desde
                        </label>
                        <input id="f-desde" type="date" class="border rounded w-full px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            Hasta
                        </label>
                        <input id="f-hasta" type="date" class="border rounded w-full px-3 py-2 text-sm">
                    </div>
                    <div class="flex items-end">
                        <button
                            onclick="aplicarFiltros()"
                            class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded w-full text-sm">
                            Aplicar filtros
                        </button>
                    </div>
                </div>
            </div>

            {{-- TABLA --}}
            <div class="bg-white rounded-lg shadow border border-gray-200 p-4">
                <div class="flex justify-between items-center mb-3">
                    <div>
                        <h3 class="text-lg font-semibold text-blue-900">Listado de compras</h3>
                        <span id="total-registros" class="block text-xs text-gray-500 mt-1"></span>
                    </div>

                    {{-- Botón NUEVA COMPRA ahora aquí --}}
                    <button 
                        onclick="window.location.href='{{ route('compras.create') }}'"
                        class="bg-blue-700 hover:bg-blue-800 text-white px-4 py-2 rounded shadow flex items-center text-sm"
                    >
                        <x-heroicon-s-plus class="w-4 h-4 mr-1" />
                        Nueva compra
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 uppercase text-xs">
                                <th class="px-3 py-2 text-left">Fecha</th>
                                <th class="px-3 py-2 text-left">Proveedor</th>
                                <th class="px-3 py-2 text-right">Total</th>
                                <th class="px-3 py-2 text-right">Pagado</th>
                                <th class="px-3 py-2 text-right">Saldo</th>
                                <th class="px-3 py-2 text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-compras">
                            {{-- Se llena por JS --}}
                        </tbody>
                    </table>
                </div>

                <div id="sin-datos" class="text-center text-gray-400 text-sm py-6 hidden">
                    No hay compras registradas todavía.
                </div>
            </div>

        </div>
    </div>

    <script>
        // URL correcta usando el nombre de la ruta (respeta el prefijo /inventario)
        const COMPRAS_LIST_URL = "{{ route('compras.list') }}";
        let TODAS_COMPRAS = [];

        document.addEventListener('DOMContentLoaded', () => {
            cargarCompras();
        });

        async function cargarCompras() {
            try {
                const resp = await fetch(COMPRAS_LIST_URL, {
                    headers: { 'Accept': 'application/json' }
                });

                // Si hay error HTTP, mostramos alerta UNA vez con info útil
                if (!resp.ok) {
                    const texto = await resp.text();
                    console.error('Error HTTP en COMPRAS_LIST_URL:', resp.status, texto);

                    if (window.Swal) {
                        Swal.fire(
                            'Error',
                            'No se pudo cargar el listado de compras (HTTP ' + resp.status + ').',
                            'error'
                        );
                    }
                    return;
                }

                const contentType = resp.headers.get('content-type') || '';
                // Si no es JSON, no intentamos parsear (evitamos el error molesto)
                if (!contentType.includes('application/json')) {
                    console.warn('Respuesta no JSON en COMPRAS_LIST_URL', await resp.text());
                    return;
                }

                const data = await resp.json();
                TODAS_COMPRAS = Array.isArray(data) ? data : [];
                renderCompras(TODAS_COMPRAS);

            } catch (err) {
                console.error('Error de red en COMPRAS_LIST_URL', err);
                if (window.Swal) {
                    Swal.fire(
                        'Error',
                        'No se pudo cargar el listado de compras por un problema de conexión.',
                        'error'
                    );
                }
            }
        }

        function renderCompras(lista) {
            const tbody     = document.getElementById('tbody-compras');
            const sinDatos  = document.getElementById('sin-datos');
            const lblTotal  = document.getElementById('total-registros');

            tbody.innerHTML = '';

            if (!lista.length) {
                sinDatos.classList.remove('hidden');
                lblTotal.textContent = '0 registros';
                return;
            } else {
                sinDatos.classList.add('hidden');
            }

            const formatter = new Intl.NumberFormat('es-EC', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 2
            });

            lista.forEach(p => {
                const estadoBadge = p.estado === 'pagada'
                    ? '<span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">Pagada</span>'
                    : '<span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-700">Pendiente</span>';

                const tr = document.createElement('tr');
                tr.className = 'border-b last:border-0 hover:bg-gray-50';

                tr.innerHTML = `
                    <td class="px-3 py-2">${p.fecha_compra ?? ''}</td>
                    <td class="px-3 py-2">${p.supplier?.nombre ?? ''}</td>
                    <td class="px-3 py-2 text-right font-semibold">${formatter.format(p.total ?? 0)}</td>
                    <td class="px-3 py-2 text-right">${formatter.format(p.total_pagado ?? 0)}</td>
                    <td class="px-3 py-2 text-right">${formatter.format(p.saldo ?? 0)}</td>
                    <td class="px-3 py-2 text-center">${estadoBadge}</td>
                `;

                tbody.appendChild(tr);
            });

            lblTotal.textContent = `${lista.length} registro(s)`;
        }

        function aplicarFiltros() {
            const qProv = document.getElementById('f-proveedor').value.toLowerCase();
            const desde = document.getElementById('f-desde').value;
            const hasta = document.getElementById('f-hasta').value;

            let filtradas = [...TODAS_COMPRAS];

            const fechaSolo = (v) => {
                if (!v) return '';
                const s = String(v);
                // Soporta 'YYYY-MM-DD' y timestamps tipo ISO 'YYYY-MM-DDTHH:mm:ss...'
                return s.length >= 10 ? s.slice(0, 10) : s;
            };

            if (qProv) {
                filtradas = filtradas.filter(p =>
                    (p.supplier?.nombre || '').toLowerCase().includes(qProv)
                );
            }

            if (desde) {
                filtradas = filtradas.filter(p => {
                    const f = fechaSolo(p.fecha_compra);
                    return !f || f >= desde;
                });
            }

            if (hasta) {
                filtradas = filtradas.filter(p => {
                    const f = fechaSolo(p.fecha_compra);
                    return !f || f <= hasta; // inclusivo: incluye exactamente la fecha "hasta"
                });
            }

            renderCompras(filtradas);
        }
    </script>


</x-app-layout>
