<div
  id="change-modal"
  class="fixed inset-0 z-50 hidden"
  aria-hidden="true"
  role="dialog"
  aria-modal="true"
>
  <!-- Overlay más notorio -->
  <div class="flex items-center justify-center min-h-screen bg-black/70 backdrop-blur-sm p-4">
    <!-- Modal más grande -->
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl border border-gray-100 overflow-hidden">
      <!-- Header -->
      <div class="px-6 py-5 border-b flex justify-between items-start gap-4">
        <div>
          <h3 class="text-lg font-bold text-gray-900">Detalle del cambio</h3>
          <p class="text-sm text-gray-500 mt-1">
            Verifica el total, el monto recibido y el cambio a entregar.
          </p>
        </div>

        <button
          type="button"
          class="text-gray-400 hover:text-gray-700 text-3xl leading-none -mt-1"
          data-change-close
          aria-label="Cerrar"
        >
          &times;
        </button>
      </div>

      <!-- Body -->
      <div class="p-6 space-y-5">
        <div class="grid gap-3">
          <div class="flex justify-between items-center">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
              Total de la venta
            </span>
            <span id="change_total" class="text-lg font-bold text-gray-900">$ 0.00</span>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
              Monto recibido
            </span>
            <span id="change_recibido" class="text-lg font-bold text-gray-900">$ 0.00</span>
          </div>
        </div>

        <!-- Bloque destacado del cambio -->
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
          <div class="flex items-center justify-between gap-4">
            <div>
              <p class="text-lg font-semibold text-emerald-700 uppercase tracking-wide">
                Cambio a entregar
              </p>
              <p class="text-sm text-emerald-700/80 mt-1">
                Este es el valor que debes devolver al cliente.
              </p>
            </div>

            <span
              id="change_cambio"
              class="text-4xl md:text-5xl font-extrabold text-emerald-700 tabular-nums"
            >
              $ 0.00
            </span>
          </div>
        </div>

        
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t flex justify-end">
        <button
          type="button"
          data-change-close
          class="px-6 py-3 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-sm"
        >
          Cerrar
        </button>
      </div>
    </div>
  </div>
</div>
