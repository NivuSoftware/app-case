export function formatMoney(value) {
    const num = isNaN(value) ? 0 : Number(value);
    return '$ ' + num.toFixed(2);
}

export function showSaleAlert(message, isError = false) {
    const box = document.getElementById('sale-alert');
    const msg = document.getElementById('sale-alert-message');
    if (!box || !msg) return;

    msg.textContent = message;
    box.classList.remove('hidden');

    const container = box.querySelector('div');
    if (!container) return;

    container.classList.remove(
        'bg-green-50', 'border-green-200', 'text-green-800',
        'bg-red-50', 'border-red-200', 'text-red-800'
    );

    if (isError) {
        container.classList.add('bg-red-50', 'border-red-200', 'text-red-800');
    } else {
        container.classList.add('bg-green-50', 'border-green-200', 'text-green-800');
    }
}

export function hideSaleAlert() {
    const box = document.getElementById('sale-alert');
    if (box) box.classList.add('hidden');
}

// Exponer para el botón de cerrar del alert (si lo usas inline)
window.SalesUtils = {
    hideSaleAlert,
    showSaleAlert,
    formatMoney,
};
