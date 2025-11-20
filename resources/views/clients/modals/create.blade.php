<div
    id="createClientModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40"
>
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg mx-4">
        {{-- HEADER --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-800">
                Nuevo Cliente
            </h3>
            <button
                type="button"
                onclick="closeCreateModal()"
                class="text-gray-400 hover:text-gray-600"
            >
                ✕
            </button>
        </div>

        {{-- FORM --}}
        <form method="POST" action="{{ route('clients.store') }}" class="px-4 py-4 space-y-3">
            @csrf

            {{-- campo real que usa el backend --}}
            <input type="hidden" name="business" id="create-business">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                {{-- Tipo de persona (ocupa 2 columnas y va primero) --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Tipo de cliente
                    </label>
                    <select
                        name="tipo"
                        class="w-full border-gray-300 rounded-md shadow-sm text-sm"
                        required
                    >
                        <option value="natural">Persona natural</option>
                        <option value="juridico">Persona jurídica</option>
                    </select>
                </div>

                {{-- Para NATURAL: nombres y apellidos --}}
                <div class="md:col-span-2" id="create-natural-fields">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Nombres
                            </label>
                            <input
                                type="text"
                                id="create-nombres"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Apellidos
                            </label>
                            <input
                                type="text"
                                id="create-apellidos"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm"
                            >
                        </div>
                    </div>
                </div>

                {{-- Para JURÍDICO: razón social --}}
                <div class="md:col-span-2 hidden" id="create-juridico-fields">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Razón social
                    </label>
                    <input
                        type="text"
                        id="create-razon-social"
                        class="w-full border-gray-300 rounded-md shadow-sm text-sm"
                    >
                </div>

                {{-- Tipo identificación --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Tipo de identificación
                    </label>
                    <select
                        name="tipo_identificacion"
                        class="w-full border-gray-300 rounded-md shadow-sm text-sm"
                        required
                    >
                        <option value="">Seleccione...</option>
                        <option value="CEDULA">Cédula</option>
                        <option value="RUC">RUC</option>
                        <option value="PASAPORTE">Pasaporte</option>
                    </select>
                </div>

                {{-- Identificación --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Identificación
                    </label>
                    <input
                        type="text"
                        name="identificacion"
                        class="w-full border-gray-300 rounded-md shadow-sm text-sm"
                        required
                    >
                </div>

                {{-- Teléfono --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Teléfono
                    </label>
                    <input
                        type="text"
                        name="telefono"
                        class="w-full border-gray-300 rounded-md shadow-sm text-sm"
                    >
                </div>

                {{-- Ciudad --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Ciudad
                    </label>
                    <input
                        type="text"
                        name="ciudad"
                        class="w-full border-gray-300 rounded-md shadow-sm text-sm"
                    >
                </div>

                {{-- Dirección --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Dirección
                    </label>
                    <input
                        type="text"
                        name="direccion"
                        class="w-full border-gray-300 rounded-md shadow-sm text-sm"
                    >
                </div>

                {{-- Estado --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Estado
                    </label>
                    <select
                        name="estado"
                        class="w-full border-gray-300 rounded-md shadow-sm text-sm"
                        required
                    >
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
            </div>

            {{-- Emails (dinámicos) --}}
            <div class="mt-2">
                <label class="block text-xs font-semibold text-gray-600 mb-1">
                    Emails
                </label>
                <p class="text-[11px] text-gray-500 mb-1">
                    Puedes registrar uno o varios correos.
                </p>

                <div id="create-emails-wrapper" class="space-y-2">
                    {{-- Fila por defecto (input + botón -) --}}
                    <div class="flex gap-2">
                        <input
                            type="email"
                            name="emails[]"
                            placeholder="correo@ejemplo.com"
                            class="flex-1 border-gray-300 rounded-md shadow-sm text-sm"
                        >
                        <button
                            type="button"
                            onclick="removeCreateEmailInput(this)"
                            class="px-2 py-1 text-xs rounded-md border border-gray-300 text-red-600 hover:bg-red-50"
                        >
                            −
                        </button>
                    </div>
                </div>

                <button
                    type="button"
                    onclick="addCreateEmailInput()"
                    class="mt-2 inline-flex items-center px-2 py-1 text-[11px] rounded-md border border-dashed border-gray-300 text-gray-700 hover:bg-gray-50"
                >
                    + Añadir otro correo
                </button>
            </div>

            {{-- FOOTER --}}
            <div class="mt-4 flex justify-end gap-2 border-t border-gray-100 pt-3">
                <button
                    type="button"
                    onclick="closeCreateModal()"
                    class="px-3 py-2 text-xs rounded-md border border-gray-300 text-gray-700 hover:bg-gray-100"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    class="px-4 py-2 text-xs rounded-md bg-blue-700 text-white hover:bg-blue-800"
                >
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>
