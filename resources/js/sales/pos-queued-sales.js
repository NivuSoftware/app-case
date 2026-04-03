import { getCart, replaceCart } from "./pos-cart";
import {
  getSelectedClientSnapshot,
  restoreClientSelection,
} from "./pos-client";
import { formatMoney, showSaleAlert } from "./pos-utils";

let editingQueueId = null;
let queues = [];
let serverOffsetMs = 0;
let pollTimer = null;
let countdownTimer = null;

function getQueueListElements() {
  return {
    list: document.getElementById("queued-sales-list"),
    empty: document.getElementById("queued-sales-empty"),
    summary: document.getElementById("queued-sales-summary"),
  };
}

function getContext() {
  const cajaId = document.getElementById("caja_id")?.value?.trim() || "";
  const bodegaId = document.getElementById("bodega_id")?.value?.trim() || "";

  return { cajaId, bodegaId };
}

function getQueueBaseUrl() {
  return window.SALES_ROUTES?.queueBase || "/api/ventas/queue";
}

function getCsrfToken() {
  return (
    window.CSRF_TOKEN ||
    document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ||
    ""
  );
}

function escapeHtml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function formatRemaining(seconds) {
  const safe = Math.max(0, Number(seconds) || 0);
  const mins = Math.floor(safe / 60);
  const secs = safe % 60;
  return `${String(mins).padStart(2, "0")}:${String(secs).padStart(2, "0")}`;
}

function getServerNowMs() {
  return Date.now() - serverOffsetMs;
}

function getRemainingSeconds(queue) {
  if (queue.status === "PAUSED") {
    return Math.max(0, Number(queue.remaining_seconds) || 0);
  }

  if (queue.status !== "QUEUED" || !queue.execute_at) {
    return 0;
  }

  const executeAt = Date.parse(queue.execute_at);
  if (Number.isNaN(executeAt)) {
    return Math.max(0, Number(queue.remaining_seconds) || 0);
  }

  return Math.max(0, Math.ceil((executeAt - getServerNowMs()) / 1000));
}

function updateCountdownElements() {
  document.querySelectorAll("[data-queued-countdown]").forEach((el) => {
    const queueId = Number(el.dataset.queuedCountdown);
    const queue = queues.find((item) => Number(item.id) === queueId);
    if (!queue) return;
    el.textContent = formatRemaining(getRemainingSeconds(queue));
  });
}

function updateSummary(totalCount) {
  const { summary } = getQueueListElements();
  if (!summary) return;

  if (!totalCount) {
    summary.textContent = "Mostrando ultimas 3 de 0 en cola";
    return;
  }

  summary.textContent = `Mostrando ultimas 3 de ${totalCount} en cola`;
}

