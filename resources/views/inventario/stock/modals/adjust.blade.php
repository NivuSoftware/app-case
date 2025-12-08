<div 
    id="modal-adjust"
    class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">

    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
        <h2 class="text-xl font-bold text-blue-900 mb-4">
            Ajustar stock
        </h2>

        <p class="mb-2 text-gray-700">
            <strong>Stock actual:</strong>
            <span id="adjust-current" class="font-bold"></span>
        </p>

        <p class="mb-4 text-gray-600 text-sm">
            Ingresa el nuevo valor de stock que quieres que tenga el producto.
            El sistema calculará automáticamente si debe sumar o restar.
        </p>

        <form onsubmit="submitAdjust(event)">
            @csrf

            <div class="mb-4">
                <label for="adjust-nuevo" class="block text-gray-700 mb-1">
                    Nuevo stock
                </label>
                <input 
                    type="number"
                    min="0"
                    id="adjust-nuevo"
                    class="border border-gray-300 rounded w-full px-3 py-2"
                    required
                >
            </div>

            {{-- 🔹 NUEVO: Motivo opcional del ajuste --}}
            <div class="mb-4">
                <label for="adjust-motivo" class="block text-gray-700 mb-1">
                    Motivo del ajuste (opcional)
                </label>
                <textarea
                    id="adjust-motivo"
                    class="border border-gray-300 rounded w-full px-3 py-2 text-sm"
                    rows="3"
                    placeholder="Ej: Diferencia en conteo físico, producto dañado, corrección de carga inicial, etc."></textarea>
            </div>

            <div class="flex justify-end space-x-3">
                <button 
                    type="button"
                    onclick="closeAdjust()"
                    class="px-4 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-100">
                    Cancelar
                </button>

                <button 
                    type="submit"
                    class="px-4 py-2 rounded bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">
                    Guardar ajuste
                </button>
            </div>
        </form>
    </div>
</div>
