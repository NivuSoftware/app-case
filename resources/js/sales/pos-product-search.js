// resources/js/sales/pos-product-search.js
import { formatMoney, showSaleAlert } from './pos-utils';
import { addOrIncrementProduct } from './pos-cart';

let ALL_PRODUCTS = [];

function escapeHtml(text) {
    return String(text || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function debounce(fn, delay = 250) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), delay);
    };
}

function notifyAdded(descripcion, extra = '') {
    const msg = extra
        ? `Se añadió "${descripcion}" al carrito ${extra}`
        : `Se añadió "${descripcion}" al carrito.`;

    if (typeof showSaleAlert === 'function') {
        showSaleAlert(msg);
    } else if (window.SalesUtils?.showSaleAlert) {
        window.SalesUtils.showSaleAlert(msg);
    }
}

async function loadProducts() {
    const list  = document.getElementById('product_list');
    const empty = document.getElementById('product_list_empty');
    const bodegaSelect = document.getElementById('bodega_id');

    if (!list || !empty) return;

    const bodegaId = bodegaSelect ? bodegaSelect.value : '';

    if (!bodegaId) {
        ALL_PRODUCTS = [];
        list.innerHTML = '';
        empty.classList.remove('hidden');
        empty.innerHTML = `
            <p class="text-[13px] text-slate-400 text-center px-6">
                Selecciona una bodega para ver los productos con stock disponible.
            </p>
        `;
        return;
    }

    const routes = window.SALES_ROUTES || {};
    let url = routes.productSearch;

    if (!url && list.dataset && list.dataset.productUrl) {
        url = list.dataset.productUrl;
    }

    if (!url) {
        empty.classList.remove('hidden');
        empty.innerHTML = `
            <p class="text-[13px] text-red-500 text-center px-6">
                No se encontró la ruta de productos.
            </p>
        `;
        list.innerHTML = '';
        return;
    }

    const separator = url.includes('?') ? '&' : '?';
    url = `${url}${separator}bodega_id=${encodeURIComponent(bodegaId)}`;

    empty.classList.remove('hidden');
    empty.innerHTML = `
        <p class="text-[13px] text-slate-400 text-center px-6">
            Cargando productos de la bodega seleccionada...
        </p>
    `;
    list.innerHTML = '';

    try {
        console.log('Cargando productos desde:', url);

        const res = await fetch(url, {
            headers: { 'Accept': 'application/json' },
        });

        if (!res.ok) {
            throw new Error('Error al cargar productos');
        }

        const data = await res.json();
        ALL_PRODUCTS = Array.isArray(data) ? data : [];

        renderProductList(ALL_PRODUCTS);
    } catch (error) {
        console.error('Error cargando productos:', error);
        list.innerHTML = '';
        empty.classList.remove('hidden');
        empty.innerHTML = `
            <p class="text-[13px] text-red-500 text-center px-6">
                Ocurrió un error al cargar los productos.
            </p>
        `;
    }
}

function filterProducts(term) {
    if (!term) return ALL_PRODUCTS;

    const q = term.toLowerCase();

    return ALL_PRODUCTS.filter((p) => {
        const nombre = (p.nombre || '').toLowerCase();
        const cb = (p.codigo_barras || '').toLowerCase();
        const ci = (p.codigo_interno || '').toLowerCase();

        return nombre.includes(q) || cb.includes(q) || ci.includes(q);
    });
}

function getUnitPrice(p) {
    const priceObj = p.price || {};
    return Number(priceObj.precio_unitario ?? p.precio_unitario ?? 0);
}

// ✅ NUEVO helper: iva del producto
function getIvaPct(p) {
    const v = Number(p.iva_porcentaje);
    if (Number.isFinite(v)) return Math.max(0, Math.min(100, v));
    return 15; // fallback razonable
}

