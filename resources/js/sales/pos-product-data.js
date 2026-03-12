const POS_FETCH_LIMIT = 200;
const POS_SEARCH_LIMIT = 1000;

function getProductListElement() {
  return document.getElementById('product_list');
}

function getBodegaId() {
  const bodegaSelect = document.getElementById('bodega_id');
  return bodegaSelect ? String(bodegaSelect.value || '').trim() : '';
}

function resolveProductSearchUrl() {
  const list = getProductListElement();
  const routes = window.SALES_ROUTES || {};
  return routes.productSearch || list?.dataset?.productUrl || '';
}

export function normalizeCode(value) {
  return String(value || '').trim().toUpperCase();
}

export function isExactCodeMatch(product, term) {
  const needle = normalizeCode(term);
  if (!needle) return false;

  return normalizeCode(product?.codigo_interno) === needle
    || normalizeCode(product?.codigo_barras) === needle;
}

export function buildProductsUrl(searchTerm = '', limit = POS_SEARCH_LIMIT) {
  const bodegaId = getBodegaId();
  const baseUrl = resolveProductSearchUrl();

  if (!bodegaId || !baseUrl) return null;

  const separator = baseUrl.includes('?') ? '&' : '?';
  const params = new URLSearchParams();
  params.set('bodega_id', bodegaId);
  params.set('limit', String(limit));
  if (searchTerm) params.set('q', searchTerm);

  return `${baseUrl}${separator}${params.toString()}`;
}

export async function fetchProductsFromUrl(url) {
  const res = await fetch(url, {
    headers: { Accept: 'application/json' },
  });

  if (!res.ok) throw new Error('Error al cargar productos');

  const data = await res.json();
  return Array.isArray(data) ? data : [];
}

export async function fetchProducts(searchTerm = '', limit = POS_SEARCH_LIMIT) {
  const url = buildProductsUrl(searchTerm, limit);
  if (!url) return [];
  return fetchProductsFromUrl(url);
}

export async function findExactProductByCode(term, products = []) {
  const needle = normalizeCode(term);
  if (!needle) return null;

  const localMatch = products.find((product) => isExactCodeMatch(product, needle));
  if (localMatch) return localMatch;

  try {
    const remoteMatches = await fetchProducts(needle, 30);
    return remoteMatches.find((product) => isExactCodeMatch(product, needle)) || null;
  } catch (error) {
    console.error('[POS] Error buscando producto exacto por codigo:', error);
    return null;
  }
}

export function getDefaultFetchLimit(searchTerm = '') {
  return searchTerm ? POS_SEARCH_LIMIT : POS_FETCH_LIMIT;
}

export function hasSelectedBodega() {
  return getBodegaId() !== '';
}

export function hasProductSearchRoute() {
  return resolveProductSearchUrl() !== '';
}
