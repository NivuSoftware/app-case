import { formatMoney } from './pos-utils';
import { addProductToCart, getIvaPct, getPriceRules, getUnitPrice } from './pos-product-cart-adapter';
import {
  buildProductsUrl,
  fetchProducts,
  fetchProductsFromUrl,
  findExactProductByCode,
  getDefaultFetchLimit,
  hasProductSearchRoute,
  hasSelectedBodega,
} from './pos-product-data';
import { createScannerDetector } from './pos-product-scanner';

let allProducts = [];
let lastFetchSeq = 0;

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

function clearSearchInput() {
  const input = document.getElementById('item_descripcion');
  if (input) input.value = '';
}

function renderStatus(message, tone = 'muted') {
  const list = document.getElementById('product_list');
  const empty = document.getElementById('product_list_empty');
  if (!list || !empty) return;

  const toneClass = tone === 'error' ? 'text-red-500' : 'text-slate-400';

  list.innerHTML = '';
  empty.classList.remove('hidden');
  empty.innerHTML = `
    <p class="text-[13px] ${toneClass} text-center px-6">
      ${escapeHtml(message)}
    </p>
  `;
}

function getCurrentBodegaState(product) {
  const bodegaSelect = document.getElementById('bodega_id');
  const currentBodegaId = bodegaSelect ? parseInt(bodegaSelect.value, 10) : 0;
  const itemBodegaId = product?.bodega_id ? parseInt(product.bodega_id, 10) : 0;

  return {
    currentBodegaId,
    itemBodegaId,
    isCurrentBodega: currentBodegaId === itemBodegaId,
  };
}

function buildRuleHint(rules) {
  if (!rules || rules.length === 0) return '';

  const boxRule = rules.find((rule) => rule.unidades_por_caja && rule.precio_por_caja != null);
  const qtyRules = rules
    .filter((rule) => rule.cantidad_min && rule.precio_por_cantidad != null)
    .sort((a, b) => (a.cantidad_min || 0) - (b.cantidad_min || 0));

  const parts = [];

  if (qtyRules.length > 0) {
    const firstRule = qtyRules[0];
    const range = firstRule.cantidad_max != null
      ? `${firstRule.cantidad_min}-${firstRule.cantidad_max}`
      : `Desde ${firstRule.cantidad_min}`;

    parts.push(`${range}: ${formatMoney(firstRule.precio_por_cantidad)}`);
  }

  if (boxRule) {
    parts.push(`Caja ${boxRule.unidades_por_caja}u: ${formatMoney(boxRule.precio_por_caja)}`);
  }

  return parts.slice(0, 2).join(' · ');
}

