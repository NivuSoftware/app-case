<div
    id="split-payment-modal"
    class="fixed inset-0 z-50 hidden"
    aria-hidden="true"
>
    <div class="flex items-center justify-center min-h-screen bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-5xl border border-slate-100 overflow-hidden">
            <div class="px-5 py-4 border-b flex justify-between items-start gap-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-800">
                        Pago split
                    </h3>
                    <p class="text-sm text-slate-500 mt-1">
                        Divide el cobro en varias formas de pago.
                    </p>
                </div>
                
                <button
                    type="button"
                    class="text-gray-400 hover:text-gray-600 text-xl leading-none"
                    data-split-close
                >
                    &times;
                </button>
            </div>

            <div class="p-5 space-y-5 text-sm max-h-[80vh] overflow-y-auto">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
                    <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3">
                        <div class="text-[11px] text-slate-500 uppercase font-semibold">Total a cobrar</div>
                        <div id="split_payment_total" class="text-2xl font-bold text-blue-700 mt-1">$ 0.00</div>
                    </div>

                    <div
                        id="split_payments_balance_card"
                        class="rounded-2xl border border-slate-200 bg-white px-4 py-3"
                    >
                        <div id="split_payments_balance_label" class="text-[11px] text-slate-500 uppercase font-semibold">
                            Faltante
                        </div>
                        <div id="split_payments_balance" class="text-2xl font-bold text-slate-900 mt-1">$ 0.00</div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                        <div class="text-[11px] text-slate-500 uppercase font-semibold">Correo de envío</div>
                        <div id="split_payment_email_preview" class="text-sm font-medium text-slate-800 mt-1 break-all">
                            (sin correo seleccionado)
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-[240px_minmax(0,1fr)] gap-4 items-start">
                    <div class="space-y-3">
                        <div>
                            <p class="text-[11px] text-slate-500 uppercase font-semibold">
                                Formas de pago
                            </p>
                            <p class="text-xs text-slate-400 mt-1">
                                Selecciona un método para agregarlo al split.
                            </p>
                        </div>

                        <div id="split_payment_method_list" class="space-y-2">
                            @foreach($paymentMethods as $pm)
                                <button
                                    type="button"
                                    data-split-method-button
                                    data-method-value="{{ $pm->nombre }}"
                                    class="w-full rounded-2xl border px-4 py-3 text-left text-sm font-semibold transition"
                                >
                                    <span class="flex items-center justify-between gap-3">
                                        <span>{{ mb_convert_case($pm->nombre, MB_CASE_TITLE, 'UTF-8') }}</span>
                                        <span data-split-method-button-icon class="text-lg leading-none text-slate-400">+</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-[11px] text-slate-500 uppercase font-semibold">
                                Detalle del cobro
                            </p>
                            <span id="split_payments_total_declared" class="text-xs font-semibold text-slate-700">
                                Declarado: $ 0.00
                            </span>
                        </div>

                        <div id="split_payment_rows" class="space-y-3"></div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p id="split_payments_status_message" class="text-sm font-medium text-amber-600">
                                Agrega y completa los pagos para emitir la factura.
                            </p>
                        </div>
                    </div>
                </div>

                <template id="split-payment-row-template">
                    <div data-split-payment-row class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                        <div class="grid grid-cols-1 xl:grid-cols-[220px_140px_minmax(0,1fr)_auto] gap-3 items-start">
                            <div class="space-y-1.5">
                                <label class="text-[10px] text-slate-400 uppercase font-semibold">
                                    Forma de pago
                                </label>
                                <div class="rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 flex items-center justify-between gap-3 min-h-[52px]">
                                    <span data-split-payment-method-label class="text-sm font-semibold text-slate-900">
                                        Seleccione...
                                    </span>
                                    <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                        Activo
                                    </span>
                                </div>
                                <select
                                    data-split-payment-method
                                    class="hidden"
                                    tabindex="-1"
                                >
                                    <option value="">Seleccione...</option>
                                    @foreach($paymentMethods as $pm)
                                        <option value="{{ $pm->nombre }}" data-id="{{ $pm->id }}" data-codigo-sri="{{ $pm->codigo_sri }}">
                                            {{ $pm->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex flex-col space-y-1">
                                <label class="text-[10px] text-slate-400 uppercase font-semibold">
                                    Valor
                                </label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    data-split-payment-amount
                                    class="border-slate-200 rounded-2xl shadow-sm focus:ring-blue-500 focus:border-blue-500 text-xl font-semibold h-[52px] text-center"
                                    placeholder="0.00"
                                >
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <div class="flex flex-col space-y-1">
                                    <label class="text-[10px] text-slate-400 uppercase font-semibold">
                                        Referencia
                                    </label>
                                    <input
                                        type="text"
                                        data-split-payment-reference
                                        class="border-slate-200 rounded-2xl shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm h-[52px]"
                                        placeholder="Nro. voucher o transferencia"
                                    >
                                </div>

                                <div class="flex flex-col space-y-1">
                                    <label class="text-[10px] text-slate-400 uppercase font-semibold">
                                        Observación
                                    </label>
                                    <input
                                        type="text"
                                        data-split-payment-observations
                                        class="border-slate-200 rounded-2xl shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm h-[52px]"
                                        placeholder="Detalle del pago"
                                    >
                                </div>
                            </div>

                            <button
                                type="button"
                                data-remove-split-payment-row
                                class="h-[52px] w-11 inline-flex items-center justify-center rounded-2xl border border-rose-200 text-rose-600 hover:bg-rose-50"
                                title="Quitar método"
                            >
                                &times;
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="px-5 py-4 border-t flex justify-end gap-2">
                <button
                    type="button"
                    class="px-4 py-2 text-xs font-semibold text-gray-600 border border-gray-300 rounded-xl hover:bg-gray-50"
                    data-split-close
                >
                    Cancelar
                </button>

                <button
                    type="button"
                    id="btn-confirm-split-payment"
                    class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700"
                >
                    Emitir con pago split
                </button>
            </div>
        </div>
    </div>
</div>