function renderProductList(products) {
    const list = document.getElementById('product_list');
    const empty = document.getElementById('product_list_empty');
    if (!list || !empty) return;

    list.innerHTML = '';

    if (!products || products.length === 0) {
        empty.classList.remove('hidden');
        empty.innerHTML = `
            <p class="text-[13px] text-slate-400 text-center px-6">
                No se encontraron productos. Ajusta tu búsqueda.
            </p>
        `;
        return;
    }

    empty.classList.add('hidden');

    products.forEach((p) => {
        const unitPrice = getUnitPrice(p);
        const codigo = p.codigo_interno || p.codigo_barras || '';
        const ivaPct = getIvaPct(p);

        const row = document.createElement('button');
        row.type = 'button';

        row.dataset.productId = p.id;
        row.dataset.productName = p.nombre || '';
        row.dataset.productPrice = String(unitPrice);
        row.dataset.productIva = String(ivaPct);

        row.className =
            'w-full text-left px-3 py-2.5 flex items-center justify-between ' +
            'hover:bg-slate-100/80 focus:outline-none';

        row.innerHTML = `
            <div class="flex flex-col">
                <span class="text-[13px] font-semibold text-slate-800">
                    ${escapeHtml(p.nombre || 'Producto sin nombre')}
                </span>
                <span class="text-[11px] text-slate-400">
                    ${codigo ? 'Cod: ' + escapeHtml(codigo) : ''}
                    ${p.unidad_medida ? ' · ' + escapeHtml(p.unidad_medida) : ''}
                    · IVA: ${ivaPct}%
                </span>
            </div>
            <div class="text-right">
                <p class="text-sm font-bold text-blue-700">
                    ${formatMoney(unitPrice)}
                </p>
                ${
                    p.categoria
                        ? `<p class="text-[11px] text-slate-400">${escapeHtml(p.categoria)}</p>`
                        : ''
                }
            </div>
        `;

        row.addEventListener('click', () => {
            const productoId = Number(p.id);
            const descripcion = p.nombre || 'Producto sin nombre';
            const precio = getUnitPrice(p);
            const iva_porcentaje = getIvaPct(p); // ✅

            if (!productoId || !descripcion || isNaN(precio)) {
                console.warn('[POS] Datos incompletos del producto al hacer click');
                return;
            }

            addOrIncrementProduct({
                producto_id: productoId,
                descripcion,
                precio_unitario: precio,
                descuento: 0,
                descuento_pct: 0,
                iva_porcentaje, // ✅ NUEVO
                cantidad: 1,
            });

            notifyAdded(descripcion);
        });

        list.appendChild(row);
    });
}

export function initProductSearch() {
    const input        = document.getElementById('item_descripcion');
    const suggBox      = document.getElementById('product_suggestions');
    const bodegaSelect = document.getElementById('bodega_id');

    if (suggBox) {
        suggBox.classList.add('hidden');
        suggBox.innerHTML = '';
    }

    loadProducts();

    if (bodegaSelect && bodegaSelect.tagName === 'SELECT') {
        bodegaSelect.addEventListener('change', () => {
            if (input) input.value = '';
            loadProducts();
        });
    }

    if (input) {
        input.addEventListener(
            'input',
            debounce((e) => {
                const term = e.target.value.trim();
                const filtered = filterProducts(term);
                renderProductList(filtered);
            }, 200)
        );

        input.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter') return;

            e.preventDefault();
            const term = input.value.trim();
            if (!term) return;

            const matches = filterProducts(term);

            if (matches.length === 1) {
                const p = matches[0];
                const productoId = Number(p.id);
                const descripcion = p.nombre || 'Producto sin nombre';
                const precio = getUnitPrice(p);
                const iva_porcentaje = getIvaPct(p);

                if (!productoId || !descripcion || isNaN(precio)) {
                    console.warn('[POS] Datos incompletos del producto (scanner)');
                    return;
                }

                addOrIncrementProduct({
                    producto_id: productoId,
                    descripcion,
                    precio_unitario: precio,
                    descuento: 0,
                    descuento_pct: 0,
                    iva_porcentaje, // ✅
                    cantidad: 1,
                });

                input.value = '';
                renderProductList(ALL_PRODUCTS);

                notifyAdded(descripcion, '(scanner)');
            } else {
                renderProductList(matches);
            }
        });
    }
}
