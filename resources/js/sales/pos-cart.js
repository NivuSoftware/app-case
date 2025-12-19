// resources/js/sales/pos-cart.js
import { formatMoney } from './pos-utils';

let cart = [];

// ==============================
// Helpers exactos (centavos)
// ==============================
const toCents = (value) => {
  const n = Number(value);
  if (!Number.isFinite(n)) return 0;
  // +EPSILON ayuda con casos como 1.005
  return Math.round((n + Number.EPSILON) * 100);
};

const fromCents = (cents) => (Number(cents || 0) / 100);

function clampPct(v) {
  let pct = parseFloat(v);
  if (isNaN(pct)) pct = 0;
  if (pct < 0) pct = 0;
  if (pct > 100) pct = 100;
  return pct;
}

function isIvaGlobalEnabled() {
  const el = document.getElementById('toggle_iva_global');
  return el ? !!el.checked : true;
}

// ==============================
// API
// ==============================
export function getCart() {
  return cart;
}

export function clearCart() {
  cart = [];
  renderCart();
  recalcSummary();
}

export function initCart() {
  const addBtn = document.getElementById('btn-add-item');
  if (addBtn) addBtn.addEventListener('click', addItemFromHiddenForm);

  const tbody = document.getElementById('cart-body');
  if (tbody) {
    tbody.addEventListener('click', (e) => {
      const removeBtn = e.target.closest('[data-cart-remove]');
      if (removeBtn) {
        const index = parseInt(removeBtn.dataset.index, 10);
        if (!isNaN(index)) removeItemFromCart(index);
        return;
      }

      const plusBtn = e.target.closest('[data-cart-plus]');
      if (plusBtn) {
        const index = parseInt(plusBtn.dataset.index, 10);
        if (!isNaN(index)) changeItemQty(index, 1);
        return;
      }

      const minusBtn = e.target.closest('[data-cart-minus]');
      if (minusBtn) {
        const index = parseInt(minusBtn.dataset.index, 10);
        if (!isNaN(index)) changeItemQty(index, -1);
        return;
      }
    });

    // Descuento % por item
    tbody.addEventListener('input', (e) => {
      const discountInput = e.target.closest('[data-cart-discount]');
      if (discountInput) {
        const index = parseInt(discountInput.dataset.index, 10);
        if (!isNaN(index)) {
          const pct = parseFloat(discountInput.value || '0');
          setItemDiscountPercent(index, pct);
        }
      }
    });
  }

  // Toggle IVA global (ON/OFF)
  const ivaToggle = document.getElementById('toggle_iva_global');
  if (ivaToggle) {
    ivaToggle.addEventListener('change', () => {
      recalcSummary();
    });
  }

  recalcSummary();
  renderCart();
}

/**
 * ✅ Calcular una línea en CENTAVOS (SIN IVA aquí)
 * - lineSubtotalCents = qty * precio_unitario
 * - descuentoCents = round(lineSubtotalCents * pct/100)
 * - totalCents = lineSubtotalCents - descuentoCents
 */
function calcLine(item) {
  const qty = parseInt(item.cantidad, 10) || 1;
  const priceCents = toCents(item.precio_unitario);

  const pct = clampPct(item.descuento_pct);

  const lineSubtotalCents = qty * priceCents;
  const descuentoCents = Math.round((lineSubtotalCents * pct) / 100);
  const totalCents = lineSubtotalCents - descuentoCents;

  return {
    // guardamos también en cents para totales exactos
    lineSubtotalCents,
    descuentoCents,
    totalCents,

    // y en $ para render
    lineSubtotal: fromCents(lineSubtotalCents),
    descuento_pct: pct,
    descuento: fromCents(descuentoCents),
    total: fromCents(totalCents),
  };
}

export function addOrIncrementProduct({
  producto_id,
  descripcion,
  precio_unitario,
  cantidad = 1,
  iva_porcentaje = 15,
}) {
  producto_id = parseInt(producto_id, 10);
  cantidad = parseInt(cantidad, 10) || 1;

  const ivaPct = clampPct(iva_porcentaje);

  if (!producto_id || !descripcion || !Number.isFinite(Number(precio_unitario)) || cantidad <= 0) return;

  const existing = cart.find((item) => item.producto_id === producto_id);

  if (existing) {
    existing.cantidad += cantidad;
    if (existing.cantidad < 1) existing.cantidad = 1;

    // actualiza IVA si llega
    existing.iva_porcentaje = ivaPct;

    const computed = calcLine(existing);
    Object.assign(existing, computed);
  } else {
    const baseItem = {
      producto_id,
      descripcion,
      cantidad,
      precio_unitario: Number(precio_unitario),
      descuento_pct: 0,
      iva_porcentaje: ivaPct,
    };

    const computed = calcLine(baseItem);

    cart.push({
      ...baseItem,
      ...computed,
    });
  }

  renderCart();
  recalcSummary();
}

function addItemFromHiddenForm() {
  const productoId = parseInt(document.getElementById('item_producto_id')?.value, 10);
  const descripcion = document.getElementById('item_descripcion')?.value.trim();
  const cantidad = parseInt(document.getElementById('item_cantidad')?.value, 10) || 1;
  const precio = parseFloat(document.getElementById('item_precio_unitario')?.value);

  addOrIncrementProduct({
    producto_id: productoId,
    descripcion,
    precio_unitario: precio,
    cantidad,
  });

  clearItemForm();
}

