import './bootstrap';

import Alpine from 'alpinejs';
import './sales/pos';
import { Chart, registerables } from 'chart.js';

window.Alpine = Alpine;
Chart.register(...registerables);
window.Chart = Chart;

Alpine.start();

const UPPERCASE_SELECTOR = "input[type='text'], input[type='search'], input[type='email'], input[type='tel'], input[type='url'], textarea";

function shouldUppercase(el) {
    if (document.body?.classList.contains('no-uppercase')) return false;
    if (!el || !el.matches || !el.matches(UPPERCASE_SELECTOR)) return false;
    if (el.hasAttribute('data-no-uppercase')) return false;
    if (el.readOnly || el.disabled) return false;
    return true;
}

function enforceUppercase(el) {
    if (!shouldUppercase(el)) return;

    const start = el.selectionStart;
    const end = el.selectionEnd;
    const next = String(el.value || '').toUpperCase();

    if (el.value !== next) {
        el.value = next;
        if (typeof start === 'number' && typeof end === 'number') {
            el.setSelectionRange(start, end);
        }
    }
}

document.addEventListener('input', (event) => {
    enforceUppercase(event.target);
});

document.addEventListener('change', (event) => {
    enforceUppercase(event.target);
});

document.addEventListener('blur', (event) => {
    enforceUppercase(event.target);
}, true);