function renderQueues() {
  const { list, empty } = getQueueListElements();
  if (!list || !empty) return;

  list.innerHTML = "";
  const visible = queues.slice(0, 3);
  updateSummary(queues.length);

  if (!visible.length) {
    empty.classList.remove("hidden");
    return;
  }

  empty.classList.add("hidden");

  visible.forEach((queue, index) => {
    const stateTone =
      queue.status === "FAILED"
        ? "border-rose-200 bg-rose-50/80"
        : queue.status === "PAUSED"
          ? "border-amber-200 bg-amber-50/80"
          : queue.status === "PROCESSING"
            ? "border-blue-200 bg-blue-50/80"
            : "border-slate-200 bg-white/90";

    const overlayLabel =
      queue.status === "FAILED"
        ? escapeHtml(queue.last_error || "Error al emitir")
        : queue.status === "PAUSED"
          ? "Pausada"
          : queue.status === "PROCESSING"
            ? "Emitiendo..."
            : "Tiempo restante";

    const showPause = queue.status === "QUEUED";
    const showResume = queue.status === "PAUSED";
    const showEdit = ["QUEUED", "PAUSED", "FAILED"].includes(queue.status);
    const showCancel = ["QUEUED", "PAUSED", "FAILED"].includes(queue.status);
    const shouldShowCountdown = ["QUEUED", "PAUSED"].includes(queue.status);
    const countdownValue = formatRemaining(getRemainingSeconds(queue));
    const overlayStyle = getOverlayStyle(queue.status);
    const overlayTextStyle = "color:#fff;text-shadow:0 4px 16px rgba(0,0,0,0.55);";
    const primaryOverlayContent = shouldShowCountdown
      ? `
          <div class="flex flex-col items-center justify-center gap-1 text-center" style="${overlayTextStyle}">
            <div style="font-size:10px;font-weight:700;letter-spacing:0.28em;text-transform:uppercase;opacity:0.96;">
              ${overlayLabel}
            </div>
            <div data-queued-countdown="${queue.id}" style="font-size:52px;line-height:1;font-weight:900;letter-spacing:0.12em;font-variant-numeric:tabular-nums;">
              ${countdownValue}
            </div>
          </div>
        `
      : `
          <div class="max-w-[82%] text-center text-sm font-semibold leading-tight" style="${overlayTextStyle}">
            ${overlayLabel}
          </div>
        `;

    const article = document.createElement("article");
    article.className = `group relative isolate overflow-hidden rounded-2xl border shadow-sm ${stateTone}`;
    article.dataset.queueCard = String(queue.id);
    article.innerHTML = `
      <div class="absolute inset-0 z-20 rounded-2xl">
        <div class="absolute inset-0 rounded-2xl pointer-events-none" style="${overlayStyle}"></div>

        <div data-queue-overlay-countdown class="absolute inset-0 z-10 flex items-center justify-center px-4 pointer-events-none transition duration-200 ease-out opacity-100 scale-100">
          ${primaryOverlayContent}
        </div>

        <div data-queue-overlay-actions class="absolute inset-0 z-20 flex items-center justify-center px-4 opacity-0 scale-95 transition duration-200 ease-out pointer-events-none">
          <div class="pointer-events-auto rounded-2xl px-4 py-3 flex flex-wrap items-center justify-center gap-3" style="background:rgba(255,255,255,0.98);border:1px solid rgba(255,255,255,0.8);box-shadow:0 18px 40px rgba(15,23,42,0.25);">
            ${showPause ? `<button type="button" data-queue-action="pause" data-queue-id="${queue.id}" class="rounded-full bg-amber-100 px-3 py-1.5 text-[11px] font-semibold text-amber-800 hover:bg-amber-200">Pausar</button>` : ""}
            ${showResume ? `<button type="button" data-queue-action="resume" data-queue-id="${queue.id}" class="rounded-full bg-emerald-100 px-3 py-1.5 text-[11px] font-semibold text-emerald-800 hover:bg-emerald-200">Reanudar</button>` : ""}
            ${showEdit ? `<button type="button" data-queue-action="edit" data-queue-id="${queue.id}" class="rounded-full bg-blue-100 px-3 py-1.5 text-[11px] font-semibold text-blue-800 hover:bg-blue-200">Editar</button>` : ""}
            ${showCancel ? `<button type="button" data-queue-action="cancel" data-queue-id="${queue.id}" class="rounded-full bg-rose-100 px-3 py-1.5 text-[11px] font-semibold text-rose-800 hover:bg-rose-200">Cancelar</button>` : ""}
          </div>
        </div>
      </div>

      <div class="relative z-0 px-3 py-3 min-h-[148px]">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
              Factura ${index + 1}
            </p>
            <p class="text-sm font-semibold text-slate-800 truncate">
              ${escapeHtml(queue.client_name || "Consumidor final")}
            </p>
            <p class="text-[11px] text-slate-500 truncate">
              ${escapeHtml(queue.client_ident || "9999999999999")}
            </p>
          </div>
          <div class="text-right shrink-0">
            <p class="text-sm font-bold text-blue-700">${escapeHtml(formatMoney(queue.total || 0))}</p>
            <p class="text-[11px] text-slate-400">${escapeHtml(queue.reserved_num_factura || "")}</p>
          </div>
        </div>

        <div class="mt-2 flex items-center justify-between gap-2 text-[11px] text-slate-500">
          <span>${escapeHtml(`${queue.meta?.lineas || 0} linea(s) · ${queue.meta?.unidades || 0} und`)}</span>
          <span class="truncate text-right">${escapeHtml(queue.meta?.preview || "Sin detalle")}</span>
        </div>
      </div>
    `;

    list.appendChild(article);
  });
}