function clearItemForm() {
  const desc = document.getElementById('item_descripcion');
  const idProd = document.getElementById('item_producto_id');
  const cant = document.getElementById('item_cantidad');
  const precio = document.getElementById('item_precio_unitario');
  const descu = document.getElementById('item_descuento');

  if (desc) desc.value = '';
  if (idProd) idProd.value = '';
  if (cant) cant.value = 1;
  if (precio) precio.value = '';
  if (descu) descu.value = 0;

  const sugg = document.getElementById('product_suggestions');
  if (sugg) sugg.classList.add('hidden');
}

function removeItemFromCart(index) {
  cart.splice(index, 1);
  renderCart();
  recalcSummary();
}

function changeItemQty(index, delta) {
  const item = cart[index];
  if (!item) return;

  item.cantidad += delta;

  if (item.cantidad < 1) {
    cart.splice(index, 1);
  } else {
    const computed = calcLine(item);
    Object.assign(item, computed);
  }

  renderCart();
  recalcSummary();
}

function setItemDiscountPercent(index, pct) {
  const item = cart[index];
  if (!item) return;

  item.descuento_pct = pct;

  const computed = calcLine(item);
  Object.assign(item, computed);

  renderCart();
  recalcSummary();
}

/**
 * ✅ Totales exactos:
 * - subtotal = suma lineSubtotalCents
 * - descuento = suma descuentoCents
 * - iva = suma round(totalCents * pct/100) por línea
 */
export function getTotals() {
  let subtotalCents = 0;
  let descuentoCents = 0;
  let impuestoCents = 0;

  let ivaCents = 0;
  const ivaEnabled = isIvaGlobalEnabled();

  cart.forEach((item) => {
    // siempre recalculamos por seguridad (si algún item vino viejo)
    const computed = calcLine(item);
    Object.assign(item, computed);

    subtotalCents += item.lineSubtotalCents || 0;
    descuentoCents += item.descuentoCents || 0;

    if (ivaEnabled) {
      const pct = clampPct(item.iva_porcentaje ?? 0);
      // IVA por línea, redondeado al centavo (exacto)
      ivaCents += Math.round(((item.totalCents || 0) * pct) / 100);
    }
  });

  const baseImponibleCents = subtotalCents - descuentoCents;
  const totalCents = baseImponibleCents + impuestoCents + ivaCents;

  return {
    subtotal: fromCents(subtotalCents),
    descuento: fromCents(descuentoCents),
    impuesto: fromCents(impuestoCents),
    iva: fromCents(ivaCents),
    total: fromCents(totalCents),
  };
}

function recalcSummary() {
  const { subtotal, descuento, impuesto, iva, total } = getTotals();

  const subEl = document.getElementById('resumen-subtotal');
  const descEl = document.getElementById('resumen-descuento');
  const impEl = document.getElementById('resumen-impuesto');
  const ivaEl = document.getElementById('resumen-iva');
  const totEl = document.getElementById('resumen-total');

  if (subEl) subEl.textContent = formatMoney(subtotal);
  if (descEl) descEl.textContent = formatMoney(descuento);
  if (impEl) impEl.textContent = formatMoney(impuesto);
  if (ivaEl) ivaEl.textContent = formatMoney(iva);
  if (totEl) totEl.textContent = formatMoney(total);
}

function renderCart() {
  const tbody = document.getElementById('cart-body');
  const emptyRow = document.getElementById('empty-cart-row');
  if (!tbody || !emptyRow) return;

  tbody.innerHTML = '';

  if (cart.length === 0) {
    emptyRow.classList.remove('hidden');
    return;
  }

  emptyRow.classList.add('hidden');

  cart.forEach((item, index) => {
    const tr = document.createElement('tr');

    tr.innerHTML = `
      <td class="px-3 py-2 text-xs text-gray-700">
        <div class="font-semibold text-gray-800">${item.descripcion}</div>
        <div class="text-[10px] text-gray-400">
          ID: ${item.producto_id} · IVA: ${Number(item.iva_porcentaje ?? 0)}%
        </div>
      </td>

      <td class="px-3 py-2 text-right text-xs text-gray-700">
        <div class="inline-flex items-center gap-1">
          <button type="button" class="px-1 text-xs text-slate-500 hover:text-slate-800" data-cart-minus data-index="${index}">-</button>
          <span class="min-w-[1.5rem] text-center">${item.cantidad}</span>
          <button type="button" class="px-1 text-xs text-slate-500 hover:text-slate-800" data-cart-plus data-index="${index}">+</button>
        </div>
      </td>

      <td class="px-3 py-2 text-right text-xs text-gray-700">
        ${formatMoney(item.precio_unitario)}
      </td>

      <td class="px-3 py-2 text-right text-xs text-gray-700">
        <div class="flex flex-col items-end gap-1">
          <div class="inline-flex items-center gap-1">
            <input
              type="number"
              min="0"
              max="100"
              step="0.01"
              value="${(item.descuento_pct ?? 0)}"
              data-cart-discount
              data-index="${index}"
              class="w-16 text-right border border-slate-200 rounded-md px-1.5 py-1 text-xs focus:ring-blue-500 focus:border-blue-500"
              title="Descuento %"
            />
            <span class="text-[10px] text-slate-500">%</span>
          </div>

          <div class="text-[10px] text-slate-400">
            -${formatMoney(item.descuento || 0)}
          </div>
        </div>
      </td>

      <td class="px-3 py-2 text-right text-xs font-semibold text-gray-900">
        ${formatMoney(item.total)}
      </td>

      <td class="px-3 py-2 text-center text-xs">
        <button type="button" class="text-red-600 hover:text-red-800" data-cart-remove data-index="${index}">
          Eliminar
        </button>
      </td>
    `;

    tbody.appendChild(tr);
  });
}
