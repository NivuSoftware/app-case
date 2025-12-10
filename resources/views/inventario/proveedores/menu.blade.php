<x-app-layout>
    {{-- HEADER --}}
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-blue-900 leading-tight">
                {{ __('Módulo de Proveedores y Compras') }}
            </h2>

            <button 
                onclick="window.location.href='{{ route('dashboard') }}'"
                class="text-blue-700 hover:text-blue-900 transition flex items-center"
            >
                <x-heroicon-s-arrow-left class="w-6 h-6 mr-1" />
                Atrás
            </button>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto px-6 lg:px-8">

            {{-- Descripción corta --}}
            <div class="mb-6">
                <p class="text-sm text-blue-900/80">
                    Desde este módulo puedes gestionar tus proveedores y registrar las compras 
                    que actualizan el stock del inventario.
                </p>
            </div>

            {{-- GRID DE OPCIONES --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- OPCIÓN 1: REGISTRAR PROVEEDOR (CRUD PROVEEDORES) --}}
                <a href="{{ route('proveedores.index') }}"
                   class="group bg-white rounded-xl p-6 shadow-sm border border-blue-100
                          hover:border-blue-400 hover:shadow-lg transition flex flex-col items-center text-center">
                    
                    <div class="h-12 w-12 rounded-full bg-blue-50 flex items-center justify-center mb-4
                                group-hover:bg-blue-100 transition">
                        <x-heroicon-s-user-group class="h-7 w-7 text-blue-700 group-hover:text-blue-800" />
                    </div>

                    <h3 class="text-lg font-semibold text-blue-900">
                        Registrar / Gestionar Proveedores
                    </h3>
                    <p class="text-sm text-blue-700/70 mt-1">
                        Crear, editar y administrar la información de tus proveedores.
                    </p>
                </a>

                {{-- OPCIÓN 2: REGISTRAR COMPRA --}}
                <a href="{{ route('compras.index') }}"
                   class="group bg-white rounded-xl p-6 shadow-sm border border-emerald-100
                          hover:border-emerald-400 hover:shadow-lg transition flex flex-col items-center text-center">
                    
                    <div class="h-12 w-12 rounded-full bg-emerald-50 flex items-center justify-center mb-4
                                group-hover:bg-emerald-100 transition">
                        <x-heroicon-s-clipboard-document-list class="h-7 w-7 text-emerald-700 group-hover:text-emerald-800" />
                    </div>

                    <h3 class="text-lg font-semibold text-emerald-900">
                        Registrar Compras
                    </h3>
                    <p class="text-sm text-emerald-700/70 mt-1">
                        Registrar compras de productos y actualizar automáticamente el stock.
                    </p>
                </a>

            </div>

        </div>
    </div>
</x-app-layout>
