import { formatMoney } from './pos-utils';

function debounce(fn, delay = 300) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), delay);
    };
}

async function searchProducts(term) {
    const routes = window.SALES_ROUTES || {};
    if (!routes.productSearch || term.length < 2) {
        renderProductSuggestions([]);
        return;
    }

    try {
        const res = await fetch(`${routes.productSearch}?q=${encodeURIComponent(term)}`, {
            headers: { 'Accept': 'application/json' },
        });
        if (!res.ok) {
            renderProductSuggestions([]);
            return;
        }
        const data = await res.json();
        renderProductSuggestions(data || []);
    } catch (e) {
        console.error(e);
        renderProductSuggestions([]);
    }
}

function renderProductSuggestions(products) {
    const box = document.getElementById('product_suggestions');
    if (!box) return;

    box.innerHTML = '';

    if (!products.length) {
        box.classList.add('hidden');
        return;
    }

    box.classList.remove('hidden');

    products.forEach((p) => {
        const div = document.createElement('div');
        div.className = 'px-3 py-2 hover:bg-blue-50 cursor-pointer flex justify-between items-center';
        div.dataset.productId = p.id;
        div.dataset.productName = p.nombre;
        div.dataset.productPrice = p.price?.precio_unitario || p.precio_unitario || 0;
        div.innerHTML = `
            <div class="flex flex-col">
                <span class="text-xs font-semibold text-gray-800">${p.nombre}</span>
                <span class="text-[10px] text-gray-400">
                    Int: ${p.codigo_interno || '-'} | Barras: ${p.codigo_barras || '-'}
                </span>
            </div>
            <span class="text-xs font-semibold text-gray-700">${formatMoney(div.dataset.productPrice)}</span>
        `;
        box.appendChild(div);
    });
}

function applyProductFromSuggestion(div) {
    const idProd = document.getElementById('item_producto_id');
    const desc = document.getElementById('item_descripcion');
    const precio = document.getElementById('item_precio_unitario');

    const prodId = div.dataset.productId;
    const name = div.dataset.productName;
    const price = parseFloat(div.dataset.productPrice || '0');

    if (idProd) idProd.value = prodId || '';
    if (desc) desc.value = name || '';
    if (precio) precio.value = price.toFixed(2);

    const box = document.getElementById('product_suggestions');
    if (box) box.classList.add('hidden');
}

export function initProductSearch() {
    const input = document.getElementById('item_descripcion');
    const suggBox = document.getElementById('product_suggestions');
    if (!input || !suggBox) return;

    input.addEventListener('input', debounce((e) => {
        const term = e.target.value.trim();
        if (!term) {
            renderProductSuggestions([]);
            return;
        }
        searchProducts(term);
    }, 300));

    suggBox.addEventListener('click', (e) => {
        const item = e.target.closest('div[data-product-id]');
        if (!item) return;
        applyProductFromSuggestion(item);
    });
}
