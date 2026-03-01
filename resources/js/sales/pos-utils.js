export function formatMoney(value) {
    const num = isNaN(value) ? 0 : Number(value);
    return '$ ' + num.toFixed(2);
}

export function showSaleAlert(message, isError = false) {
    const text = message || (isError ? 'Ocurrió un error.' : 'Operación realizada correctamente.');

    // 1) SWEETALERT2 (si está disponible)
    if (window.Swal) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: isError ? 'error' : 'success',
            title: text,
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true,
        });
    } else {
        // Fallback por si acaso
        console.log('[POS] Alert:', text);
    }

    // 2) (Opcional) actualizar la barrita superior #sale-alert
    const box = document.getElementById('sale-alert');
    const msg = document.getElementById('sale-alert-message');
    if (!box || !msg) return;

    msg.textContent = text;
    box.classList.remove('hidden');

    const container = box.querySelector('div');
    if (!container) return;

    
    container.classList.remove(
        'bg-emerald-50', 'border-emerald-200', 'text-emerald-800',
        'bg-red-100', 'border-red-100', 'text-red-800'
    );

    if (isError) {
        container.classList.add('bg-red-100', 'border-red-100', 'text-red-800');
    } else {
        container.classList.add('bg-emerald-50', 'border-emerald-200', 'text-emerald-800');
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
