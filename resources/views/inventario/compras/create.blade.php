<x-app-layout>

    {{-- HEADER --}}
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-blue-900 leading-tight">
                {{ __('Registrar Compra a Proveedor') }}
            </h2>

            <button onclick="window.location.href='{{ route('compras.index') }}'"
                class="text-blue-700 hover:text-blue-900 transition flex items-center">
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
                                Proveedor *
                            </label>
                            <select id="supplier_id" required class="border rounded w-full px-3 py-2 text-sm">
                                <option value="">-- Seleccione --</option>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Fecha de compra *
                            </label>
                            <input id="fecha_compra" type="date" value="{{ now()->toDateString() }}" required class="border rounded w-full px-3 py-2 text-sm">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Descripción / Nota *</label>
                            <input id="descripcion" type="text" required class="border rounded w-full px-3 py-2 text-sm"
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
                                Método de pago *
                            </label>
                            <select id="metodo_pago" required class="border rounded w-full px-3 py-2 text-sm">
                                <option value="">-- Seleccione --</option>
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
                                class="border rounded w-full px-3 py-2 text-sm bg-gray-100" placeholder="0.00" readonly>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Referencia (opcional)
                            </label>
                            <input id="referencia" type="text" class="border rounded w-full px-3 py-2 text-sm"
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
                    <button onclick="agregarItem()"
                        class="bg-blue-700 hover:bg-blue-800 text-white px-4 py-2 rounded text-sm flex items-center">
                        <x-heroicon-s-plus class="w-4 h-4 mr-1" /> Agregar ítem
                    </button>
                </div>

                {{-- Fila de captura rápida --}}
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end border-b pb-4 mb-4">
                    <div class="relative">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            Producto
                        </label>
                        {{-- Input de búsqueda --}}
                        <input type="text" id="product_search" class="border rounded w-full px-2 py-1 text-xs"
                            placeholder="Buscar por nombre o código..." oninput="buscarProducto(this.value)">

                        {{-- ID seleccionado (oculto) --}}
                        <input type="hidden" id="c-producto">

                        {{-- Lista de resultados --}}
                        <ul id="search-results"
                            class="absolute z-10 bg-white border border-gray-300 w-full max-h-48 overflow-y-auto hidden shadow-lg text-xs">
                            {{-- Se llena por JS --}}
                        </ul>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            Bodega
                        </label>
                        <select id="c-bodega" onchange="filtrarPerchasCaptura()"
                            class="border rounded w-full px-2 py-1 text-xs">
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
                        {{-- Inicia vacío o deshabilitado hasta elegir bodega --}}
                        <select id="c-percha" class="border rounded w-full px-2 py-1 text-xs">
                            <option value="">-- Seleccione Bodega primero --</option>
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
                            onkeydown="handleCostoUnitarioEnter(event)"
                            class="border rounded w-full px-2 py-1 text-xs" />
                    </div>

                </div>

                {{-- TABLA DE ÍTEMS --}}
                <div class="overflow-x-auto min-h-[150px]">
                    <table class="min-w-full text-xs">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 uppercase text-[11px]">
                                <th class="px-2 py-2 text-left w-1/4">Producto</th>
                                <th class="px-2 py-2 text-left w-1/6">Bodega</th>
                                <th class="px-2 py-2 text-left w-1/6">Percha</th>
                                <th class="px-2 py-2 text-right w-20">Cant.</th>
                                <th class="px-2 py-2 text-right w-24">Costo unit.</th>
                                <th class="px-2 py-2 text-center w-20">IVA 15%</th>
                                <th class="px-2 py-2 text-right w-24">Subtotal</th>
                                <th class="px-2 py-2 text-center w-16">Acciones</th>
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
                            <span id="lbl-iva-title">IVA (15%):</span>
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
                <button onclick="guardarCompra()"
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded shadow font-semibold">
                    Guardar compra
                </button>
            </div>

        </div>
    </div>

    @php
        $jsProducts = $products->map(fn($p) => [
            'id' => $p->id,
            'nombre' => $p->nombre,
            'codigo_interno' => $p->codigo_interno,
            'codigo_barras' => $p->codigo_barras
        ]);
        $jsBodegas = $bodegas->map(fn($b) => ['id' => $b->id, 'nombre' => $b->nombre]);
        $jsPerchas = $perchas->map(fn($per) => [
            'id' => $per->id,
            'codigo' => $per->codigo,
            'bodega_id' => $per->bodega_id
        ]);
    @endphp

    <script>
        const PRODUCTS = @json($jsProducts);
        const BODEGAS = @json($jsBodegas);
        const PERCHAS = @json($jsPerchas);

        let ITEMS = [];

        const STORE_URL = "{{ route('compras.store') }}"; // /inventario/compras
        const IVA_RATE = 0.15; // 15% fijo

        const formatter = new Intl.NumberFormat('es-EC', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2
        });

        function agregarItem() {
            const productoId = document.getElementById('c-producto').value;
            const bodegaId = document.getElementById('c-bodega').value;
            const perchaId = document.getElementById('c-percha').value || null;
            const cantidad = parseInt(document.getElementById('c-cantidad').value, 10);
            const costo = parseFloat(document.getElementById('c-costo').value);

            if (!productoId || !bodegaId || !cantidad || !costo) {
                if (window.Swal) {
                    Swal.fire('Datos incompletos', 'Producto, bodega, cantidad y costo son obligatorios.', 'warning');
                } else {
                    alert('Producto, bodega, cantidad y costo son obligatorios.');
                }
                return;
            }

            const prod = PRODUCTS.find(p => p.id == productoId);
            const bod = BODEGAS.find(b => b.id == bodegaId);
            const per = PERCHAS.find(p => p.id == perchaId);

            const item = {
                producto_id: parseInt(productoId),
                producto_nombre: prod ? prod.nombre : '',
                bodega_id: parseInt(bodegaId),
                bodega_nombre: bod ? bod.nombre : '',
                percha_id: perchaId ? parseInt(perchaId) : null,
                percha_codigo: per ? per.codigo : '',
                cantidad: cantidad,
                costo_unitario: costo,
                subtotal: cantidad * costo,
                grava_iva: true,
            };

            ITEMS.push(item);
            renderItems();

            // Limpiar y enfocar buscador
            limpiarCapturaRapida();
            document.getElementById('product_search').focus();
        }

        function handleCostoUnitarioEnter(event) {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            agregarItem();
        }

        /* -----------------------------------------------------
           BÚSQUEDA DE PRODUCTOS (AUTOCOMPLETE)
           ----------------------------------------------------- */
        function buscarProducto(texto) {
            const list = document.getElementById('search-results');
            const hidden = document.getElementById('c-producto');

            // Si está vacío, limpiamos y ocultamos
            if (!texto.trim()) {
                list.innerHTML = '';
                list.classList.add('hidden');
                hidden.value = '';
                return;
            }

            const rawTerm = texto.trim().toLowerCase();
            const compactTerm = rawTerm.replace(/\s+/g, '');
            const digitsOnly = compactTerm.replace(/\D/g, '');

            // Escáneres pueden inyectar prefijos (ej: letras) o dañar el primer carácter.
            // Probamos varias formas del término antes de mostrar sugerencias.
            const candidates = new Set([compactTerm]);
            if (digitsOnly) candidates.add(digitsOnly);
            if (compactTerm.length > 1 && /^\D\d+$/.test(compactTerm)) {
                candidates.add(compactTerm.slice(1));
            }

            let exactCodeMatch = null;
            for (const c of candidates) {
                exactCodeMatch = PRODUCTS.find(p => {
                    const code = (p.codigo_interno || '').trim().toLowerCase();
                    const bar = (p.codigo_barras || '').trim().toLowerCase();
                    return code === c || bar === c;
                });
                if (exactCodeMatch) break;
            }

            // Fallback para scanner: primer carácter corrupto, pero resto correcto.
            if (!exactCodeMatch && compactTerm.length > 1 && /^\D\d+$/.test(compactTerm)) {
                const suffix = compactTerm.slice(1);
                const matchesBySuffix = PRODUCTS.filter(p => {
                    const bar = (p.codigo_barras || '').trim().toLowerCase();
                    return bar.endsWith(suffix);
                });
                if (matchesBySuffix.length === 1) {
                    exactCodeMatch = matchesBySuffix[0];
                }
            }

            if (exactCodeMatch) {
                seleccionarProducto(exactCodeMatch);
                return;
            }

            // Filtrar productos por nombre, cod interno o cod barras
            const results = PRODUCTS.filter(p => {
                const name = (p.nombre || '').toLowerCase();
                const code = (p.codigo_interno || '').toLowerCase();
                const bar = (p.codigo_barras || '').toLowerCase();
                return (
                    name.includes(rawTerm) ||
                    code.includes(compactTerm) ||
                    bar.includes(compactTerm) ||
                    (digitsOnly && (code.includes(digitsOnly) || bar.includes(digitsOnly)))
                );
            }).slice(0, 10); // Limitar a 10 resultados para no saturar

            list.innerHTML = '';
            if (results.length === 0) {
                const li = document.createElement('li');
                li.className = "px-3 py-2 text-gray-500 cursor-default";
                li.textContent = "No encontrado";
                list.appendChild(li);
            } else {
                results.forEach(res => {
                    const li = document.createElement('li');
                    li.className = "px-3 py-2 hover:bg-blue-100 cursor-pointer border-b last:border-0";
                    li.textContent = `${res.nombre} | ${res.codigo_interno || '--'} | ${res.codigo_barras || '--'}`;
                    li.onclick = () => seleccionarProducto(res);
                    list.appendChild(li);
                });
            }
            list.classList.remove('hidden');
        }

        function seleccionarProducto(prod) {
            const input = document.getElementById('product_search');
            const hidden = document.getElementById('c-producto');
            const list = document.getElementById('search-results');

            input.value = prod.nombre;
            hidden.value = prod.id;
            list.classList.add('hidden');
        }

        /* -----------------------------------------------------
           FILTRADO DE PERCHAS (Captura Rápida)
           ----------------------------------------------------- */
        function filtrarPerchasCaptura() {
            const bodegaId = document.getElementById('c-bodega').value;
            const selectPer = document.getElementById('c-percha');

            // Limpiar opciones actuales
            selectPer.innerHTML = '<option value="">-- Ninguna --</option>';

            if (!bodegaId) {
                selectPer.innerHTML = '<option value="">-- Seleccione Bodega --</option>';
                return;
            }

            // Filtrar y llenar
            const validas = PERCHAS.filter(p => p.bodega_id == bodegaId);
            if (validas.length === 0) {
                const opt = document.createElement('option');
                opt.value = "";
                opt.textContent = "-- Sin perchas --";
                selectPer.appendChild(opt);
            } else {
                validas.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = p.codigo;
                    selectPer.appendChild(opt);
                });
            }
        }

        function limpiarCapturaRapida() {
            document.getElementById('product_search').value = '';
            document.getElementById('c-producto').value = '';
            document.getElementById('search-results').classList.add('hidden');

            document.getElementById('c-bodega').value = '';
            // Resetear percha
            const selectPer = document.getElementById('c-percha');
            selectPer.value = '';
            selectPer.innerHTML = '<option value="">-- Seleccione Bodega --</option>';

            document.getElementById('c-cantidad').value = '';
            document.getElementById('c-costo').value = '';
        }

        function renderItems() {
            const tbody = document.getElementById('tbody-items');
            tbody.innerHTML = '';

            ITEMS.forEach((it, idx) => {
                const tr = document.createElement('tr');
                tr.className = 'border-b last:border-0';

                /*
                  Creamos inputs para edición en línea.
                  Usamos onchange para detectar cambios y actualizar el array.
                */

                // Options para Bodegas
                let bodegasOpts = '';
                BODEGAS.forEach(b => {
                    const sel = (b.id == it.bodega_id) ? 'selected' : '';
                    bodegasOpts += `<option value="${b.id}" ${sel}>${b.nombre}</option>`;
                });

                // Options para Perchas (filtradas por la bodega ACTUAL del item)
                let perchasOpts = '<option value="">—</option>';
                const perchasValidas = PERCHAS.filter(p => p.bodega_id == it.bodega_id);
                perchasValidas.forEach(p => {
                    const sel = (p.id == it.percha_id) ? 'selected' : '';
                    perchasOpts += `<option value="${p.id}" ${sel}>${p.codigo}</option>`;
                });

                tr.innerHTML = `
                    <td class="px-2 py-2">
                        <span class="block truncate" title="${it.producto_nombre}">${it.producto_nombre}</span>
                    </td>
                    <td class="px-2 py-2">
                        <select onchange="updateItem(${idx}, 'bodega_id', this.value)"
                                class="border rounded w-full px-1 py-1 text-xs">
                           ${bodegasOpts}
                        </select>
                    </td>
                    <td class="px-2 py-2">
                        <select onchange="updateItem(${idx}, 'percha_id', this.value)"
                                class="border rounded w-full px-1 py-1 text-xs">
                           ${perchasOpts}
                        </select>
                    </td>
                    <td class="px-2 py-2 text-right">
                        <input type="number" min="1" step="1"
                               value="${it.cantidad}"
                               onchange="updateItem(${idx}, 'cantidad', this.value)"
                               class="border rounded w-full px-1 py-1 text-xs text-right">
                    </td>
                    <td class="px-2 py-2 text-right">
                        <input type="number" min="0" step="0.0001"
                               value="${it.costo_unitario}"
                               onchange="updateItem(${idx}, 'costo_unitario', this.value)"
                               class="border rounded w-full px-1 py-1 text-xs text-right">
                    </td>
                    <td class="px-2 py-2 text-center">
                        <input type="checkbox"
                               ${it.grava_iva ? 'checked' : ''}
                               onchange="updateItem(${idx}, 'grava_iva', this.checked ? 1 : 0)"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </td>
                    <td class="px-2 py-2 text-right font-semibold">
                        ${formatter.format(it.subtotal)}
                    </td>
                    <td class="px-2 py-2 text-center">
                        <button
                            class="text-red-600 hover:text-red-800 text-xs"
                            onclick="eliminarItem(${idx})">
                            <x-heroicon-s-trash class="w-4 h-4 mx-auto" />
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            recalcularTotales();
        }

        /* -----------------------------------------------------
           EDICIÓN EN LÍNEA
           ----------------------------------------------------- */
        function updateItem(index, field, value) {
            const item = ITEMS[index];

            if (field === 'bodega_id') {
                item.bodega_id = parseInt(value);
                // Si cambiamos de bodega, la percha anterior podría no ser válida
                // Verificamos si la percha actual pertenece a la nueva bodega
                const perchaValida = PERCHAS.find(p => p.id == item.percha_id && p.bodega_id == item.bodega_id);
                if (!perchaValida) {
                    item.percha_id = null; // Reseteamos percha si ya no es válida
                }
                // Obtenemos nombres para refrescar si fuera texto estático (aquí no hace falta pq es select)
            }
            else if (field === 'percha_id') {
                item.percha_id = value ? parseInt(value) : null;
            }
            else if (field === 'cantidad') {
                item.cantidad = parseInt(value) || 0;
            }
            else if (field === 'costo_unitario') {
                item.costo_unitario = parseFloat(value) || 0;
            }
            else if (field === 'grava_iva') {
                item.grava_iva = value === true || value === 1 || value === '1';
            }

            // Recalcular subtotal
            item.subtotal = item.cantidad * item.costo_unitario;

            // Re-renderizar (importante para actualizar selects de perchas si cambió bodega, o subtotales)
            renderItems();
        }

        function eliminarItem(index) {
            ITEMS.splice(index, 1);
            renderItems();
        }

        function recalcularTotales() {
            const subtotal = ITEMS.reduce((acc, it) => acc + it.subtotal, 0);
            const iva = ITEMS.reduce((acc, it) => {
                return acc + ((it.grava_iva ? it.subtotal : 0) * IVA_RATE);
            }, 0);
            const total = subtotal + iva;
            const hayItemsConIva = ITEMS.some(it => !!it.grava_iva);

            const lblSubtotal = document.getElementById('lbl-subtotal');
            const lblIva = document.getElementById('lbl-iva');
            const lblIvaTitle = document.getElementById('lbl-iva-title');
            const lblTotal = document.getElementById('lbl-total');
            const lblMontoPagado = document.getElementById('lbl-monto-pagado');
            const inputMonto = document.getElementById('monto_pagado');

            if (lblSubtotal) lblSubtotal.textContent = formatter.format(subtotal);
            if (lblIva) lblIva.textContent = formatter.format(iva);
            if (lblIvaTitle) lblIvaTitle.textContent = hayItemsConIva ? 'IVA (15%):' : 'IVA (0%):';
            if (lblTotal) lblTotal.textContent = formatter.format(total);
            if (lblMontoPagado) lblMontoPagado.textContent = formatter.format(total);

            if (inputMonto) {
                inputMonto.value = total.toFixed(2);
            }
        }

        async function guardarCompra() {
            const supplierId = document.getElementById('supplier_id').value;
            const fechaCompra = document.getElementById('fecha_compra').value;
            const descripcion = document.getElementById('descripcion').value.trim();
            const metodoPago = document.getElementById('metodo_pago').value;
            const montoPagado = parseFloat(document.getElementById('monto_pagado').value || '0');
            const referencia = document.getElementById('referencia').value;
            const observ = document.getElementById('observaciones').value;

            if (!supplierId || !fechaCompra || !descripcion || !metodoPago) {
                if (window.Swal) {
                    Swal.fire('Datos incompletos', 'Proveedor, fecha, descripción y método de pago son obligatorios.', 'warning');
                } else {
                    alert('Proveedor, fecha, descripción y método de pago son obligatorios.');
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
                supplier_id: parseInt(supplierId),
                fecha_compra: fechaCompra,
                descripcion: descripcion,
                metodo_pago: metodoPago,
                pago_inicial: montoPagado,          // se registra 100% pagado
                referencia: referencia || null,
                observaciones: observ || null,
                iva_porcentaje: IVA_RATE,
                items: ITEMS.map(it => ({
                    producto_id: it.producto_id,
                    bodega_id: it.bodega_id,
                    percha_id: it.percha_id,
                    cantidad: it.cantidad,
                    costo_unitario: it.costo_unitario,
                    grava_iva: !!it.grava_iva,
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



