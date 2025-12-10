<div
    id="change-modal"
    class="fixed inset-0 z-50 hidden"
    aria-hidden="true"
>
    <div class="flex items-center justify-center min-h-screen bg-black/40">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
            <div class="px-4 py-3 border-b flex justify-between items-center">
                <h3 class="text-sm font-semibold text-gray-800">
                    Detalle del cambio
                </h3>
                <button
                    type="button"
                    class="text-gray-400 hover:text-gray-600 text-xl leading-none"
                    data-change-close
                >
                    &times;
                </button>
            </div>

            <div class="p-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-xs text-gray-500 uppercase">Total de la venta</span>
                    <span id="change_total" class="font-semibold text-gray-900">$ 0.00</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-xs text-gray-500 uppercase">Monto recibido</span>
                    <span id="change_recibido" class="font-semibold text-gray-900">$ 0.00</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-xs text-gray-500 uppercase">Cambio a entregar</span>
                    <span id="change_cambio" class="font-semibold text-green-700 text-lg">$ 0.00</span>
                </div>

                <p class="text-[11px] text-gray-400 mt-2">
                    Entrega el cambio en efectivo según tu disponibilidad de billetes y monedas.
                </p>
            </div>

            <div class="px-4 py-3 border-t flex justify-end">
                <button
                    type="button"
                    data-change-close
                    class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700"
                >
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
