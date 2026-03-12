<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
   <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        

        <title>{{ config('app.name', 'CASE APP') }}</title>

        <!-- App Icon / Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('case.png') }}">
        <link rel="shortcut icon" type="image/png" href="{{ asset('lg.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('lg.png') }}">

        <!-- Optional PWA manifest (future use) -->
        <link rel="manifest" href="/site.webmanifest">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    </head>

    @php
        $isVentasPos = request()->routeIs('ventas.index');
        $disableUppercase = request()->routeIs('usuarios.*') || request()->routeIs('users.*');
    @endphp

    <body class="font-sans antialiased {{ $disableUppercase ? 'no-uppercase' : '' }}">
        <div class="min-h-screen bg-gray-100">
            @if($isVentasPos)
                <button
                    type="button"
                    id="ventas-nav-toggle"
                    data-open="0"
                    class="fixed top-2 right-2 z-50 inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white shadow-lg hover:bg-slate-800"
                >
                    Abrir menú
                </button>
            @endif

            <div id="global-nav" class="{{ $isVentasPos ? 'hidden' : '' }}">
                @include('layouts.navigation')
            </div>

            <!-- Page Heading -->
            @isset($header)
                <header id="global-page-header" class="bg-white shadow {{ $isVentasPos ? 'hidden' : '' }}">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
        <script>
            (() => {
                const STORAGE_KEY = 'product_import_tracker_v1';
                const POLL_MS = 5000;
                let timer = null;
                let isPolling = false;

                function safeParse(value, fallback) {
                    try {
                        return value ? JSON.parse(value) : fallback;
                    } catch {
                        return fallback;
                    }
                }

                function getState() {
                    return safeParse(localStorage.getItem(STORAGE_KEY), {
                        pending: [],
                        finished: [],
                    });
                }

                function setState(state) {
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
                }

                function normalizeState(state) {
                    if (!state || typeof state !== 'object') {
                        return { pending: [], finished: [] };
                    }

                    if (!Array.isArray(state.pending)) state.pending = [];
                    if (!Array.isArray(state.finished)) state.finished = [];

                    state.pending = state.pending.filter(item => item && Number(item.id) > 0);
                    state.finished = state.finished.filter(item => Number(item) > 0);

                    return state;
                }

                function markFinished(importId) {
                    const state = normalizeState(getState());
                    state.pending = state.pending.filter(item => Number(item.id) !== Number(importId));
                    if (!state.finished.includes(Number(importId))) {
                        state.finished.push(Number(importId));
                    }
                    setState(state);
                }

                function trackImport(importId, meta = {}) {
                    const numericId = Number(importId);
                    if (!numericId) return;

                    const state = normalizeState(getState());
                    const exists = state.pending.some(item => Number(item.id) === numericId);
                    if (!exists) {
                        state.pending.push({
                            id: numericId,
                            startedAt: new Date().toISOString(),
                            filename: meta.filename || null,
                        });
                        setState(state);
                    }

                    ensurePolling();
                }

                async function pollPendingImports() {
                    if (isPolling) return;
                    isPolling = true;

                    try {
                        const state = normalizeState(getState());
                        if (!state.pending.length) {
                            stopPolling();
                            return;
                        }

                        for (const item of [...state.pending]) {
                            const importId = Number(item.id);
                            if (!importId) continue;

                            try {
                                const res = await fetch(`/productos/import/${importId}/status`, {
                                    headers: { 'Accept': 'application/json' },
                                    cache: 'no-store',
                                });

                                if (!res.ok) continue;
                                const st = await res.json();

                                if (st.status === 'completed') {
                                    markFinished(importId);
                                    const resumen = [
                                        `Filas procesadas: ${st.processed_rows || 0}`,
                                        `Productos creados: ${st.created_count || 0}`,
                                        `Filas con error: ${st.failed_count || 0}`,
                                    ].join('\n');

                                    const errorPreview = st.error_log
                                        ? `\n\nErrores:\n${String(st.error_log).split('\n').slice(0, 10).join('\n')}`
                                        : '';

                                    await Swal.fire({
                                        icon: 'success',
                                        title: 'Importacion finalizada',
                                        text: resumen + errorPreview,
                                    });

                                    window.dispatchEvent(new CustomEvent('product-import-finished', {
                                        detail: st,
                                    }));
                                } else if (st.status === 'failed') {
                                    markFinished(importId);
                                    await Swal.fire({
                                        icon: 'error',
                                        title: 'Importacion fallida',
                                        text: st.error_log || 'La importacion fallo.',
                                    });

                                    window.dispatchEvent(new CustomEvent('product-import-finished', {
                                        detail: st,
                                    }));
                                }
                            } catch {
                                // Se reintenta en el siguiente ciclo.
                            }
                        }
                    } finally {
                        isPolling = false;
                    }
                }

                function ensurePolling() {
                    if (timer) return;
                    timer = setInterval(pollPendingImports, POLL_MS);
                    pollPendingImports();
                }

                function stopPolling() {
                    if (!timer) return;
                    clearInterval(timer);
                    timer = null;
                }

                window.ProductImportTracker = {
                    track(importId, meta = {}) {
                        trackImport(importId, meta);
                    },
                    getState,
                };

                if (normalizeState(getState()).pending.length) {
                    ensurePolling();
                }

                window.addEventListener('storage', (event) => {
                    if (event.key !== STORAGE_KEY) return;
                    const state = normalizeState(getState());
                    if (state.pending.length) {
                        ensurePolling();
                    } else {
                        stopPolling();
                    }
                });
            })();
        </script>
        @if($isVentasPos)
            <script>
                (() => {
                    const nav = document.getElementById('global-nav');
                    const header = document.getElementById('global-page-header');
                    const btn = document.getElementById('ventas-nav-toggle');
                    if (!nav || !btn) return;

                    btn.addEventListener('click', () => {
                        nav.classList.toggle('hidden');
                        if (header) header.classList.toggle('hidden');
                        const isOpen = !nav.classList.contains('hidden');
                        btn.dataset.open = isOpen ? '1' : '0';
                        btn.textContent = isOpen ? 'Ocultar menú' : 'Abrir menú';
                    });
                })();
            </script>
        @endif
    </body>
</html>
