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
                                d="M7 3h7l4 4v11a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M14 3v5h5" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M8.5 13.5l2 2 4-4" />
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
                            class="h-12 w-12 transition" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="#1D4ED8"
                                d="M23.59 3.475a5.1 5.1 0 0 0-3.05-3.05c-1.31-.42-2.5-.42-4.92-.42H8.36c-2.4 0-3.61 0-4.9.4a5.1 5.1 0 0 0-3.05 3.06C0 4.765 0 5.965 0 8.365v7.27c0 2.41 0 3.6.4 4.9a5.1 5.1 0 0 0 3.05 3.05c1.3.41 2.5.41 4.9.41h7.28c2.41 0 3.61 0 4.9-.4a5.1 5.1 0 0 0 3.06-3.06c.41-1.3.41-2.5.41-4.9v-7.25c0-2.41 0-3.61-.41-4.91m-6.17 4.63-.93.93a.5.5 0 0 1-.67.01 5 5 0 0 0-3.22-1.18c-.97 0-1.94.32-1.94 1.21 0 .9 1.04 1.2 2.24 1.65 2.1.7 3.84 1.58 3.84 3.64 0 2.24-1.74 3.78-4.58 3.95l-.26 1.2a.49.49 0 0 1-.48.39H9.63l-.09-.01a.5.5 0 0 1-.38-.59l.28-1.27a6.54 6.54 0 0 1-2.88-1.57v-.01a.48.48 0 0 1 0-.68l1-.97a.49.49 0 0 1 .67 0c.91.86 2.13 1.34 3.39 1.32 1.3 0 2.17-.55 2.17-1.42s-.88-1.1-2.54-1.72c-1.76-.63-3.43-1.52-3.43-3.6 0-2.42 2.01-3.6 4.39-3.71l.25-1.23a.48.48 0 0 1 .48-.38h1.78l.1.01c.26.06.43.31.37.57l-.27 1.37c.9.3 1.75.77 2.48 1.39l.02.02c.19.2.19.5 0 .68" />
                        </svg>
                        <h3 class="mt-4 text-lg font-semibold text-blue-900">
                            Venta diaria por forma de pago
                        </h3>
                        <p class="text-sm text-blue-700/70 mt-1">
                            Consolidado diario por metodo de pago
                        </p>
                    </div>
                </a>
                <a href="{{ route('reporteria.cashier.closures.daily') }}"
                    class="group bg-white rounded-xl p-6 shadow-sm border border-blue-100
                          hover:border-blue-400 hover:shadow-lg transition">
                    <div class="flex flex-col items-center text-center">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-12 w-12 transition" fill="none" stroke="#1D4ED8" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M21 15h-2.5a1.503 1.503 0 0 0-1.5 1.5 1.503 1.503 0 0 0 1.5 1.5h1a1.503 1.503 0 0 1 1.5 1.5 1.503 1.503 0 0 1-1.5 1.5H17m2 0v1m0-8v1m-6 6H6a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h2m12 3.12V9a2 2 0 0 0-2-2h-2" />
                            <path d="M16 10V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v6m8 0H8m8 0h1m-9 0H7m1 4v.01M8 17v.01m4-3.02V14m0 3v.01" />
                        </svg>
                        <h3 class="mt-4 text-lg font-semibold text-blue-900">
                            Cierres de caja diarios
                        </h3>
                        <p class="text-sm text-blue-700/70 mt-1">
                            Resumen por dia con formas de pago, horas y usuarios
                        </p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
