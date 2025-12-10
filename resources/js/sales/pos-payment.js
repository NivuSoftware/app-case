import { getCart, getTotals, clearCart } from './pos-cart';
import { formatMoney, showSaleAlert, hideSaleAlert } from './pos-utils';

function openPaymentModal() {
    const cart = getCart();
    if (cart.length === 0) {
        showSaleAlert('Debes agregar al menos un producto al carrito.', true);
        return;
    }

    const bodegaId = document.getElementById('bodega_id')?.value;
    if (!bodegaId) {
        showSaleAlert('Selecciona una bodega.', true);
        return;
    }

    const fechaVenta = document.getElementById('fecha_venta')?.value;
    if (!fechaVenta) {
        showSaleAlert('La fecha de venta es obligatoria.', true);
        return;
    }

    hideSaleAlert();

    const totals = getTotals();
    const totalEl = document.getElementById('payment_modal_total');
    if (totalEl) totalEl.textContent = formatMoney(totals.total);

    const inputRecibido = document.getElementById('payment_modal_monto_recibido');
    if (inputRecibido) {
        inputRecibido.value = totals.total.toFixed(2);
    }

    const emailSelect = document.getElementById('cliente_email');
    const emailPreview = document.getElementById('payment_modal_email_preview');
    if (emailSelect && emailPreview) {
        const opt = emailSelect.selectedOptions[0];
        emailPreview.textContent = opt && opt.value ? opt.value : '(sin correo seleccionado)';
    }

    recalcCambio();

    const modal = document.getElementById('payment-modal');
    if (modal) modal.classList.remove('hidden');
}

function closePaymentModal() {
    const modal = document.getElementById('payment-modal');
    if (modal) modal.classList.add('hidden');
}

function recalcCambio() {
    const totals = getTotals();
    const recibido = parseFloat(document.getElementById('payment_modal_monto_recibido')?.value || '0');
    const cambio = recibido - totals.total;

    const span = document.getElementById('payment_modal_cambio');
    if (span) span.textContent = formatMoney(cambio > 0 ? cambio : 0);
}

function showChangeModal(total, recibido, cambio) {
    const totalEl = document.getElementById('change_total');
    const recEl = document.getElementById('change_recibido');
    const camEl = document.getElementById('change_cambio');

    if (totalEl) totalEl.textContent = formatMoney(total);
    if (recEl) recEl.textContent = formatMoney(recibido);
    if (camEl) camEl.textContent = formatMoney(cambio > 0 ? cambio : 0);

    const modal = document.getElementById('change-modal');
    if (modal) modal.classList.remove('hidden');
}

function closeChangeModal() {
    const modal = document.getElementById('change-modal');
    if (modal) modal.classList.add('hidden');
}

async function submitSaleFromModal() {
    const cart = getCart();
    if (cart.length === 0) {
        showSaleAlert('Debes agregar al menos un producto al carrito.', true);
        return;
    }

    const { total } = getTotals();

    const bodegaId = document.getElementById('bodega_id')?.value;
    const fechaVenta = document.getElementById('fecha_venta')?.value;
    const tipoDocumento = document.getElementById('tipo_documento')?.value || 'FACTURA';
    const numFactura = document.getElementById('num_factura')?.value || null;
    const observacionesVenta = document.getElementById('sale_observaciones')?.value || null;

    if (!bodegaId || !fechaVenta) {
        showSaleAlert('Completa los datos de la venta.', true);
        return;
    }

    const recibido = parseFloat(document.getElementById('payment_modal_monto_recibido')?.value || '0');
    if (recibido < total) {
        showSaleAlert('El monto recibido no puede ser menor al total.', true);
        return;
    }

    const metodoSelect = document.getElementById('payment_modal_metodo');
    const metodo = metodoSelect?.value;
    const paymentMethodId = metodoSelect?.selectedOptions[0]?.dataset.id || null;

    const referencia = document.getElementById('payment_modal_referencia')?.value || null;
    const observacionesPago = document.getElementById('payment_modal_observaciones')?.value || null;

    const clientId = document.getElementById('client_id')?.value || null;
    const emailSelect = document.getElementById('cliente_email');
    const emailDestino = emailSelect && emailSelect.value ? emailSelect.value : null;

    const payload = {
        client_id: clientId || null,
        user_id: window.AUTH_USER_ID || null,
        bodega_id: bodegaId,
        fecha_venta: fechaVenta,
        tipo_documento: tipoDocumento,
        num_factura: numFactura,
        observaciones: observacionesVenta,
        items: cart.map(item => ({
            producto_id: item.producto_id,
            descripcion: item.descripcion,
            cantidad: item.cantidad,
            precio_unitario: item.precio_unitario,
            descuento: item.descuento,
            percha_id: null, 
        })),
        payment: {
            metodo,
            payment_method_id: paymentMethodId,
            monto_recibido: recibido,
            referencia,
            observaciones: observacionesPago,
            fecha_pago: fechaVenta,
        },
        email_destino: emailDestino,
    };

    const routes = window.SALES_ROUTES || {};
    if (!routes.store) {
        showSaleAlert('Ruta de venta no configurada.', true);
        return;
    }

    try {
        const res = await fetch(routes.store, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.CSRF_TOKEN,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        if (res.status === 422) {
            const data = await res.json();
            showSaleAlert(data?.message || 'Error de validación en la venta.', true);
            return;
        }

        if (!res.ok) {
            showSaleAlert('Ocurrió un error al registrar la venta.', true);
            return;
        }

        const data = await res.json();

        // Aquí backend debería: guardar venta, enviar a SRI, enviar correo, etc.
        // Front solo muestra éxito y cambio.
        showSaleAlert(data.message || 'Venta registrada correctamente.');

        const cambio = recibido - total;

        closePaymentModal();
        showChangeModal(total, recibido, cambio);

        // Limpiar carrito y formularios
        clearCart();
        document.getElementById('sale_observaciones') && (document.getElementById('sale_observaciones').value = '');
        document.getElementById('payment_modal_referencia') && (document.getElementById('payment_modal_referencia').value = '');
        document.getElementById('payment_modal_observaciones') && (document.getElementById('payment_modal_observaciones').value = '');

    } catch (e) {
        console.error(e);
        showSaleAlert('Error de comunicación con el servidor.', true);
    }
}

export function initPayment() {
    const btnOpen = document.getElementById('btn-open-payment-modal');
    if (btnOpen) btnOpen.addEventListener('click', openPaymentModal);

    const modal = document.getElementById('payment-modal');
    if (modal) {
        modal.querySelectorAll('[data-payment-close]').forEach(btn => {
            btn.addEventListener('click', closePaymentModal);
        });
    }

    const changeModal = document.getElementById('change-modal');
    if (changeModal) {
        changeModal.querySelectorAll('[data-change-close]').forEach(btn => {
            btn.addEventListener('click', closeChangeModal);
        });
    }

    const inputRecibido = document.getElementById('payment_modal_monto_recibido');
    if (inputRecibido) inputRecibido.addEventListener('input', recalcCambio);

    const btnConfirm = document.getElementById('btn-confirm-payment');
    if (btnConfirm) btnConfirm.addEventListener('click', submitSaleFromModal);
}
