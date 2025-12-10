import { formatMoney } from './pos-utils';

let cart = [];

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
    if (addBtn) {
        addBtn.addEventListener('click', addItemToCart);
    }

    const tbody = document.getElementById('cart-body');
    if (tbody) {
        tbody.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-cart-remove]');
            if (!btn) return;
            const index = parseInt(btn.dataset.index, 10);
            if (!isNaN(index)) {
                removeItemFromCart(index);
            }
        });
    }

    recalcSummary();
    renderCart();
}

function addItemToCart() {
    const productoId = parseInt(document.getElementById('item_producto_id')?.value, 10);
    const descripcion = document.getElementById('item_descripcion')?.value.trim();
    const cantidad = parseInt(document.getElementById('item_cantidad')?.value, 10);
    const precio = parseFloat(document.getElementById('item_precio_unitario')?.value);
    const descuento = parseFloat(document.getElementById('item_descuento')?.value || '0');

    if (!productoId || !descripcion || !cantidad || isNaN(precio)) {
        return; // validación más fina desde payment si quieres
    }

    const lineSubtotal = cantidad * precio;
    const total = lineSubtotal - descuento;

    if (total < 0) {
        return;
    }

    cart.push({
        producto_id: productoId,
        descripcion,
        cantidad,
        precio_unitario: precio,
        descuento,
        total,
    });

    clearItemForm();
    renderCart();
    recalcSummary();
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

export function getTotals() {
    let subtotal = 0;
    let descuentoTotal = 0;
    const impuesto = 0;
    const iva = 0;

    cart.forEach((item) => {
        subtotal += item.cantidad * item.precio_unitario;
        descuentoTotal += item.descuento;
    });

    const total = subtotal - descuentoTotal + impuesto + iva;

    return { subtotal, descuento: descuentoTotal, impuesto, iva, total };
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
                <div class="text-[10px] text-gray-400">ID: ${item.producto_id}</div>
            </td>
            <td class="px-3 py-2 text-right text-xs text-gray-700">${item.cantidad}</td>
            <td class="px-3 py-2 text-right text-xs text-gray-700">${formatMoney(item.precio_unitario)}</td>
            <td class="px-3 py-2 text-right text-xs text-gray-700">${formatMoney(item.descuento)}</td>
            <td class="px-3 py-2 text-right text-xs font-semibold text-gray-900">${formatMoney(item.total)}</td>
            <td class="px-3 py-2 text-center text-xs">
                <button
                    type="button"
                    class="text-red-600 hover:text-red-800"
                    data-cart-remove
                    data-index="${index}"
                >
                    Eliminar
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}
