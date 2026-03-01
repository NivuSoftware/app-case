<div
    id="client-modal"
    class="fixed inset-0 z-50 hidden"
    aria-hidden="true"
>
    <div class="flex items-center justify-center min-h-screen bg-black/40">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl">
            <div class="px-4 py-3 border-b flex justify-between items-center">
                <h3 class="text-sm font-semibold text-gray-800">
                    Buscar cliente
                </h3>
                <button
                    type="button"
                    class="text-gray-400 hover:text-gray-600 text-xl leading-none"
                    data-client-close
                >
                    &times;
                </button>
            </div>

            <div class="p-4 space-y-4 text-sm">
                <div class="flex flex-col space-y-1">
                    <label class="text-xs text-gray-500 uppercase tracking-wide">
                        Buscar por nombre, identificación o RUC
                    </label>
                    <input
                        type="text"
                        id="client_search_term"
                        class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                        placeholder="Ej: Juan, 1720..., 0999..."
                    >
                </div>

                <div class="border border-gray-200 rounded-md max-h-72 overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-gray-500 uppercase">Cliente</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-500 uppercase">Identificación</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-500 uppercase">Tipo</th>
                                <th class="px-3 py-2 text-center font-semibold text-gray-500 uppercase">Seleccionar</th>
                            </tr>
                        </thead>
                        <tbody id="client_results" class="divide-y divide-gray-100 bg-white">
                            {{-- Filas dinámicas vía JS --}}
                        </tbody>
                    </table>

                    <div id="client_results_empty" class="p-3 text-center text-[11px] text-gray-400">
                        Escribe para buscar clientes.
                    </div>
                </div>
            </div>

            <div class="px-4 py-3 border-t flex justify-end">
                <button
                    type="button"
                    data-client-close
                    class="px-4 py-2 text-xs font-semibold text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50"
                >
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