function renderProductList(products, meta = {}) {
  const list = document.getElementById('product_list');
  const empty = document.getElementById('product_list_empty');
  if (!list || !empty) return;

  list.innerHTML = '';

  if (!products || products.length === 0) {
    renderStatus('No se encontraron productos. Ajusta tu busqueda.');
    return;
  }

  empty.classList.add('hidden');

  products.forEach((product) => {
    const unitPrice = getUnitPrice(product);
    const codigo = product.codigo_interno || product.codigo_barras || '';
    const ivaPct = getIvaPct(product);
    const priceRules = getPriceRules(product);
    const hint = buildRuleHint(priceRules);

    const { itemBodegaId, isCurrentBodega } = getCurrentBodegaState(product);
    const stock = Number(product.stock_actual || 0);
    const isZeroStock = stock <= 0;

    let bgClass = 'hover:bg-slate-100/80';
    let textClass = 'text-slate-800';

    if (!isCurrentBodega) bgClass = 'bg-amber-50 hover:bg-amber-100';
    if (isZeroStock) {
      bgClass = 'bg-slate-50 opacity-80';
      textClass = 'text-slate-400';
    }

    const perchasHtml = Array.isArray(product.perchas) && product.perchas.length > 0
      ? `<div class="mt-1 flex flex-wrap gap-1">
           ${product.perchas.map((percha) => `
             <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] bg-slate-100 text-slate-600 border border-slate-200">
               ${escapeHtml(percha.nombre)}: <b class="ml-1 text-slate-800">${percha.stock}</b>
             </span>
           `).join('')}
         </div>`
      : '';

    const bodegaBadge = !isCurrentBodega
      ? `<span class="text-[10px] bg-amber-200 text-amber-800 px-1.5 py-0.5 rounded font-bold ml-1">
           ${escapeHtml(product.bodega_nombre || `Bodega ${itemBodegaId}`)}
         </span>`
      : '';

    const stockHtml = isZeroStock
      ? `<span class="inline-block px-1.5 py-0.5 rounded bg-rose-100 text-rose-600 font-bold border border-rose-200">
           Stock: 0
         </span>`
      : `Stock: ${stock}`;

    const row = document.createElement('button');
    row.type = 'button';
    row.className = `w-full text-left px-3 py-2.5 flex items-center justify-between focus:outline-none transition border-b border-transparent ${bgClass}`;
    row.innerHTML = `
      <div class="flex flex-col min-w-0">
        <span class="text-[13px] font-semibold ${textClass} truncate flex items-center">
          ${escapeHtml(product.nombre || 'Producto sin nombre')}
          ${bodegaBadge}
        </span>
        <span class="text-[11px] text-slate-400">
          ${codigo ? 'Cod: ' + escapeHtml(codigo) : ''}
          ${product.unidad_medida ? ' · ' + escapeHtml(product.unidad_medida) : ''}
          · IVA: ${ivaPct}%
        </span>
        ${perchasHtml}
        ${hint ? `<span class="text-[11px] text-emerald-700 mt-0.5">${escapeHtml(hint)}</span>` : ''}
      </div>

      <div class="text-right">
        <p class="text-sm font-bold text-blue-700">${formatMoney(unitPrice)}</p>
        <div class="text-[10px] font-semibold text-slate-500 mt-0.5">${stockHtml}</div>
        ${product.categoria ? `<p class="text-[11px] text-slate-400">${escapeHtml(product.categoria)}</p>` : ''}
      </div>
    `;

    row.addEventListener('click', () => {
      addProductToCart(product);
    });

    list.appendChild(row);
  });

  const shownCount = Number(meta.shownCount || products.length);
  const totalCount = Number(meta.totalCount || products.length);
  if (totalCount > shownCount) {
    const hint = document.createElement('div');
    hint.className = 'px-3 py-2 text-[11px] text-slate-500 bg-slate-50 border-t border-slate-100';
    hint.textContent = meta.hasSearch
      ? `Mostrando ${shownCount} de ${totalCount} coincidencias. Escribe mas para acotar.`
      : `Mostrando ${shownCount} de ${totalCount} productos. Usa el buscador para filtrar mas rapido.`;
    list.appendChild(hint);
  }
}

async function loadProducts(searchTerm = '') {
  if (!hasSelectedBodega()) {
    allProducts = [];
    renderStatus('Selecciona una bodega para ver los productos con stock disponible.');
    return [];
  }

  if (!hasProductSearchRoute()) {
    renderStatus('No se encontro la ruta de productos.', 'error');
    return [];
  }

  renderStatus('Cargando productos de la bodega seleccionada...');

  try {
    const fetchSeq = ++lastFetchSeq;
    const url = buildProductsUrl(searchTerm, getDefaultFetchLimit(searchTerm));
    const data = await fetchProductsFromUrl(url);
    if (fetchSeq !== lastFetchSeq) return [];

    allProducts = Array.isArray(data) ? data : [];
    renderProductList(allProducts, {
      shownCount: allProducts.length,
      totalCount: allProducts.length,
      hasSearch: searchTerm.length > 0,
    });
    return allProducts;
  } catch (error) {
    console.error('Error cargando productos:', error);
    renderStatus('Ocurrio un error al cargar los productos.', 'error');
    return [];
  }
}

async function tryFastAddByCode(term, extra = '') {
  const product = await findExactProductByCode(term, allProducts);
  if (!product) return false;

  const added = addProductToCart(product, extra);
  if (!added) return false;

  clearSearchInput();
  loadProducts('');
  return true;
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
      loadProducts('');
    });
  }

  if (!input) return;

  const scannerDetector = createScannerDetector({
    onScan: async (scannedTerm) => {
      await tryFastAddByCode(scannedTerm, '(scanner)');
    },
  });

  input.addEventListener(
    'input',
    debounce((event) => {
      const term = event.target.value.trim();
      loadProducts(term);
    }, 200)
  );

  input.addEventListener('keydown', async (event) => {
    scannerDetector.handleKeydown(event);

    if (event.key !== 'Enter') return;

    event.preventDefault();
    scannerDetector.reset();

    const term = input.value.trim();
    if (!term) return;

    const exactAdded = await tryFastAddByCode(term, '(codigo)');
    if (exactAdded) return;

    const matches = await fetchProducts(term, getDefaultFetchLimit(term));
    allProducts = Array.isArray(matches) ? matches : [];

    if (allProducts.length === 1) {
      const added = addProductToCart(allProducts[0], '(busqueda)');
      if (!added) return;

      clearSearchInput();
      loadProducts('');
      return;
    }

    renderProductList(allProducts, {
      shownCount: allProducts.length,
      totalCount: allProducts.length,
      hasSearch: true,
    });
  });
}
