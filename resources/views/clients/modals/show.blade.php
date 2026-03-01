<div
    id="viewClientModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40"
>
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg mx-4">
        {{-- HEADER --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-800">
                Detalle del Cliente
            </h3>
            <button
                type="button"
                onclick="closeViewModal()"
                class="text-gray-400 hover:text-gray-600"
            >
                ✕
            </button>
        </div>

        {{-- BODY --}}
        <div class="px-4 py-4 space-y-3 text-sm">
            <div>
                <span class="block text-xs font-semibold text-gray-500">
                    Identificación
                </span>
                <span
                    class="text-gray-900 font-mono"
                    data-field="identificacion"
                >
                    <!-- Se llena por JS -->
                </span>
            </div>

            <div>
                <span class="block text-xs font-semibold text-gray-500">
                    Nombres / Razón social
                </span>
                <span
                    class="text-gray-900"
                    data-field="business"
                >
                    <!-- Se llena por JS -->
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <span class="block text-xs font-semibold text-gray-500">
                        Tipo de cliente
                    </span>
                    <span
                        class="text-gray-900"
                        data-field="tipo"
                    >
                        <!-- Se llena por JS -->
                    </span>
                </div>

                <div>
                    <span class="block text-xs font-semibold text-gray-500">
                        Estado
                    </span>
                    <span
                        class="text-gray-900"
                        data-field="estado"
                    >
                        <!-- Se llena por JS -->
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <span class="block text-xs font-semibold text-gray-500">
                        Teléfono
                    </span>
                    <span
                        class="text-gray-900"
                        data-field="telefono"
                    >
                        <!-- Se llena por JS -->
                    </span>
                </div>

                <div>
                    <span class="block text-xs font-semibold text-gray-500">
                        Ciudad
                    </span>
                    <span
                        class="text-gray-900"
                        data-field="ciudad"
                    >
                        <!-- Se llena por JS -->
                    </span>
                </div>
            </div>

            <div>
                <span class="block text-xs font-semibold text-gray-500">
                    Dirección
                </span>
                <span
                    class="text-gray-900"
                    data-field="direccion"
                >
                    <!-- Se llena por JS -->
                </span>
            </div>

            <div>
                <span class="block text-xs font-semibold text-gray-500 mb-1">
                    Emails
                </span>
                <ul
                    class="list-disc list-inside text-xs text-gray-800 space-y-1"
                    data-field="emails"
                >
                    {{-- JS agregará <li> con cada correo --}}
                </ul>
            </div>
        </div>

        {{-- FOOTER --}}
        <div class="px-4 py-3 border-t border-gray-100 flex justify-end">
            <button
                type="button"
                onclick="closeViewModal()"
                class="px-4 py-2 text-xs rounded-md border border-gray-300 text-gray-700 hover:bg-gray-100"
            >
                Cerrar
            </button>
        </div>
    </div>
</div>
