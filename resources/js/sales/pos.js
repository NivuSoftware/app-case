import { initCart } from './pos-cart';
import { initPayment } from './pos-payment';
import { initClientSelector } from './pos-client';
import { initProductSearch } from './pos-product-search';

document.addEventListener('DOMContentLoaded', () => {
    initCart();
    initPayment();
    initClientSelector();
    initProductSearch();
});
