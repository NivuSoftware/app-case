<div id="modal-edit" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center">
  <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl">
    <!-- Header -->
    <div class="px-6 py-4 border-b flex items-center justify-between">
      <h2 class="text-xl font-semibold text-blue-900">Editar Producto</h2>
      <button type="button" onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">✕</button>
    </div>

    <form id="form-edit" class="p-6">
      <input type="hidden" name="id" id="edit-id">
      <input type="hidden" name="price_id" id="edit-price-id">

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- COL IZQUIERDA: DETALLES -->
        <section class="bg-blue-50/40 border border-blue-100 rounded-lg p-4">
          <h3 class="text-sm font-semibold text-blue-900 mb-3">Detalles del producto</h3>

          <div class="space-y-3">
            <div>
              <label class="text-xs text-blue-800">Nombre</label>
              <input type="text" name="nombre" id="edit-nombre"
                     class="w-full border rounded-md px-3 py-2" placeholder="Ej. Lápiz HB amarillo">
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="text-xs text-blue-800">Categoría</label>
                <input type="text" name="categoria" id="edit-categoria"
                       class="w-full border rounded-md px-3 py-2" placeholder="Útiles escolares">
              </div>
              <div>
                <label class="text-xs text-blue-800">Unidad</label>
                <input type="text" name="unidad_medida" id="edit-unidad-medida"
                       class="w-full border rounded-md px-3 py-2" placeholder="unidad / caja / paquete">
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="text-xs text-blue-800">Código barras</label>
                <input type="text" name="codigo_barras" id="edit-codigo-barras"
                       class="w-full border rounded-md px-3 py-2" placeholder="EAN/UPC (opcional)">
              </div>
              <div>
                <label class="text-xs text-blue-800">Código interno</label>
                <input type="text" name="codigo_interno" id="edit-codigo-interno"
                       class="w-full border rounded-md px-3 py-2" placeholder="SKU propio (opcional)">
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="text-xs text-blue-800">Stock mínimo</label>
                <input type="number" name="stock_minimo" id="edit-stock-minimo" min="0"
                       class="w-full border rounded-md px-3 py-2" placeholder="Ej. 20">
                <p class="text-[11px] text-slate-500 mt-1">Te avisaremos cuando el stock baje de este valor.</p>
              </div>
            </div>

            <div>
              <label class="text-xs text-blue-800">Descripción</label>
              <textarea name="descripcion" id="edit-descripcion" rows="3"
                        class="w-full border rounded-md px-3 py-2"
                        placeholder="Notas o características (opcional)"></textarea>
            </div>
          </div>
        </section>

        <!-- COL DERECHA: PRECIOS -->
        <section class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <h3 class="text-sm font-semibold text-slate-800 mb-3">Reglas de precio</h3>

          <!-- Precio base -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs text-slate-700">Precio unitario *</label>
              <input type="number" step="0.01" min="0" name="precio_unitario" id="edit-precio-unitario"
                     class="w-full border rounded-md px-3 py-2" placeholder="Ej. 1.50">
            </div>
            <div>
              <label class="text-xs text-slate-700">Moneda</label>
                <div class="w-full border rounded-md px-3 py-2 bg-gray-100 text-gray-700 text-sm">
                  USD
                </div>
                <input type="hidden" name="moneda" id="edit-moneda" value="USD">
            </div>
          </div>

          <!-- Descuento por cantidad -->
          <div class="mt-5">
            <label class="inline-flex items-center gap-2 cursor-pointer">
              <input id="edit-toggle-descuento" type="checkbox" class="rounded border-gray-300">
              <span class="text-sm text-slate-800 font-medium">Activar descuento por cantidad</span>
            </label>
            <p class="text-[11px] text-slate-500 mt-1">Aplica un precio especial para un rango de unidades.</p>

            <div id="edit-box-descuento" class="mt-3 grid grid-cols-3 gap-3 opacity-50 pointer-events-none">
              <div>
                <label class="text-xs text-slate-700">Cant. mín.</label>
                <input type="number" min="1" name="cantidad_min" id="edit-cantidad-min"
                       class="w-full border rounded-md px-3 py-2" placeholder="Ej. 6" disabled>
              </div>
              <div>
                <label class="text-xs text-slate-700">Cant. máx.</label>
                <input type="number" min="1" name="cantidad_max" id="edit-cantidad-max"
                       class="w-full border rounded-md px-3 py-2" placeholder="Ej. 11" disabled>
              </div>
              <div>
                <label class="text-xs text-slate-700">Precio por cantidad</label>
                <input type="number" step="0.01" min="0" name="precio_por_cantidad" id="edit-precio-por-cantidad"
                       class="w-full border rounded-md px-3 py-2" placeholder="Ej. 1.25" disabled>
              </div>
            </div>
          </div>

          <!-- Venta por caja -->
          <div class="mt-5">
            <label class="inline-flex items-center gap-2 cursor-pointer">
              <input id="edit-toggle-caja" type="checkbox" class="rounded border-gray-300">
              <span class="text-sm text-slate-800 font-medium">Activar venta por caja</span>
            </label>
            <p class="text-[11px] text-slate-500 mt-1">Define cuántas unidades trae la caja y su precio total.</p>

            <div id="edit-box-caja" class="mt-3 grid grid-cols-2 gap-3 opacity-50 pointer-events-none">
              <div>
                <label class="text-xs text-slate-700">Unidades por caja</label>
                <input type="number" min="1" name="unidades_por_caja" id="edit-unidades-por-caja"
                       class="w-full border rounded-md px-3 py-2" placeholder="Ej. 12" disabled>
              </div>
              <div>
                <label class="text-xs text-slate-700">Precio por caja</label>
                <input type="number" step="0.01" min="0" name="precio_por_caja" id="edit-precio-por-caja"
                       class="w-full border rounded-md px-3 py-2" placeholder="Ej. 13.80" disabled>
              </div>
            </div>
          </div>

          <ul class="mt-4 text-[11px] text-slate-500 space-y-1 list-disc list-inside">
            <li>Si no activas un bloque, esos campos no se guardan.</li>
            <li>El precio por caja se usa primero (cantidad divisible). Lo restante aplica precio por cantidad o unitario.</li>
          </ul>
        </section>
      </div>

      <!-- Footer -->
      <div class="flex justify-end gap-3 mt-6">
        <button type="button" onclick="closeEditModal()"
                class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-700">
          Cancelar
        </button>
        <button type="submit"
                class="px-4 py-2 bg-blue-700 text-white rounded-md hover:bg-blue-800">
          Actualizar
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  // UX: habilitar/inhabilitar bloques en EDIT según switches y valores cargados
  (function initEditToggles(){
    const tDesc = document.getElementById('edit-toggle-descuento');
    const boxDesc = document.getElementById('edit-box-descuento');
    const tCaja = document.getElementById('edit-toggle-caja');
    const boxCaja = document.getElementById('edit-box-caja');

    const setBlock = (toggle, box) => {
      const inputs = box.querySelectorAll('input');
      if (toggle.checked) {
        box.classList.remove('opacity-50','pointer-events-none');
        inputs.forEach(i => i.disabled = false);
      } else {
        box.classList.add('opacity-50','pointer-events-none');
        inputs.forEach(i => { i.disabled = true; });
      }
    };

    // Exponer para que openEditModal lo llame tras setear valores
    window._editToggleApply = function autoToggleFromValues(){
      const hasQtyDisc =
        (document.getElementById('edit-cantidad-min').value ||
         document.getElementById('edit-cantidad-max').value ||
         document.getElementById('edit-precio-por-cantidad').value);
      tDesc.checked = !!hasQtyDisc;
      setBlock(tDesc, boxDesc);

      const hasBox =
        (document.getElementById('edit-unidades-por-caja').value ||
         document.getElementById('edit-precio-por-caja').value);
      tCaja.checked = !!hasBox;
      setBlock(tCaja, boxCaja);
    };

    tDesc?.addEventListener('change', () => setBlock(tDesc, boxDesc));
    tCaja?.addEventListener('change', () => setBlock(tCaja, boxCaja));
  })();
</script>
