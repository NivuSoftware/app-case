<div
    id="split-payment-modal"
    class="fixed inset-0 z-50 hidden"
    aria-hidden="true"
>
    <div class="flex items-center justify-center min-h-screen bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl border border-slate-100 overflow-hidden">
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

            <div class="p-5 space-y-4 text-sm max-h-[80vh] overflow-y-auto">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3">
                        <div class="text-[11px] text-slate-500 uppercase font-semibold">Total a cobrar</div>
                        <div id="split_payment_total" class="text-2xl font-bold text-blue-700 mt-1">$ 0.00</div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                        <div class="text-[11px] text-slate-500 uppercase font-semibold">Correo de envío</div>
                        <div id="split_payment_email_preview" class="text-sm font-medium text-slate-800 mt-1 break-all">
                            (sin correo seleccionado)
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2">
                    <p class="text-[11px] text-slate-500 uppercase font-semibold">
                        Formas de pago
                    </p>
                    <button
                        type="button"
                        id="btn-add-split-payment-row"
                        class="inline-flex items-center justify-center px-2.5 py-1 rounded-xl border border-dashed border-slate-300 text-[10px] font-semibold uppercase tracking-wide text-slate-700 hover:bg-slate-50"
                    >
                        + Agregar método
                    </button>
                </div>

                <div id="split_payment_rows" class="space-y-2"></div>

                <template id="split-payment-row-template">
                    <div data-split-payment-row class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm space-y-3">
                        <div class="flex justify-end">
                            <button
                                type="button"
                                data-remove-split-payment-row
                                class="px-2 py-1 rounded-lg text-[10px] font-semibold uppercase tracking-wide text-rose-600 hover:bg-rose-50"
                            >
                                Eliminar
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div class="flex flex-col space-y-1">
                                <label class="text-[10px] text-slate-400 uppercase font-semibold">
                                    Método de pago
                                </label>
                                <select
                                    data-split-payment-method
                                    class="border-slate-200 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 text-[11px] h-8 leading-tight"
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
                                    Monto ($)
                                </label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    data-split-payment-amount
                                    class="border-slate-200 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm h-8"
                                    placeholder="0.00"
                                >
                            </div>

                            <div class="flex flex-col space-y-1">
                                <label class="text-[10px] text-slate-400 uppercase font-semibold">
                                    Referencia
                                </label>
                                <input
                                    type="text"
                                    data-split-payment-reference
                                    class="border-slate-200 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 text-xs h-8"
                                    placeholder="Nro. voucher..."
                                >
                            </div>

                            <div class="flex flex-col space-y-1">
                                <label class="text-[10px] text-slate-400 uppercase font-semibold">
                                    Observaciones del pago
                                </label>
                                <input
                                    type="text"
                                    data-split-payment-observations
                                    class="border-slate-200 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 text-xs h-8"
                                    placeholder="Notas del pago"
                                >
                            </div>
                        </div>

                    </div>
                </template>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="grid grid-cols-1 sm:grid-cols-[auto_auto_1fr] gap-2 sm:gap-4 items-start sm:items-center">
                        <div class="flex items-center justify-between sm:block text-[11px] text-slate-600">
                            <span class="block">Total declarado</span>
                            <span id="split_payments_total_declared" class="font-semibold text-slate-800">$ 0.00</span>
                        </div>

                        <div class="flex items-center justify-between sm:block text-[11px] text-slate-600">
                            <span class="block">Balance</span>
                            <span id="split_payments_balance" class="font-semibold text-amber-600">$ 0.00</span>
                        </div>

                        <p id="split_payments_status_message" class="text-[11px] font-medium text-amber-600 sm:text-right">
                            Agrega y completa los pagos para emitir la factura.
                        </p>
                    </div>
                </div>
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