function setQueueCardHoverState(card, hovered) {
  const countdown = card?.querySelector("[data-queue-overlay-countdown]");
  const actions = card?.querySelector("[data-queue-overlay-actions]");
  if (!countdown || !actions) return;

  if (hovered) {
    countdown.style.opacity = "0";
    countdown.style.transform = "scale(0.95)";
    actions.style.opacity = "1";
    actions.style.transform = "scale(1)";
    actions.style.pointerEvents = "auto";
    return;
  }

  countdown.style.opacity = "1";
  countdown.style.transform = "scale(1)";
  actions.style.opacity = "0";
  actions.style.transform = "scale(0.95)";
  actions.style.pointerEvents = "none";
}

function getOverlayStyle(status) {
  if (status === "FAILED") {
    return "background:rgba(225,29,72,0.18);backdrop-filter:blur(1.5px);-webkit-backdrop-filter:blur(1.5px);";
  }

  if (status === "PAUSED") {
    return "background:rgba(245,158,11,0.16);backdrop-filter:blur(1.5px);-webkit-backdrop-filter:blur(1.5px);";
  }

  if (status === "PROCESSING") {
    return "background:rgba(2,132,199,0.16);backdrop-filter:blur(1.5px);-webkit-backdrop-filter:blur(1.5px);";
  }

  return "background:rgba(249,115,22,0.14);backdrop-filter:blur(1.5px);-webkit-backdrop-filter:blur(1.5px);";
}

