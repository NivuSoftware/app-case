<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-blue-900 leading-tight">
                {{ __('Facturas') }}
            </h2>
            <button onclick="window.location.href='{{ route('reporteria.menu') }}'"
                class="text-blue-700 hover:text-blue-900 transition flex items-center space-x-1" title="Regresar">
                <x-heroicon-s-arrow-left class="w-5 h-5" />
                <span>Atras</span>
            </button>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 space-y-6">
            <div class="bg-white border border-blue-100 rounded-xl p-4">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-end">
                    <div class="lg:col-span-2">
                        <div class="text-sm text-blue-900 font-semibold mb-2">Filtros de busqueda</div>
                        <form method="GET" action="{{ route('reporteria.invoices.index') }}"
                            class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                            <div>
                                <label for="desde" class="block text-xs font-semibold text-blue-800 mb-1">Desde</label>
                                <input id="desde" type="date" name="desde" value="{{ $desde ?? '' }}"
                                    class="w-full border border-blue-100 rounded px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label for="hasta" class="block text-xs font-semibold text-blue-800 mb-1">Hasta</label>
                                <input id="hasta" type="date" name="hasta" value="{{ $hasta ?? '' }}"
                                    class="w-full border border-blue-100 rounded px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label for="identificacion" class="block text-xs font-semibold text-blue-800 mb-1">
                                    RUC, cedula o pasaporte
                                </label>
                                <input id="identificacion" type="text" name="identificacion"
                                    value="{{ $identificacion ?? '' }}" placeholder="Ej: 0912345678"
                                    class="w-full border border-blue-100 rounded px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label for="numero_factura" class="block text-xs font-semibold text-blue-800 mb-1">
                                    Numero de factura
                                </label>
                                <input id="numero_factura" type="text" name="numero_factura"
                                    value="{{ $numero_factura ?? '' }}" placeholder="001-001-000000123"
                                    class="w-full border border-blue-100 rounded px-3 py-2 text-sm" />
                            </div>
                            <div class="md:col-span-2 xl:col-span-4 flex flex-wrap gap-2">
                                <button type="submit"
                                    class="text-sm px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
                                    Buscar
                                </button>
                                <a href="{{ route('reporteria.invoices.index') }}"
                                    class="text-sm px-4 py-2 rounded bg-blue-100 text-blue-800 hover:bg-blue-200">
                                    Limpiar
                                </a>
                            </div>
                        </form>
                    </div>
                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                        <div class="text-sm text-blue-900 font-semibold">Vista de facturas</div>
                        <div class="text-xs text-blue-800 mt-1">
                            Consulta facturas por rango de fecha, identificacion del cliente o numero de factura.
                        </div>
                        <div class="text-xs text-blue-800 mt-2">
                            El boton <span class="font-semibold">VER RIDE</span> se habilita cuando la factura ya tiene RIDE
                            o cuando el comprobante esta autorizado para generarlo.
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm border border-blue-100 rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-blue-100 text-sm">
                        <thead class="bg-blue-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-blue-900">Fecha</th>
                                <th class="px-4 py-3 text-left font-semibold text-blue-900">Factura</th>
                                <th class="px-4 py-3 text-left font-semibold text-blue-900">Cliente</th>
                                <th class="px-4 py-3 text-left font-semibold text-blue-900">Identificacion</th>
                                <th class="px-4 py-3 text-right font-semibold text-blue-900">Total</th>
                                <th class="px-4 py-3 text-left font-semibold text-blue-900">Estado SRI</th>
                                <th class="px-4 py-3 text-left font-semibold text-blue-900">Accion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-blue-100">
                            @forelse ($invoices as $sale)
                                @php
                                    $client = $sale->client;
                                    $invoice = $sale->electronicInvoice;
                                    $estadoSri = strtoupper((string) ($invoice->estado_sri ?? ''));
                                    $canViewRide = !empty($invoice?->ride_pdf_path) || $estadoSri === 'AUTORIZADO';
                                    $canResend = $estadoSri === 'AUTORIZADO';
                                    $primaryEmail = $sale->email_destino ?? $sale->clientEmail?->email ?? $client?->emails?->pluck('email')->filter()->first();
                                    $registeredEmails = collect($client?->emails ?? [])
                                        ->pluck('email')
                                        ->map(fn ($email) => mb_strtolower(trim((string) $email)))
                                        ->filter()
                                        ->unique()
                                        ->values();
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-blue-900 whitespace-nowrap">
                                        {{ $sale->fecha_venta?->format('Y-m-d H:i') ?? 'N/D' }}
                                    </td>
                                    <td class="px-4 py-3 text-blue-900 whitespace-nowrap">
                                        <div class="font-semibold">{{ $sale->num_factura ?? 'N/D' }}</div>
                                        <div class="text-xs text-blue-700">Venta #{{ $sale->id }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-blue-900">
                                        {{ $client?->business ?? 'Consumidor final' }}
                                    </td>
                                    <td class="px-4 py-3 text-blue-900 whitespace-nowrap">
                                        <div>{{ $client?->tipo_identificacion ?? 'N/D' }}</div>
                                        <div class="text-xs text-blue-700">{{ $client?->identificacion ?? 'N/D' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right text-blue-900 whitespace-nowrap">
                                        ${{ number_format((float) ($sale->total ?? 0), 2) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($estadoSri === 'AUTORIZADO')
                                            <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">AUTORIZADO</span>
                                        @elseif ($estadoSri === 'RECHAZADO')
                                            <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-800">RECHAZADO</span>
                                        @elseif ($estadoSri !== '')
                                            <span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-800">{{ $estadoSri }}</span>
                                        @else
                                            <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-700">SIN COMPROBANTE</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex flex-wrap gap-2">
                                            @if ($canViewRide)
                                                <a href="{{ route('reporteria.invoices.ride', $sale->id) }}" target="_blank"
                                                    class="inline-flex items-center text-xs px-3 py-1 rounded bg-blue-600 text-white hover:bg-blue-700">
                                                    VER RIDE
                                                </a>
                                            @else
                                                <span class="inline-flex items-center text-xs px-3 py-1 rounded bg-gray-100 text-gray-500">
                                                    RIDE no disponible
                                                </span>
                                            @endif

                                            @if ($canResend)
                                                <button
                                                    type="button"
                                                    class="js-resend-invoice inline-flex items-center text-xs px-3 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700"
                                                    data-sale-id="{{ $sale->id }}"
                                                    data-num-factura="{{ $sale->num_factura ?? 'N/D' }}"
                                                    data-client-name="{{ $client?->business ?? 'Consumidor final' }}"
                                                    data-primary-email="{{ $primaryEmail ?? '' }}"
                                                    data-registered-emails='@json($registeredEmails->all())'>
                                                    REENVIAR FACTURA
                                                </button>
                                            @else
                                                <span class="inline-flex items-center text-xs px-3 py-1 rounded bg-gray-100 text-gray-500">
                                                    Reenvio no disponible
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-6 text-center text-blue-700" colspan="7">
                                        No se encontraron facturas con los filtros actuales.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($invoices->hasPages())
                    <div class="p-4">
                        {{ $invoices->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div id="resend-invoice-modal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-blue-100">
                    <div>
                        <div class="text-sm font-semibold text-blue-900">Reenviar factura</div>
                        <div class="text-xs text-blue-700" id="resend-invoice-title">Factura</div>
                    </div>
                    <button id="resend-invoice-close" class="text-blue-700 hover:text-blue-900">Cerrar</button>
                </div>

                <form id="resend-invoice-form" class="p-4 space-y-4">
                    <input type="hidden" id="resend-sale-id" />

                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 space-y-1">
                        <div class="text-sm font-semibold text-blue-900" id="resend-client-name">Cliente</div>
                        <div class="text-xs text-blue-800">
                            Se reenviará el mismo correo automático de factura autorizada con sus adjuntos.
                        </div>
                        <div class="text-xs text-blue-800">
                            Los correos nuevos que agregues también se guardarán para este cliente.
                        </div>
                    </div>

                    <div>
                        <label for="resend-primary-email" class="block text-xs font-semibold text-blue-800 mb-1">
                            Correo principal a enviar
                        </label>
                        <input id="resend-primary-email" type="email"
                            class="w-full border border-blue-100 rounded px-3 py-2 text-sm"
                            placeholder="cliente@correo.com" required />
                    </div>

                    <div>
                        <div class="text-xs font-semibold text-blue-800 mb-2">Correos registrados del cliente</div>
                        <div id="resend-registered-emails" class="space-y-2"></div>
                        <div id="resend-registered-empty" class="text-xs text-gray-500 hidden">
                            Este cliente no tiene otros correos registrados.
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <div class="text-xs font-semibold text-blue-800">Agregar nuevos correos</div>
                            <button type="button" id="resend-add-email"
                                class="text-xs px-3 py-1 rounded bg-blue-100 text-blue-800 hover:bg-blue-200">
                                + Agregar correo
                            </button>
                        </div>
                        <div id="resend-new-emails" class="space-y-2"></div>
                    </div>

                    <div id="resend-invoice-message" class="hidden text-sm rounded px-3 py-2"></div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" id="resend-invoice-cancel"
                            class="text-sm px-4 py-2 rounded bg-gray-100 text-gray-700 hover:bg-gray-200">
                            Cancelar
                        </button>
                        <button type="submit" id="resend-invoice-submit"
                            class="text-sm px-4 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700">
                            Reenviar factura
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('resend-invoice-modal');
            const closeBtn = document.getElementById('resend-invoice-close');
            const cancelBtn = document.getElementById('resend-invoice-cancel');
            const form = document.getElementById('resend-invoice-form');
            const saleIdInput = document.getElementById('resend-sale-id');
            const titleEl = document.getElementById('resend-invoice-title');
            const clientNameEl = document.getElementById('resend-client-name');
            const primaryEmailEl = document.getElementById('resend-primary-email');
            const registeredWrapper = document.getElementById('resend-registered-emails');
            const registeredEmptyEl = document.getElementById('resend-registered-empty');
            const newEmailsWrapper = document.getElementById('resend-new-emails');
            const addEmailBtn = document.getElementById('resend-add-email');
            const submitBtn = document.getElementById('resend-invoice-submit');
            const messageEl = document.getElementById('resend-invoice-message');
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            function openModal() {
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
            }

            function clearChildren(node) {
                while (node.firstChild) node.removeChild(node.firstChild);
            }

            function setMessage(text, type) {
                if (!text) {
                    messageEl.textContent = '';
                    messageEl.className = 'hidden text-sm rounded px-3 py-2';
                    return;
                }

                const base = 'text-sm rounded px-3 py-2';
                const tone = type === 'error'
                    ? ' bg-red-50 text-red-700 border border-red-100'
                    : ' bg-emerald-50 text-emerald-700 border border-emerald-100';

                messageEl.textContent = text;
                messageEl.className = base + tone;
            }

            function createRegisteredEmailRow(email) {
                const label = document.createElement('label');
                label.className = 'flex items-center gap-2 text-sm text-blue-900';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.value = email;
                checkbox.className = 'rounded border-blue-200 text-emerald-600 focus:ring-emerald-500';

                const span = document.createElement('span');
                span.textContent = email;

                label.appendChild(checkbox);
                label.appendChild(span);

                return label;
            }

            function createNewEmailRow(value = '') {
                const row = document.createElement('div');
                row.className = 'flex gap-2';

                const input = document.createElement('input');
                input.type = 'email';
                input.value = value;
                input.placeholder = 'nuevo@correo.com';
                input.className = 'js-new-email w-full border border-blue-100 rounded px-3 py-2 text-sm';

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.textContent = 'Quitar';
                removeBtn.className = 'text-xs px-3 py-2 rounded bg-red-50 text-red-700 hover:bg-red-100';
                removeBtn.addEventListener('click', () => row.remove());

                row.appendChild(input);
                row.appendChild(removeBtn);

                return row;
            }

            function fillModal(button) {
                const saleId = button.getAttribute('data-sale-id') || '';
                const numFactura = button.getAttribute('data-num-factura') || 'N/D';
                const clientName = button.getAttribute('data-client-name') || 'Cliente';
                const primaryEmail = button.getAttribute('data-primary-email') || '';
                let registeredEmails = [];

                try {
                    registeredEmails = JSON.parse(button.getAttribute('data-registered-emails') || '[]');
                } catch (error) {
                    registeredEmails = [];
                }

                saleIdInput.value = saleId;
                titleEl.textContent = `Factura ${numFactura}`;
                clientNameEl.textContent = clientName;
                primaryEmailEl.value = primaryEmail;
                clearChildren(registeredWrapper);
                clearChildren(newEmailsWrapper);
                setMessage('', 'success');

                const normalizedPrimary = primaryEmail.trim().toLowerCase();
                const filteredRegistered = registeredEmails
                    .map((email) => (email || '').trim().toLowerCase())
                    .filter((email, index, arr) => email && arr.indexOf(email) === index && email !== normalizedPrimary);

                if (filteredRegistered.length) {
                    registeredEmptyEl.classList.add('hidden');
                    filteredRegistered.forEach((email) => {
                        registeredWrapper.appendChild(createRegisteredEmailRow(email));
                    });
                } else {
                    registeredEmptyEl.classList.remove('hidden');
                }
            }

            document.querySelectorAll('.js-resend-invoice').forEach((button) => {
                button.addEventListener('click', () => {
                    fillModal(button);
                    openModal();
                });
            });

            addEmailBtn.addEventListener('click', () => {
                newEmailsWrapper.appendChild(createNewEmailRow());
            });

            function closeAndReset() {
                form.reset();
                clearChildren(registeredWrapper);
                clearChildren(newEmailsWrapper);
                setMessage('', 'success');
                closeModal();
            }

            closeBtn.addEventListener('click', closeAndReset);
            cancelBtn.addEventListener('click', closeAndReset);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeAndReset();
                }
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const saleId = saleIdInput.value;
                const emailDestino = (primaryEmailEl.value || '').trim();
                const selectedEmails = Array.from(
                    registeredWrapper.querySelectorAll('input[type="checkbox"]:checked')
                ).map((input) => input.value);
                const newEmails = Array.from(
                    newEmailsWrapper.querySelectorAll('.js-new-email')
                ).map((input) => input.value.trim()).filter(Boolean);

                if (!saleId) {
                    setMessage('No se encontró la factura seleccionada.', 'error');
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.textContent = 'Reenviando...';
                setMessage('', 'success');

                try {
                    const response = await fetch(`/reporteria/facturas/${saleId}/reenviar-factura`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            email_destino: emailDestino,
                            selected_emails: selectedEmails,
                            new_emails: newEmails,
                        }),
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        const firstError = Object.values(data?.errors || {})[0];
                        const message = Array.isArray(firstError) ? firstError[0] : (data?.message || 'No se pudo reenviar la factura.');
                        throw new Error(message);
                    }

                    setMessage(data?.message || 'Correo reenviado correctamente.', 'success');
                    setTimeout(() => {
                        closeAndReset();
                    }, 900);
                } catch (error) {
                    setMessage(error?.message || 'No se pudo reenviar la factura.', 'error');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Reenviar factura';
                }
            });
        })();
    </script>
</x-app-layout>
