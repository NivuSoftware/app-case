import { showSaleAlert } from './pos-utils';

function debounce(fn, delay = 300) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), delay);
    };
}

async function searchClients(term) {
    const routes = window.SALES_ROUTES || {};
    if (!routes.clientSearch || term.length < 2) {
        renderClientResults([]);
        return;
    }

    try {
        const res = await fetch(`${routes.clientSearch}?q=${encodeURIComponent(term)}`, {
            headers: { 'Accept': 'application/json' },
        });
        if (!res.ok) {
            renderClientResults([]);
            return;
        }
        const data = await res.json();
        renderClientResults(data || []);
    } catch (e) {
        console.error(e);
        renderClientResults([]);
    }
}

function renderClientResults(clients) {
    const tbody = document.getElementById('client_results');
    const empty = document.getElementById('client_results_empty');
    if (!tbody || !empty) return;

    tbody.innerHTML = '';

    if (!clients.length) {
        empty.textContent = 'No se encontraron clientes.';
        empty.classList.remove('hidden');
        return;
    }

    empty.classList.add('hidden');

    clients.forEach((c) => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 cursor-pointer';
        tr.dataset.clientId = c.id;
        tr.dataset.clientName = c.business || c.nombre || '';
        tr.innerHTML = `
            <td class="px-3 py-2">
                <div class="font-semibold text-gray-800 text-xs">${c.business || c.nombre}</div>
            </td>
            <td class="px-3 py-2 text-xs text-gray-700">${c.identificacion || '-'}</td>
            <td class="px-3 py-2 text-xs text-gray-700">${c.tipo || c.tipo_identificacion || '-'}</td>
            <td class="px-3 py-2 text-center text-xs text-blue-600">
                Seleccionar
            </td>
        `;
        tbody.appendChild(tr);
    });
}

async function loadClientEmails(clientId) {
    const routes = window.SALES_ROUTES || {};
    if (!routes.clientEmailsBase) return;

    const select = document.getElementById('cliente_email');
    if (!select) return;

    select.innerHTML = '<option value="">Cargando correos...</option>';

    try {
        const res = await fetch(`${routes.clientEmailsBase}/${clientId}/emails`, {
            headers: { 'Accept': 'application/json' },
        });
        if (!res.ok) {
            select.innerHTML = '<option value="">Sin correos disponibles</option>';
            return;
        }

        const data = await res.json();
        select.innerHTML = '<option value="">-- Selecciona un correo --</option>';

        (data || []).forEach((e) => {
            const opt = document.createElement('option');
            opt.value = e.email;
            opt.textContent = e.email;
            select.appendChild(opt);
        });
    } catch (e) {
        console.error(e);
        select.innerHTML = '<option value="">Error al cargar correos</option>';
    }
}

export function initClientSelector() {
    const btnOpen = document.getElementById('btn-open-client-modal');
    const modal = document.getElementById('client-modal');
    if (!btnOpen || !modal) return;

    btnOpen.addEventListener('click', () => {
        modal.classList.remove('hidden');
    });

    modal.querySelectorAll('[data-client-close]').forEach(btn => {
        btn.addEventListener('click', () => modal.classList.add('hidden'));
    });

    const searchInput = document.getElementById('client_search_term');
    if (searchInput) {
        searchInput.addEventListener('input', debounce((e) => {
            const term = e.target.value.trim();
            if (!term) {
                renderClientResults([]);
                const empty = document.getElementById('client_results_empty');
                if (empty) {
                    empty.textContent = 'Escribe para buscar clientes.';
                    empty.classList.remove('hidden');
                }
                return;
            }
            searchClients(term);
        }, 300));
    }

    const tbody = document.getElementById('client_results');
    if (tbody) {
        tbody.addEventListener('click', (e) => {
            const tr = e.target.closest('tr[data-client-id]');
            if (!tr) return;

            const clientId = tr.dataset.clientId;
            const clientName = tr.dataset.clientName;

            const inputId = document.getElementById('client_id');
            const inputName = document.getElementById('cliente_nombre');

            if (inputId) inputId.value = clientId;
            if (inputName) inputName.value = clientName || '';

            modal.classList.add('hidden');

            if (clientId) {
                loadClientEmails(clientId);
            } else {
                showSaleAlert('No se pudo obtener el ID del cliente seleccionado.', true);
            }
        });
    }
}