async function fetchJson(url, options = {}) {
  const res = await fetch(url, {
    credentials: "same-origin",
    headers: {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
      "X-CSRF-TOKEN": getCsrfToken(),
      ...(options.body ? { "Content-Type": "application/json" } : {}),
      ...(options.headers || {}),
    },
    ...options,
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const firstError = Object.values(data?.errors || {}).flat().find(Boolean);
    throw new Error(firstError || data?.message || "No se pudo completar la accion.");
  }

  return data;
}

function resetDraftInputs() {
  const referenceInput = document.getElementById("payment_modal_referencia");
  const observationsInput = document.getElementById("payment_modal_observaciones");
  const quickSearch = document.getElementById("client_quick_search");

  if (referenceInput) referenceInput.value = "";
  if (observationsInput) observationsInput.value = "";
  if (quickSearch) quickSearch.value = "";
}

function currentDraftNeedsConfirmation() {
  if (getCart().length > 0) return true;

  const currentClient = getSelectedClientSnapshot();
  return !!(currentClient?.clientId && !currentClient?.isConsumidorFinal);
}

async function confirmReplaceCurrentDraft() {
  if (!currentDraftNeedsConfirmation()) return true;

  if (window.Swal) {
    const result = await window.Swal.fire({
      title: "Reemplazar venta actual",
      text: "La venta que tienes en pantalla se reemplazara por la factura en cola seleccionada.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Si, editar",
      cancelButtonText: "Cancelar",
    });

    return !!result.isConfirmed;
  }

  return window.confirm(
    "La venta que tienes en pantalla se reemplazara por la factura en cola seleccionada. Deseas continuar?"
  );
}

async function loadQueues() {
  const { cajaId, bodegaId } = getContext();
  const { list, empty } = getQueueListElements();
  if (!cajaId || !bodegaId || !list || !empty) return;

  try {
    const url = new URL(window.SALES_ROUTES?.queueIndex || "/api/ventas/queue", window.location.origin);
    url.searchParams.set("caja_id", cajaId);
    url.searchParams.set("bodega_id", bodegaId);

    const data = await fetchJson(url.toString(), { method: "GET" });
    const serverNowMs = Date.parse(data?.server_now || "");
    if (!Number.isNaN(serverNowMs)) {
      serverOffsetMs = Date.now() - serverNowMs;
    }

    queues = Array.isArray(data?.items) ? data.items : [];
    renderQueues();
    updateCountdownElements();
  } catch (error) {
    console.error("[POS] Error cargando facturas en cola:", error);
  }
}

async function handleQueueAction(action, queueId) {
  const base = getQueueBaseUrl();

  try {
    if (action === "pause") {
      await fetchJson(`${base}/${queueId}/pause`, { method: "POST" });
      showSaleAlert("Factura en cola pausada.");
      await loadQueues();
      return;
    }

    if (action === "resume") {
      await fetchJson(`${base}/${queueId}/resume`, { method: "POST" });
      showSaleAlert("Factura en cola reanudada.");
      await loadQueues();
      return;
    }

    if (action === "cancel") {
      if (window.Swal) {
        const result = await window.Swal.fire({
          title: "Cancelar factura en cola",
          text: "Se eliminara la factura en cola y no se emitira nada.",
          icon: "warning",
          showCancelButton: true,
          confirmButtonText: "Si, cancelar",
          cancelButtonText: "Volver",
        });

        if (!result.isConfirmed) return;
      } else if (!window.confirm("Se eliminara la factura en cola y no se emitira nada. Deseas continuar?")) {
        return;
      }

      await fetchJson(`${base}/${queueId}`, { method: "DELETE" });
      if (editingQueueId && Number(editingQueueId) === Number(queueId)) {
        editingQueueId = null;
      }
      showSaleAlert("Factura en cola cancelada.");
      await loadQueues();
      return;
    }

    if (action === "edit") {
      const confirmed = await confirmReplaceCurrentDraft();
      if (!confirmed) return;

      const data = await fetchJson(`${base}/${queueId}/edit`, { method: "POST" });
      const payload = data?.data?.payload || {};
      editingQueueId = data?.data?.queue_id ? String(data.data.queue_id) : null;

      replaceCart(Array.isArray(payload.cart) ? payload.cart : []);
      await restoreClientSelection(payload.client || null);
      resetDraftInputs();

      showSaleAlert("Factura en cola cargada para editar.");
      await loadQueues();
    }
  } catch (error) {
    console.error("[POS] Error operando factura en cola:", error);
    showSaleAlert(error.message || "No se pudo completar la accion.", true);
  }
}

export async function refreshQueuedSales() {
  await loadQueues();
}

export function getQueuedSaleEditingId() {
  return editingQueueId;
}

export function clearQueuedSaleEditingId() {
  editingQueueId = null;
}

export function initQueuedSales() {
  const { list } = getQueueListElements();
  if (!list) return;

  list.addEventListener("mouseover", (event) => {
    const card = event.target.closest("[data-queue-card]");
    if (!card || !list.contains(card)) return;
    setQueueCardHoverState(card, true);
  });

  list.addEventListener("mouseout", (event) => {
    const card = event.target.closest("[data-queue-card]");
    if (!card || !list.contains(card)) return;

    const related = event.relatedTarget;
    if (related instanceof Node && card.contains(related)) {
      return;
    }

    setQueueCardHoverState(card, false);
  });

  list.addEventListener("click", async (event) => {
    const button = event.target.closest("[data-queue-action]");
    if (!button) return;

    await handleQueueAction(button.dataset.queueAction, button.dataset.queueId);
  });

  loadQueues();

  if (pollTimer) window.clearInterval(pollTimer);
  pollTimer = window.setInterval(loadQueues, 5000);

  if (countdownTimer) window.clearInterval(countdownTimer);
  countdownTimer = window.setInterval(updateCountdownElements, 1000);
}
