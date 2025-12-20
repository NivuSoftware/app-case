// resources/js/sales/pos-product-search.js
import { formatMoney, showSaleAlert } from './pos-utils';
import { addOrIncrementProduct } from './pos-cart';

let ALL_PRODUCTS = [];

// ✅ IVA negocio: solo 0 o 15
function normalizeIvaPct(v) {
  const n = Number(v);
  return n === 0 ? 0 : 15;
}

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
  const list = document.getElementById('product_list');
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
      headers: { Accept: 'application/json' },
    });

    if (!res.ok) throw new Error('Error al cargar productos');

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

// ✅ helper: iva del producto (solo 0 / 15)
function getIvaPct(p) {
  return normalizeIvaPct(p?.iva_porcentaje);
}

// ✅ reglas de precios del producto (por cantidad / por caja)
// Espera que backend mande: p.product_prices o p.prices (array)
function getPriceRules(p) {
  const rules = p.product_prices || p.prices || p.price_rules || [];
  if (!Array.isArray(rules)) return [];

  // normalizamos números para evitar NaN en el carrito
  return rules
    .map((r) => ({
      producto_id: Number(r.producto_id ?? p.id),
      precio_unitario:
        r.precio_unitario != null ? Number(r.precio_unitario) : null,
      precio_por_cantidad:
        r.precio_por_cantidad != null ? Number(r.precio_por_cantidad) : null,
      cantidad_min: r.cantidad_min != null ? Number(r.cantidad_min) : null,
      cantidad_max: r.cantidad_max != null ? Number(r.cantidad_max) : null,
      precio_por_caja: r.precio_por_caja != null ? Number(r.precio_por_caja) : null,
      unidades_por_caja:
        r.unidades_por_caja != null ? Number(r.unidades_por_caja) : null,
      moneda: r.moneda || 'USD',
    }))
    .filter((r) => Number.isFinite(r.producto_id));
}

// ✅ Opcional: mini etiqueta para mostrar reglas en la lista
function buildRuleHint(rules) {
  if (!rules || rules.length === 0) return '';

  const caja = rules.find(
    (r) => r.unidades_por_caja && r.precio_por_caja != null
  );

  const qtyRules = rules
    .filter((r) => r.cantidad_min && r.precio_por_cantidad != null)
    .sort((a, b) => (a.cantidad_min || 0) - (b.cantidad_min || 0));

  const parts = [];

  if (qtyRules.length > 0) {
    const r0 = qtyRules[0];
    const range =
      r0.cantidad_max != null
        ? `${r0.cantidad_min}-${r0.cantidad_max}`
        : `Desde ${r0.cantidad_min}`;
    parts.push(`${range}: ${formatMoney(r0.precio_por_cantidad)}`);
  }

  if (caja) {
    parts.push(
      `Caja ${caja.unidades_por_caja}u: ${formatMoney(caja.precio_por_caja)}`
    );
  }

  if (parts.length === 0) return '';
  return parts.slice(0, 2).join(' · ');
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

    const priceRules = getPriceRules(p);
    const hint = buildRuleHint(priceRules);

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
      <div class="flex flex-col min-w-0">
        <span class="text-[13px] font-semibold text-slate-800 truncate">
          ${escapeHtml(p.nombre || 'Producto sin nombre')}
        </span>
        <span class="text-[11px] text-slate-400">
          ${codigo ? 'Cod: ' + escapeHtml(codigo) : ''}
          ${p.unidad_medida ? ' · ' + escapeHtml(p.unidad_medida) : ''}
          · IVA: ${ivaPct}%
        </span>

        ${
          hint
            ? `<span class="text-[11px] text-emerald-700 mt-0.5">
                 ${escapeHtml(hint)}
               </span>`
            : ''
        }
      </div>

      <div class="text-right">
        <p class="text-sm font-bold text-blue-700">
          ${formatMoney(unitPrice)}
        </p>
        ${
          p.categoria
            ? `<p class="text-[11px] text-slate-400">${escapeHtml(
                p.categoria
              )}</p>`
            : ''
        }
      </div>
    `;

    row.addEventListener('click', () => {
      const productoId = Number(p.id);
      const descripcion = p.nombre || 'Producto sin nombre';
      const precio = getUnitPrice(p);
      const iva_porcentaje = getIvaPct(p);

      if (!productoId || !descripcion || isNaN(precio)) {
        console.warn('[POS] Datos incompletos del producto al hacer click');
        return;
      }

      addOrIncrementProduct({
        producto_id: productoId,
        descripcion,
        precio_unitario: precio, // base; el carrito ajusta con reglas
        iva_porcentaje, // 0/15
        cantidad: 1,
        price_rules: priceRules,
      });

      notifyAdded(descripcion);
    });

    list.appendChild(row);
  });
}

export function initProductSearch() {
  const input = document.getElementById('item_descripcion');
  const suggBox = document.getElementById('product_suggestions');
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
        const priceRules = getPriceRules(p);

        if (!productoId || !descripcion || isNaN(precio)) {
          console.warn('[POS] Datos incompletos del producto (scanner)');
          return;
        }

        addOrIncrementProduct({
          producto_id: productoId,
          descripcion,
          precio_unitario: precio,
          iva_porcentaje, // 0/15
          cantidad: 1,
          price_rules: priceRules,
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
