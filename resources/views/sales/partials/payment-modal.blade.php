<div
    id="payment-modal"
    class="fixed inset-0 z-50 hidden"
    aria-hidden="true"
>
    <div class="flex items-center justify-center min-h-screen bg-black/40">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
            <div class="px-4 py-3 border-b flex justify-between items-center">
                <h3 class="text-sm font-semibold text-gray-800">
                    Registrar pago
                </h3>
                <button
                    type="button"
                    class="text-gray-400 hover:text-gray-600 text-xl leading-none"
                    data-payment-close
                >
                    &times;
                </button>
            </div>

            <div class="p-4 space-y-4 text-sm">
                {{-- Total y correo --}}
                <div class="space-y-1">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500 uppercase">Total a cobrar</span>
                        <span id="payment_modal_total" class="text-xl font-bold text-blue-700">
                            $ 0.00
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500 uppercase">Correo de envío</span>
                        <span id="payment_modal_email_preview" class="text-xs font-medium text-gray-800">
                            (sin correo seleccionado)
                        </span>
                    </div>
                </div>

                {{-- Método de pago --}}
                <div class="flex flex-col space-y-1">
                    <label class="text-xs text-gray-500 uppercase tracking-wide">Método de pago</label>
                    <select
                        id="payment_modal_metodo"
                        class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                    >
                        @foreach($paymentMethods as $pm)
                            <option value="{{ $pm->nombre }}" data-id="{{ $pm->id }}">
                                {{ $pm->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Monto recibido --}}
                <div class="flex flex-col space-y-1">
                    <label class="text-xs text-gray-500 uppercase tracking-wide">Monto recibido</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        id="payment_modal_monto_recibido"
                        class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                        value="0"
                    >
                </div>

                {{-- Vuelto --}}
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-500 uppercase tracking-wide">Vuelto</span>
                    <span id="payment_modal_cambio" class="text-lg font-semibold text-green-700">
                        $ 0.00
                    </span>
                </div>

                {{-- Referencia y observaciones --}}
                <div class="flex flex-col space-y-1">
                    <label class="text-xs text-gray-500 uppercase tracking-wide">Referencia (opcional)</label>
                    <input
                        type="text"
                        id="payment_modal_referencia"
                        class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                        placeholder="Número de voucher, transacción, etc."
                    >
                </div>

                <div class="flex flex-col space-y-1">
                    <label class="text-xs text-gray-500 uppercase tracking-wide">Observaciones (opcional)</label>
                    <textarea
                        id="payment_modal_observaciones"
                        rows="2"
                        class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                        placeholder="Notas del pago"
                    ></textarea>
                </div>
            </div>

            <div class="px-4 py-3 border-t flex justify-end gap-2">
                <button
                    type="button"
                    class="px-4 py-2 text-xs font-semibold text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50"
                    data-payment-close
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    id="btn-confirm-payment"
                    class="px-4 py-2 text-xs font-semibold text-white bg-green-600 rounded-md hover:bg-green-700"
                >
                    Aceptar y emitir factura
                </button>
            </div>
        </div>
    </div>
</div>
