<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-slate-900 leading-tight">
                {{ __('Ventas / Facturación') }}
            </h2>

            <button
                onclick="window.location.href='{{ route('dashboard') }}'"
                class="text-slate-600 hover:text-slate-900 transition flex items-center space-x-1 text-sm"
                title="Regresar"
            >
                <x-heroicon-s-arrow-left class="w-5 h-5" />
                <span>Atrás</span>
            </button>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto px-3 lg:px-4">

            {{-- ALERTA --}}
            <div id="sale-alert" class="hidden mb-3">
                <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800 flex justify-between items-center shadow-sm">
                    <span id="sale-alert-message"></span>
                    <button
                        type="button"
                        onclick="window.SalesUtils?.hideSaleAlert && window.SalesUtils.hideSaleAlert()"
                        class="ml-4 text-emerald-700 hover:text-emerald-900 text-lg leading-none"
                    >
                        &times;
                    </button>
                </div>
            </div>

            {{-- LAYOUT POS: DOS COLUMNAS --}}
            <div class="flex flex-col lg:flex-row gap-4 h-[calc(100vh-8rem)] min-h-[620px]">

                {{-- =============== COL IZQUIERDA: BARRA SUPERIOR + CLIENTE + PRODUCTOS =============== --}}
                <section class="flex-[1.6] flex flex-col gap-4 min-h-0">

                    {{-- Barra superior --}}
                    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm px-4 py-3">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div class="flex flex-col space-y-1">
                                <label class="text-[11px] tracking-wide font-semibold text-slate-500 uppercase">
                                    Tipo de documento
                                </label>
                                <select
                                    id="tipo_documento"
                                    class="border-slate-200 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm h-10 bg-slate-50"
                                >
                                    <option value="FACTURA">Factura electrónica</option>
                                </select>
                            </div>

                            <div class="flex flex-col space-y-1">
                                <label class="text-[11px] tracking-wide font-semibold text-slate-500 uppercase">
                                    Fecha de venta
                                </label>

                                {{-- Muestra solo la fecha (no editable) --}}
                                <div class="border border-slate-200 rounded-xl bg-slate-50 text-sm h-10 px-3 flex items-center text-slate-800">
                                    {{ now()->format('d/m/Y') }}
                                </div>

                                {{-- Campo hidden para que JS/API sigan usando fecha_venta --}}
                                <input
                                    type="hidden"
                                    id="fecha_venta"
                                    value="{{ now()->format('Y-m-d\TH:i') }}"
                                >
                            </div>


                            <div class="flex flex-col space-y-1">
                                <label class="text-[11px] tracking-wide font-semibold text-slate-500 uppercase">
                                    Bodega
                                </label>

                                <div class="border border-slate-200 rounded-xl bg-slate-50 text-sm h-10 px-3 flex items-center text-slate-800">
                                    {{ $bodegaSelected->nombre }}
                                </div>

                                <input type="hidden" id="bodega_id" value="{{ $bodegaSelected->id }}">
                            </div>


                            <div class="flex flex-col space-y-1">
                                <label class="text-[11px] tracking-wide font-semibold text-slate-500 uppercase">
                                    N° documento (opcional)
                                </label>
                                <input
                                    type="text"
                                    id="num_factura"
                                    class="border-slate-200 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm h-10"
                                    placeholder="Se puede autogenerar luego"
                                >
                            </div>
                        </div>
                    </div>

                    {{-- CLIENTE (ahora en la columna izquierda) --}}
                    <div class="bg-white border border-slate-200 rounded-3xl shadow-lg overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-3 bg-slate-100">
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold text-slate-600 uppercase tracking-wide">
                                    Cliente
                                </p>
                                <p id="cliente_nombre"
                                   class="text-sm font-semibold text-slate-900 truncate">
                                    Consumidor final
                                </p>
                                <input type="hidden" id="client_id" value="">
                                <p id="cliente_identificacion" class="text-[11px] text-slate-400 truncate mt-0.5">
                                    Cédula o RUC aquí
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    id="btn-open-client-modal"
                                    class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-blue-600 text-white text-lg font-bold shadow hover:bg-blue-700"
                                    title="Agregar / seleccionar cliente"
                                >
                                    +
                                </button>
                                <button
                                    type="button"
                                    id="btn-search-client"
                                    class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-slate-900 text-white shadow hover:bg-black"
                                    title="Buscar cliente"
                                >
                                    <x-heroicon-s-magnifying-glass class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="px-5 pb-3 pt-2 bg-white">
                            <p class="text-[12px] font-semibold text-slate-600 mb-1">
                                Correo para enviar factura
                            </p>
                            <select
                                id="cliente_email"
                                class="w-full border-slate-200 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-xs h-9"
                            >
                                <option value="">Selecciona un correo (opcional)</option>
                            </select>
                            <p id="cliente_email_resumen" class="text-[11px] text-slate-400 mt-1">
                                Sin correo seleccionado
                            </p>
                        </div>
                    </div>

                    {{-- Tarjeta de productos grande --}}
                    <div class="bg-white border border-slate-200 rounded-3xl shadow-lg flex flex-col overflow-hidden flex-1 min-h-0">
                        {{-- HEADER productos --}}
                        <header class="px-5 pt-4 pb-3 border-b border-slate-100">
                            <h3 class="text-sm font-semibold text-slate-900 text-center mb-3">
                                Productos
                            </h3>

                            <div class="flex items-center gap-2">
                                <div class="flex-1 relative">
                                    <label class="block text-[11px] text-slate-500 font-semibold uppercase mb-1">
                                        Buscar productos
                                    </label>
                                    <input
                                        type="text"
                                        id="item_descripcion"
                                        class="w-full border-slate-200 rounded-full pl-9 pr-3 py-2.5 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Nombre, código interno o código de barras"
                                        autocomplete="off"
                                    >
                                    <span class="absolute left-3 top-[27px] text-slate-400 text-sm">
                                        <x-heroicon-s-magnifying-glass class="w-4 h-4" />
                                    </span>

                                    {{-- Sugerencias --}}
                                    <div
                                        id="product_suggestions"
                                        class="hidden border border-slate-200 bg-white rounded-xl shadow-xl text-xs max-h-56 overflow-y-auto mt-1 z-20"
                                    ></div>
                                </div>
                            </div>

                            {{-- Inputs ocultos para JS actual --}}
                            <div class="hidden">
                                <input type="number" id="item_cantidad" value="1" min="1">
                                <input type="number" id="item_precio_unitario" step="0.01" min="0">
                                <input type="number" id="item_descuento" step="0.01" min="0" value="0">
                                <button type="button" id="btn-add-item"></button>
                            </div>
                        </header>

                        {{-- LISTA SCROLLABLE --}}
                        <div class="flex-1 px-5 pb-5 pt-3 min-h-0">
                            <div
                                class="w-full h-full border border-slate-100 rounded-2xl bg-slate-50/80 overflow-y-auto"
                            >
                                <div
                                    id="product_list"
                                    data-product-url="{{ url('/productos/list') }}"
                                    class="divide-y divide-slate-100 text-sm"
                                >
                                    {{-- Render de productos --}}
                                </div>

                                <div id="product_list_empty" class="py-6 flex items-center justify-center">
                                    <p class="text-[13px] text-slate-400 text-center px-6">
                                        LISTA DE LOS PRODUCTOS. Escribe en el buscador para comenzar a buscar y agregar al carrito.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- =============== COL DERECHA: SOLO CARRITO (MÁS ALTO) =============== --}}
                <section class="flex-[1.2] flex flex-col">
                    <div class="flex-1 bg-white border border-slate-200 rounded-3xl shadow-lg flex flex-col overflow-hidden">

                        {{-- CABECERA --}}
                        <header class="px-5 py-3 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <p class="text-[11px] text-slate-500 uppercase font-semibold">
                                    Carrito
                                </p>
                                <p class="text-[11px] text-slate-400">
                                    Detalle de la venta actual
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-[11px] text-slate-500 uppercase font-semibold">Total</p>
                                <p id="resumen-total" class="text-2xl font-bold text-blue-700">$ 0.00</p>
                            </div>
                        </header>

                        {{-- LISTA DE ÍTEMS (MUCHO MÁS ALTA) --}}
                        <div class="flex-1 overflow-y-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-[13px]">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-500 uppercase text-[10px]">Producto</th>
                                        <th class="px-2 py-2 text-right font-semibold text-slate-500 uppercase text-[10px]">Cantidad</th>
                                        <th class="px-2 py-2 text-right font-semibold text-slate-500 uppercase text-[10px]">P. unitar</th>
                                        <th class="px-2 py-2 text-right font-semibold text-slate-500 uppercase text-[10px]">Descuento</th>
                                        <th class="px-2 py-2 text-right font-semibold text-slate-500 uppercase text-[10px]">Total</th>
                                        <th class="px-2 py-2 text-center font-semibold text-slate-500 uppercase text-[10px]">Acc.</th>
                                    </tr>
                                </thead>
                                <tbody id="cart-body" class="divide-y divide-slate-100 bg-white">
                                    {{-- Filas dinámicas --}}
                                </tbody>
                            </table>

                            <div id="empty-cart-row" class="px-4 py-6 text-center text-[12px] text-slate-400">
                                Lista de los productos añadidos aparecerá aquí.
                            </div>
                        </div>

                        {{-- TOTALES + OBSERVACIONES + COBRAR --}}
                        <footer class="border-t border-slate-100 bg-white px-5 pt-3 pb-4 space-y-3">
                            <div class="space-y-1.5 text-[12px] text-slate-600">
                                <div class="flex justify-between">
                                    <span>Subtotal</span>
                                    <span id="resumen-subtotal">$ 0.00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Descuento</span>
                                    <span id="resumen-descuento">$ 0.00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Impuesto</span>
                                    <span id="resumen-impuesto">$ 0.00</span>
                                </div>
                                <div class="flex items-center justify-between">
                                <label class="inline-flex items-center gap-2 text-[12px] text-slate-700 select-none">
                                    <input
                                        id="toggle_iva_global"
                                        type="checkbox"
                                        checked
                                        class="rounded border-slate-300"
                                    />
                                    Aplicar IVA (15%)
                                </label>

                                <span id="resumen-iva">$ 0.00</span>
                            </div>


                            </div>

                            <div class="flex flex-col space-y-1">
                                <label class="text-[11px] text-slate-500 uppercase font-semibold">
                                    Observaciones
                                </label>
                                <textarea
                                    id="sale_observaciones"
                                    rows="2"
                                    class="border-slate-200 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 text-xs"
                                    placeholder="Notas internas o comentarios para la factura"
                                ></textarea>
                            </div>

                            <button
                                type="button"
                                id="btn-open-payment-modal"
                                class="w-full inline-flex justify-center items-center gap-2 px-4 py-3 bg-emerald-600 border border-transparent rounded-2xl font-semibold text-sm text-white uppercase tracking-wide shadow-xl hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                            >
                                <x-heroicon-s-currency-dollar class="w-5 h-5" />
                                <span>COBRAR</span>
                            </button>
                        </footer>
                    </div>
                </section>
            </div>

            {{-- MODALES --}}
            @include('sales.partials.payment-modal')
            @include('sales.partials.change-modal')
            @include('sales.partials.client-modal')
            @include('clients.modals.create')


        </div>
        <iframe
        id="ticketPrintFrame"
        style="position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;"
        ></iframe>

        <script>
        window.addEventListener('message', (e) => {
            if (e?.data?.type === 'ticket-printed') {
            const f = document.getElementById('ticketPrintFrame');
            if (f) f.src = 'about:blank';
            }
        });
        </script>

    </div>

    <script>
        window.SALES_ROUTES = {
            store: "{{ route('api.ventas.store') }}",
            productSearch: "{{ url('/productos/list') }}",
            clientIndex: "{{ route('clients.index') }}",
            clientEmailsBase: "{{ url('/clients') }}",
        };
        window.CSRF_TOKEN = "{{ csrf_token() }}";

        window.AUTH_USER_ID = @json(auth()->id());

        console.log('[POS] AUTH_USER_ID =', window.AUTH_USER_ID);
    </script>

</x-app-layout>
