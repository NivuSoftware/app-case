import { showSaleAlert } from './pos-utils';
import { addOrIncrementProduct } from './pos-cart';

function normalizeIvaPct(value) {
  const n = Number(value);
  return n === 0 ? 0 : 15;
}

export function getUnitPrice(product) {
  const priceObj = product?.price || {};
  return Number(priceObj.precio_unitario ?? product?.precio_unitario ?? 0);
}

export function getIvaPct(product) {
  return normalizeIvaPct(product?.iva_porcentaje);
}

export function getPriceRules(product) {
  const rules = product?.product_prices || product?.prices || product?.price_rules || [];
  if (!Array.isArray(rules)) return [];

  return rules
    .map((rule) => ({
      producto_id: Number(rule.producto_id ?? product.id),
      precio_unitario:
        rule.precio_unitario != null ? Number(rule.precio_unitario) : null,
      precio_por_cantidad:
        rule.precio_por_cantidad != null ? Number(rule.precio_por_cantidad) : null,
      cantidad_min: rule.cantidad_min != null ? Number(rule.cantidad_min) : null,
      cantidad_max: rule.cantidad_max != null ? Number(rule.cantidad_max) : null,
      precio_por_caja:
        rule.precio_por_caja != null ? Number(rule.precio_por_caja) : null,
      unidades_por_caja:
        rule.unidades_por_caja != null ? Number(rule.unidades_por_caja) : null,
      moneda: rule.moneda || 'USD',
    }))
    .filter((rule) => Number.isFinite(rule.producto_id));
}

export function notifyProductAdded(description, extra = '') {
  const msg = extra
    ? `Se anadio "${description}" al carrito ${extra}`
    : `Se anadio "${description}" al carrito.`;

  if (typeof showSaleAlert === 'function') {
    showSaleAlert(msg);
  } else if (window.SalesUtils?.showSaleAlert) {
    window.SalesUtils.showSaleAlert(msg);
  }
}

export function addProductToCart(product, extra = '') {
  const productoId = Number(product?.id);
  const descripcion = product?.nombre || 'Producto sin nombre';
  const precio = getUnitPrice(product);
  const ivaPorcentaje = getIvaPct(product);
  const priceRules = getPriceRules(product);

  if (!productoId || !descripcion || isNaN(precio)) {
    console.warn('[POS] Datos incompletos del producto al agregar rapido');
    return false;
  }

  addOrIncrementProduct({
    producto_id: productoId,
    descripcion,
    precio_unitario: precio,
    iva_porcentaje: ivaPorcentaje,
    cantidad: 1,
    price_rules: priceRules,
  });

  notifyProductAdded(descripcion, extra);
  return true;
}
