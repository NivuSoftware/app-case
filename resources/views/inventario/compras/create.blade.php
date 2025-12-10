<x-app-layout>

    {{-- HEADER --}}
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-blue-900 leading-tight">
                {{ __('Registrar Compra a Proveedor') }}
            </h2>

            <button 
                onclick="window.location.href='{{ route('compras.index') }}'"
                class="text-blue-700 hover:text-blue-900 transition flex items-center"
            >
                <x-heroicon-s-arrow-left class="w-6 h-6 mr-1" />
                Volver al listado
            </button>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 space-y-6">

            {{-- CABECERA Y PAGO --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Datos de compra --}}
                <div class="lg:col-span-2 bg-white rounded-lg shadow border border-gray-200 p-5 space-y-4">
                    <h3 class="text-lg font-semibold text-blue-900 mb-2">Datos de la compra</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Proveedor
                            </label>
                            <select id="supplier_id"
                                    class="border rounded w-full px-3 py-2 text-sm">
                                <option value="">-- Seleccione --</option>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Fecha de compra
                            </label>
                            <input id="fecha_compra" type="date"
                                   value="{{ now()->toDateString() }}"
                                   class="border rounded w-full px-3 py-2 text-sm">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Descripción / Nota
                            </label>
                            <input id="descripcion" type="text"
                                   class="border rounded w-full px-3 py-2 text-sm"
                                   placeholder="Ej: Compra mensual de stock, reposición de productos, etc.">
                        </div>
                    </div>
                </div>

                {{-- Pago de la compra (100% pagada) --}}
                <div class="bg-white rounded-lg shadow border border-gray-200 p-5 space-y-3">
                    <h3 class="text-lg font-semibold text-blue-900 mb-2">Pago de la compra</h3>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Método de pago
                            </label>
                            <select id="metodo_pago"
                                    class="border rounded w-full px-3 py-2 text-sm">
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="credito">Crédito</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Monto pagado (se registra el 100% de la compra)
                            </label>
                            <input id="monto_pagado" type="number" min="0" step="0.01"
                                   class="border rounded w-full px-3 py-2 text-sm bg-gray-100"
                                   placeholder="0.00" readonly>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Referencia (opcional)
                            </label>
                            <input id="referencia" type="text"
                                   class="border rounded w-full px-3 py-2 text-sm"
                                   placeholder="N° de comprobante, trx, etc.">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Observaciones (opcional)
                            </label>
                            <textarea id="observaciones" rows="2"
                                      class="border rounded w-full px-3 py-2 text-xs"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ÍTEMS DE LA COMPRA --}}
            <div class="bg-white rounded-lg shadow border border-gray-200 p-5 space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-blue-900">Ítems de la compra</h3>
                    <button
                        onclick="agregarItem()"
                        class="bg-blue-700 hover:bg-blue-800 text-white px-4 py-2 rounded text-sm flex items-center">
                        <x-heroicon-s-plus class="w-4 h-4 mr-1" /> Agregar ítem
                    </button>
                </div>

                {{-- Fila de captura rápida --}}
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end border-b pb-4 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            Producto
                        </label>
                        <select id="c-producto" class="border rounded w-full px-2 py-1 text-xs">
                            <option value="">-- Seleccione --</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            Bodega
                        </label>
                        <select id="c-bodega" class="border rounded w-full px-2 py-1 text-xs">
                            <option value="">-- Seleccione --</option>
                            @foreach($bodegas as $b)
                                <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            Percha (opcional)
                        </label>
                        <select id="c-percha" class="border rounded w-full px-2 py-1 text-xs">
                            <option value="">-- Ninguna --</option>
                            @foreach($perchas as $per)
                                <option value="{{ $per->id }}">{{ $per->codigo }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            Cantidad
                        </label>
                        <input id="c-cantidad" type="number" min="1" step="1"
                               class="border rounded w-full px-2 py-1 text-xs" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            Costo unitario (USD)
                        </label>
                        <input id="c-costo" type="number" min="0" step="0.0001"
                               class="border rounded w-full px-2 py-1 text-xs" />
                    </div>
                </div>

                {{-- TABLA DE ÍTEMS --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 uppercase text-[11px]">
                                <th class="px-2 py-2 text-left">Producto</th>
                                <th class="px-2 py-2 text-left">Bodega</th>
                                <th class="px-2 py-2 text-left">Percha</th>
                                <th class="px-2 py-2 text-right">Cant.</th>
                                <th class="px-2 py-2 text-right">Costo unit.</th>
                                <th class="px-2 py-2 text-right">Subtotal</th>
                                <th class="px-2 py-2 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-items">
                            {{-- Se llena por JS --}}
                        </tbody>
                    </table>
                </div>

                {{-- TOTALES --}}
                <div class="flex justify-end mt-4">
                    <div class="w-full md:w-1/3 border-t pt-3 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span>Subtotal (sin IVA):</span>
                            <span id="lbl-subtotal" class="font-medium">$ 0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span>IVA (15%):</span>
                            <span id="lbl-iva">$ 0.00</span>
                        </div>
                        <div class="flex justify-between text-base font-semibold">
                            <span>Total compra:</span>
                            <span id="lbl-total" class="font-bold text-blue-800">$ 0.00</span>
                        </div>
                        <div class="flex justify-between text-xs text-gray-600 pt-1 border-t mt-2">
                            <span>Monto pagado:</span>
                            <span id="lbl-monto-pagado" class="font-semibold">$ 0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- BOTÓN GUARDAR --}}
            <div class="flex justify-end">
                <button
                    onclick="guardarCompra()"
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded shadow font-semibold">
                    Guardar compra
                </button>
            </div>

        </div>
    </div>

    <script>
        const PRODUCTS = @json($products->map(fn($p) => ['id' => $p->id, 'nombre' => $p->nombre]));
        const BODEGAS  = @json($bodegas->map(fn($b) => ['id' => $b->id, 'nombre' => $b->nombre]));
        const PERCHAS  = @json($perchas->map(fn($per) => ['id' => $per->id, 'codigo' => $per->codigo]));

        let ITEMS = [];

        const STORE_URL = "{{ route('compras.store') }}"; // /inventario/compras
        const IVA_RATE  = 0.15; // 15% fijo

        const formatter = new Intl.NumberFormat('es-EC', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2
        });

        function agregarItem() {
            const productoId = document.getElementById('c-producto').value;
            const bodegaId   = document.getElementById('c-bodega').value;
            const perchaId   = document.getElementById('c-percha').value || null;
            const cantidad   = parseInt(document.getElementById('c-cantidad').value, 10);
            const costo      = parseFloat(document.getElementById('c-costo').value);

            if (!productoId || !bodegaId || !cantidad || !costo) {
                if (window.Swal) {
                    Swal.fire('Datos incompletos', 'Producto, bodega, cantidad y costo son obligatorios.', 'warning');
                } else {
                    alert('Producto, bodega, cantidad y costo son obligatorios.');
                }
                return;
            }

            const prod  = PRODUCTS.find(p => p.id == productoId);
            const bod   = BODEGAS.find(b => b.id == bodegaId);
            const per   = PERCHAS.find(p => p.id == perchaId);

            const item = {
                producto_id:      parseInt(productoId),
                producto_nombre:  prod ? prod.nombre : '',
                bodega_id:        parseInt(bodegaId),
                bodega_nombre:    bod ? bod.nombre : '',
                percha_id:        perchaId ? parseInt(perchaId) : null,
                percha_codigo:    per ? per.codigo : '',
                cantidad:         cantidad,
                costo_unitario:   costo,
                subtotal:         cantidad * costo
            };

            ITEMS.push(item);
            renderItems();
            limpiarCapturaRapida();
        }

        function limpiarCapturaRapida() {
            document.getElementById('c-producto').value = '';
            document.getElementById('c-bodega').value   = '';
            document.getElementById('c-percha').value   = '';
            document.getElementById('c-cantidad').value = '';
            document.getElementById('c-costo').value    = '';
        }

        function renderItems() {
            const tbody = document.getElementById('tbody-items');
            tbody.innerHTML = '';

            ITEMS.forEach((it, idx) => {
                const tr = document.createElement('tr');
                tr.className = 'border-b last:border-0';

                tr.innerHTML = `
                    <td class="px-2 py-2">${it.producto_nombre}</td>
                    <td class="px-2 py-2">${it.bodega_nombre}</td>
                    <td class="px-2 py-2">${it.percha_codigo || '—'}</td>
                    <td class="px-2 py-2 text-right">${it.cantidad}</td>
                    <td class="px-2 py-2 text-right">${formatter.format(it.costo_unitario)}</td>
                    <td class="px-2 py-2 text-right font-semibold">${formatter.format(it.subtotal)}</td>
                    <td class="px-2 py-2 text-center">
                        <button
                            class="text-red-600 hover:text-red-800 text-xs"
                            onclick="eliminarItem(${idx})">
                            Quitar
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            recalcularTotales();
        }

        function eliminarItem(index) {
            ITEMS.splice(index, 1);
            renderItems();
        }

        function recalcularTotales() {
            const subtotal = ITEMS.reduce((acc, it) => acc + it.subtotal, 0);
            const iva      = subtotal * IVA_RATE;
            const total    = subtotal + iva;

            const lblSubtotal     = document.getElementById('lbl-subtotal');
            const lblIva          = document.getElementById('lbl-iva');
            const lblTotal        = document.getElementById('lbl-total');
            const lblMontoPagado  = document.getElementById('lbl-monto-pagado');
            const inputMonto      = document.getElementById('monto_pagado');

            if (lblSubtotal)    lblSubtotal.textContent    = formatter.format(subtotal);
            if (lblIva)         lblIva.textContent         = formatter.format(iva);
            if (lblTotal)       lblTotal.textContent       = formatter.format(total);
            if (lblMontoPagado) lblMontoPagado.textContent = formatter.format(total);

            if (inputMonto) {
                inputMonto.value = total.toFixed(2);
            }
        }

        async function guardarCompra() {
            const supplierId  = document.getElementById('supplier_id').value;
            const fechaCompra = document.getElementById('fecha_compra').value;
            const descripcion = document.getElementById('descripcion').value;
            const metodoPago  = document.getElementById('metodo_pago').value;
            const montoPagado = parseFloat(document.getElementById('monto_pagado').value || '0');
            const referencia  = document.getElementById('referencia').value;
            const observ      = document.getElementById('observaciones').value;

            if (!supplierId || !fechaCompra || !metodoPago) {
                if (window.Swal) {
                    Swal.fire('Datos incompletos', 'Proveedor, fecha y método de pago son obligatorios.', 'warning');
                } else {
                    alert('Proveedor, fecha y método de pago son obligatorios.');
                }
                return;
            }

            if (!ITEMS.length) {
                if (window.Swal) {
                    Swal.fire('Sin ítems', 'Debes agregar al menos un ítem a la compra.', 'warning');
                } else {
                    alert('Debes agregar al menos un ítem.');
                }
                return;
            }

            if (montoPagado <= 0) {
                if (window.Swal) {
                    Swal.fire('Monto inválido', 'El monto pagado debe ser mayor a 0.', 'warning');
                } else {
                    alert('El monto pagado debe ser mayor a 0.');
                }
                return;
            }

            const payload = {
                supplier_id:    parseInt(supplierId),
                fecha_compra:   fechaCompra,
                descripcion:    descripcion || null,
                metodo_pago:    metodoPago,
                pago_inicial:   montoPagado,          // se registra 100% pagado
                referencia:     referencia || null,
                observaciones:  observ || null,
                iva_porcentaje: IVA_RATE,
                items: ITEMS.map(it => ({
                    producto_id:    it.producto_id,
                    bodega_id:      it.bodega_id,
                    percha_id:      it.percha_id,
                    cantidad:       it.cantidad,
                    costo_unitario: it.costo_unitario
                }))
            };

            try {
                const resp = await fetch(STORE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });

                if (!resp.ok) {
                    const error = await resp.json().catch(() => ({}));
                    throw error;
                }

                await resp.json(); // no lo usamos, solo aseguramos que fue válido

                if (window.Swal) {
                    await Swal.fire('Compra registrada', 'La compra se guardó correctamente.', 'success');
                } else {
                    alert('Compra registrada correctamente.');
                }

                window.location.href = "{{ route('compras.index') }}";

            } catch (err) {
                console.error(err);
                let msg = 'Error al guardar la compra.';
                if (err && err.message) msg = err.message;
                if (window.Swal) {
                    Swal.fire('Error', msg, 'error');
                } else {
                    alert(msg);
                }
            }
        }
    </script>

</x-app-layout>
