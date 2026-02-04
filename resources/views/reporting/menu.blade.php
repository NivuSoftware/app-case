<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-blue-900 leading-tight">
                {{ __('Reporteria') }}
            </h2>
            <button onclick="window.location.href='{{ route('dashboard') }}'"
                class="text-blue-700 hover:text-blue-900 transition flex items-center space-x-1" title="Regresar">
                <x-heroicon-s-arrow-left class="w-5 h-5" />
                <span>Atras</span>
            </button>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <a href="{{ route('reporteria.invoices.statuses') }}"
                    class="group bg-white rounded-xl p-6 shadow-sm border border-blue-100
                          hover:border-blue-400 hover:shadow-lg transition">
                    <div class="flex flex-col items-center text-center">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-12 w-12 text-blue-700 group-hover:text-blue-800 transition" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M3 7h18M3 12h18M3 17h18" />
                        </svg>
                        <h3 class="mt-4 text-lg font-semibold text-blue-900">
                            Estados de facturas
                        </h3>
                        <p class="text-sm text-blue-700/70 mt-1">
                            Seguimiento del estado SRI y dias pendientes
                        </p>
                    </div>
                </a>
                <a href="{{ route('reporteria.sales.daily.by-payment') }}"
                    class="group bg-white rounded-xl p-6 shadow-sm border border-blue-100
                          hover:border-blue-400 hover:shadow-lg transition">
                    <div class="flex flex-col items-center text-center">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-12 w-12 text-blue-700 group-hover:text-blue-800 transition" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M3 6h18M3 10h18M3 14h18M7 18h10" />
                        </svg>
                        <h3 class="mt-4 text-lg font-semibold text-blue-900">
                            Venta diaria por forma de pago
                        </h3>
                        <p class="text-sm text-blue-700/70 mt-1">
                            Consolidado diario por metodo de pago
                        </p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
