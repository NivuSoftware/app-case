import { getCart, getCartSnapshot, getTotals, clearCart } from "./pos-cart";
import { getSelectedClientSnapshot } from "./pos-client";
import {
  clearQueuedSaleEditingId,
  getQueuedSaleEditingId,
  refreshQueuedSales,
} from "./pos-queued-sales";
import { formatMoney, showSaleAlert } from "./pos-utils";

const CASH_METHODS = ["EFECTIVO", "CASH"];
let submitting = false;
let lastSplitValidation = { valid: false, message: "" };

function getIvaEnabled() {
  const el = document.getElementById("toggle_iva_global");
  return el ? !!el.checked : true;
}

function getCajaId() {
  const el = document.getElementById("caja_id");
  const raw = el?.value ? String(el.value).trim() : "";
  const n = parseInt(raw, 10);
  return Number.isFinite(n) && n > 0 ? n : null;
}

function buildCashierOpenUrl() {
  const base = window.SALES_ROUTES?.cashierOpen || "/cashier/open";
  const cajaId = getCajaId();
  const bodegaId = document.getElementById("bodega_id")?.value || null;

  const url = new URL(base, window.location.origin);
  url.searchParams.set("return_to", window.location.href);
  if (bodegaId) url.searchParams.set("bodega_id", bodegaId);
  if (cajaId) url.searchParams.set("caja_id", String(cajaId));
  return url.toString();
}

function ensureCajaOrRedirect() {
  const cajaId = getCajaId();
  if (!cajaId) {
    window.location.href = buildCashierOpenUrl();
    return false;
  }
  return true;
}

function toNumber(value) {
  const normalized = String(value ?? "").trim().replace(",", ".");
  const n = Number.parseFloat(normalized);
  return Number.isFinite(n) ? n : 0;
}

function toCents(value) {
  return Math.round(toNumber(value) * 100);
}

function fromCents(cents) {
  return cents / 100;
}

function isCashMethod(method) {
  return CASH_METHODS.includes(String(method || "").trim().toUpperCase());
}

function getSimpleMethodSelect() {
  return document.getElementById("payment_modal_metodo");
}

function getSimpleMethodData() {
  const select = getSimpleMethodSelect();
  const option = select?.selectedOptions?.[0] || null;

  return {
    method: option?.value?.trim() || "",
    paymentMethodId: option?.dataset?.id ? Number(option.dataset.id) : null,
    isCash: isCashMethod(option?.value || ""),
  };
}

function getSaleObservations() {
  return document.getElementById("payment_modal_observaciones")?.value?.trim() || null;
}

function getClientContext() {
  const emailSelect = document.getElementById("cliente_email");
  const selectedOpt = emailSelect?.selectedOptions?.[0];

  return {
    clientId: document.getElementById("client_id")?.value || null,
    clientEmailId: emailSelect && emailSelect.value ? Number(emailSelect.value) : null,
    emailDestino: selectedOpt && selectedOpt.value ? selectedOpt.text : null,
    clientNameUI:
      document.getElementById("cliente_nombre")?.textContent?.trim()?.toUpperCase() || "",
    clientIdentUI:
      document.getElementById("cliente_identificacion")?.textContent?.trim() || "",
  };
}

function validateClientForAmount(totalUi) {
  const { clientId, clientNameUI, clientIdentUI } = getClientContext();
  const isCF =
    !clientId ||
    clientIdentUI === "9999999999999" ||
    clientNameUI === "CONSUMIDOR FINAL";

  if (totalUi >= 50 && isCF) {
    return "Para ventas de $50 o más, es obligatorio ingresar un cliente con datos y no Consumidor Final.";
  }

  return null;
}

function recalcSimpleCambio() {
  const total = Number(getTotals().total || 0);
  const receivedInput = document.getElementById("payment_modal_monto_recibido");
  const changeEl = document.getElementById("payment_modal_cambio");
  const { isCash } = getSimpleMethodData();

  if (!changeEl) return;

  if (!isCash) {
    changeEl.textContent = formatMoney(0);
    return;
  }

  const received = toNumber(receivedInput?.value);
  changeEl.textContent = formatMoney(Math.max(0, received - total));
}

function syncSimplePaymentToTotal(total) {
  const receivedInput = document.getElementById("payment_modal_monto_recibido");
  if (!receivedInput) return;

  if (getSimpleMethodData().isCash && document.activeElement !== receivedInput) {
    receivedInput.value = Number(total || 0).toFixed(2);
  }

  recalcSimpleCambio();
}

function buildSimplePayments(fechaVenta, totalUi) {
  const { method, paymentMethodId, isCash } = getSimpleMethodData();
  if (!method || !paymentMethodId) {
    throw new Error("Debes seleccionar un método de pago.");
  }

  const payment = {
    metodo: method,
    payment_method_id: paymentMethodId,
    monto: Number(totalUi || 0).toFixed(2),
    referencia: document.getElementById("payment_modal_referencia")?.value?.trim() || null,
    observaciones: getSaleObservations(),
    fecha_pago: fechaVenta,
  };

  if (isCash) {
    const received = toNumber(document.getElementById("payment_modal_monto_recibido")?.value);
    if (received < totalUi) {
      throw new Error("El monto recibido no puede ser menor al total de la venta.");
    }
    payment.monto_recibido = received.toFixed(2);
  }

  return [payment];
}

function getSplitModal() {
  return document.getElementById("split-payment-modal");
}

function openSplitModal() {
  updateSplitModalHeader();
  resetSplitRows();
  getSplitModal()?.classList.remove("hidden");
}

function closeSplitModal() {
  getSplitModal()?.classList.add("hidden");
}

function updateSplitModalHeader() {
  const totalEl = document.getElementById("split_payment_total");
  const emailEl = document.getElementById("split_payment_email_preview");
  const total = Number(getTotals().total || 0);
  const { emailDestino } = getClientContext();

  if (totalEl) totalEl.textContent = formatMoney(total);
  if (emailEl) emailEl.textContent = emailDestino || "(sin correo seleccionado)";
}

function getSplitRowsContainer() {
  return document.getElementById("split_payment_rows");
}

function getSplitTemplate() {
  return document.getElementById("split-payment-row-template");
}

function getSplitRows() {
  return Array.from(
    getSplitRowsContainer()?.querySelectorAll("[data-split-payment-row]") || []
  );
}

function normalizeSplitMethod(method = "") {
  return String(method || "").trim().toUpperCase();
}

function formatSplitMethodLabel(method = "") {
  return String(method || "")
    .trim()
    .toLowerCase()
    .split(/\s+/)
    .filter(Boolean)
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(" ");
}

function getSplitMethodButtons() {
  return Array.from(
    document.querySelectorAll("[data-split-method-button]") || []
  );
}

function getSplitMethodCount() {
  const template = getSplitTemplate();
  const select = template?.content?.querySelector("[data-split-payment-method]");
  if (!select) return 0;

  return Array.from(select.options).filter((option) => option.value.trim() !== "").length;
}

function getSplitRowParts(row) {
  return {
    method: row.querySelector("[data-split-payment-method]"),
    amount: row.querySelector("[data-split-payment-amount]"),
    reference: row.querySelector("[data-split-payment-reference]"),
    observations: row.querySelector("[data-split-payment-observations]"),
    remove: row.querySelector("[data-remove-split-payment-row]"),
  };
}

function getSplitRowState(row) {
  const parts = getSplitRowParts(row);
  const option = parts.method?.selectedOptions?.[0] || null;
  const method = option?.value?.trim() || "";
  const paymentMethodId = option?.dataset?.id ? Number(option.dataset.id) : null;
  const amountCents = toCents(parts.amount?.value);
  const receivedCents = amountCents;

  return {
    row,
    parts,
    method,
    paymentMethodId,
    amountCents,
    receivedCents,
    isCash: isCashMethod(method),
  };
}

function findSplitRowByMethod(method) {
  const methodKey = normalizeSplitMethod(method);
  return (
    getSplitRows().find(
      (row) => normalizeSplitMethod(getSplitRowState(row).method) === methodKey
    ) || null
  );
}

function focusSplitRow(row) {
  if (!row) return;

  row.scrollIntoView({ behavior: "smooth", block: "nearest" });
  const { amount } = getSplitRowParts(row);
  if (amount) {
    amount.focus();
    amount.select?.();
  }
}

function pickDefaultSplitMethod(existingMethods = [], preferred = null) {
  const template = getSplitTemplate();
  const select = template?.content?.querySelector("[data-split-payment-method]");
  if (!select) return "";

  const existing = new Set(existingMethods.map((method) => normalizeSplitMethod(method)));
  const options = Array.from(select.options).filter((option) => option.value.trim() !== "");

  if (preferred) {
    const preferredOption = options.find(
      (option) => normalizeSplitMethod(option.value) === normalizeSplitMethod(preferred)
    );
    if (preferredOption) return preferredOption.value;
  }

  const cashOption = options.find(
    (option) => normalizeSplitMethod(option.value) === "EFECTIVO"
  );
  if (cashOption && !existing.has("EFECTIVO")) {
    return cashOption.value;
  }

  return (
    options.find((option) => !existing.has(normalizeSplitMethod(option.value)))?.value || ""
  );
}

function addSplitRow(preferredMethod = null) {
  const container = getSplitRowsContainer();
  const template = getSplitTemplate();
  if (!container || !template) return null;

  const existingMethods = getSplitRows()
    .map((row) => getSplitRowState(row).method)
    .filter(Boolean);

  const fragment = template.content.cloneNode(true);
  const row = fragment.querySelector("[data-split-payment-row]");
  container.appendChild(fragment);

  const parts = getSplitRowParts(row);
  if (parts.method) {
    parts.method.value = pickDefaultSplitMethod(existingMethods, preferredMethod);
  }

  updateSplitRowUI(row);
  updateSplitControls();
  refreshSplitSummary();
  return row;
}

function resetSplitRows() {
  const container = getSplitRowsContainer();
  if (!container) return;

  container.innerHTML = "";
  addSplitRow(getSimpleMethodData().method || "EFECTIVO");
  syncSingleSplitToTotal(Number(getTotals().total || 0));
  refreshSplitSummary();
}

function updateSplitControls() {
  const rows = getSplitRows();
  const maxMethods = getSplitMethodCount();

  rows.forEach((row) => {
    const { remove } = getSplitRowParts(row);
    if (remove) {
      remove.disabled = rows.length === 1;
      remove.classList.toggle("opacity-50", rows.length === 1);
      remove.classList.toggle("cursor-not-allowed", rows.length === 1);
    }
  });

  const addButton = document.getElementById("btn-add-split-payment-row");
  if (addButton) {
    const disable = maxMethods > 0 && rows.length >= maxMethods;
    addButton.disabled = disable;
    addButton.classList.toggle("opacity-50", disable);
    addButton.classList.toggle("cursor-not-allowed", disable);
  }

  updateSplitMethodButtons();
}

function updateSplitRowUI(row) {
  const state = getSplitRowState(row);
  const label = row.querySelector("[data-split-payment-method-label]");

  if (label) {
    label.textContent = state.method
      ? formatSplitMethodLabel(state.method)
      : "Seleccione...";
  }

  row.dataset.methodKey = normalizeSplitMethod(state.method);
  return state;
}

function updateSplitMethodButtons() {
  const activeMethods = new Set(
    getSplitRows()
      .map((row) => normalizeSplitMethod(getSplitRowState(row).method))
      .filter(Boolean)
  );

  getSplitMethodButtons().forEach((button) => {
    const method = button.dataset.methodValue || "";
    const active = activeMethods.has(normalizeSplitMethod(method));
    const icon = button.querySelector("[data-split-method-button-icon]");

    button.classList.toggle("border-slate-300", !active);
    button.classList.toggle("bg-white", !active);
    button.classList.toggle("text-slate-800", !active);
    button.classList.toggle("hover:bg-slate-50", !active);
    button.classList.toggle("hover:border-slate-400", !active);
    button.classList.toggle("border-slate-900", active);
    button.classList.toggle("bg-slate-900", active);
    button.classList.toggle("text-white", active);
    button.classList.toggle("hover:bg-slate-900", active);
    button.classList.toggle("hover:border-slate-900", active);

    if (icon) {
      icon.textContent = active ? "✓" : "+";
      icon.classList.toggle("text-slate-400", !active);
      icon.classList.toggle("text-white", active);
    }
  });
}

function setSplitBalanceCardTone(tone) {
  const card = document.getElementById("split_payments_balance_card");
  if (!card) return;

  card.classList.remove(
    "border-slate-200",
    "bg-white",
    "border-emerald-200",
    "bg-emerald-50",
    "border-amber-200",
    "bg-amber-50",
    "border-rose-200",
    "bg-rose-50"
  );

  if (tone === "success") {
    card.classList.add("border-emerald-200", "bg-emerald-50");
    return;
  }

  if (tone === "danger") {
    card.classList.add("border-rose-200", "bg-rose-50");
    return;
  }

  if (tone === "warning") {
    card.classList.add("border-amber-200", "bg-amber-50");
    return;
  }

  card.classList.add("border-slate-200", "bg-white");
}

function setStatusClasses(element, tone) {
  if (!element) return;

  element.classList.remove(
    "text-emerald-600",
    "text-amber-600",
    "text-rose-600",
    "text-slate-500",
    "text-slate-600",
    "text-slate-800"
  );

  if (tone === "success") {
    element.classList.add("text-emerald-600");
    return;
  }

  if (tone === "danger") {
    element.classList.add("text-rose-600");
    return;
  }

  if (tone === "muted") {
    element.classList.add("text-slate-600");
    return;
  }

  element.classList.add("text-amber-600");
}

function updateSplitConfirmButton(isValid) {
  const button = document.getElementById("btn-confirm-split-payment");
  if (!button) return;

  button.disabled = submitting || !isValid;
  button.classList.toggle("opacity-50", button.disabled);
  button.classList.toggle("cursor-not-allowed", button.disabled);
}

function refreshSplitSummary() {
  const totalCents = toCents(getTotals().total || 0);
  const rows = getSplitRows();

  let declaredCents = 0;
  let cashRows = 0;
  let duplicateMethod = false;
  let incomplete = rows.length === 0;
  const seenMethods = new Set();

  rows.forEach((row) => {
    updateSplitRowUI(row);

    const state = getSplitRowState(row);
    if (!state.method || !state.paymentMethodId) {
      incomplete = true;
    }

    const methodKey = state.method.toUpperCase();
    if (state.method) {
      if (seenMethods.has(methodKey)) {
        duplicateMethod = true;
      }
      seenMethods.add(methodKey);
    }

    if (state.amountCents <= 0) {
      incomplete = true;
    } else {
      declaredCents += state.amountCents;
    }

    if (state.isCash) {
      cashRows += 1;
    }
  });

  const balanceCents = totalCents - declaredCents;
  const totalDeclaredEl = document.getElementById("split_payments_total_declared");
  const balanceEl = document.getElementById("split_payments_balance");
  const balanceLabelEl = document.getElementById("split_payments_balance_label");
  const statusEl = document.getElementById("split_payments_status_message");

  if (totalDeclaredEl) {
    totalDeclaredEl.textContent = `Declarado: ${formatMoney(fromCents(declaredCents))}`;
  }

  if (balanceEl) {
    balanceEl.textContent = formatMoney(Math.abs(fromCents(balanceCents)));
  }

  let message = "Agrega y completa los pagos para emitir la factura.";
  let tone = "warning";
  let valid = false;

  if (rows.length === 0) {
    message = "Debes agregar al menos un método de pago.";
  } else if (duplicateMethod) {
    message = "No puedes repetir el mismo método de pago en una factura.";
    tone = "danger";
  } else if (cashRows > 1) {
    message = "Solo se permite una línea de pago en efectivo por factura.";
    tone = "danger";
  } else if (incomplete) {
    message = "Completa método y monto en todas las líneas de pago.";
  } else if (balanceCents > 0) {
    message = `Falta declarar ${formatMoney(fromCents(balanceCents))} para completar la factura.`;
  } else if (balanceCents < 0) {
    message = `Los pagos exceden el total por ${formatMoney(fromCents(Math.abs(balanceCents)))}.`;
    tone = "danger";
  } else {
    message = "Pagos completos. La factura está lista para emitirse.";
    tone = "success";
    valid = true;
  }

  if (statusEl) {
    statusEl.textContent = message;
    setStatusClasses(statusEl, tone);
  }

  if (balanceEl) {
    const balanceTone =
      balanceCents === 0 ? "success" : balanceCents < 0 ? "danger" : "warning";
    setStatusClasses(balanceEl, balanceTone);
    setSplitBalanceCardTone(balanceTone);
  } else {
    setSplitBalanceCardTone("muted");
  }

  if (balanceLabelEl) {
    balanceLabelEl.textContent =
      balanceCents === 0 ? "Completo" : balanceCents < 0 ? "Excedente" : "Faltante";
    setStatusClasses(
      balanceLabelEl,
      balanceCents === 0 ? "success" : balanceCents < 0 ? "danger" : "warning"
    );
  }

  lastSplitValidation = { valid, message };
  updateSplitConfirmButton(valid);
  updateSplitControls();

  return lastSplitValidation;
}

function syncSingleSplitToTotal(total) {
  const rows = getSplitRows();
  if (rows.length !== 1) return;

  const state = getSplitRowState(rows[0]);
  const { amount } = state.parts;

  if (amount && document.activeElement !== amount) {
    amount.value = Number(total || 0).toFixed(2);
  }

  updateSplitRowUI(rows[0]);
}

function buildSplitPayments(fechaVenta) {
  return getSplitRows().map((row) => {
    const state = getSplitRowState(row);

    const payment = {
      metodo: state.method,
      payment_method_id: state.paymentMethodId,
      monto: fromCents(state.amountCents).toFixed(2),
      referencia: state.parts.reference?.value?.trim() || null,
      observaciones: state.parts.observations?.value?.trim() || null,
      fecha_pago: fechaVenta,
    };

    if (state.isCash) {
      payment.monto_recibido = fromCents(state.receivedCents || state.amountCents).toFixed(2);
    }

    return payment;
  });
}

async function submitSaleWithPayments(payments) {
  if (!ensureCajaOrRedirect()) return;
  if (submitting) return;

  submitting = true;
  updateSplitConfirmButton(lastSplitValidation.valid);
  const simpleBtn = document.getElementById("btn-confirm-payment");
  if (simpleBtn) {
    simpleBtn.disabled = true;
    simpleBtn.classList.add("opacity-50", "cursor-not-allowed");
  }

  try {
    const cart = getCart();
    if (!cart || cart.length === 0) {
      showSaleAlert("Debes agregar al menos un producto al carrito.", true);
      return;
    }

    const totals = getTotals();
    const totalUi = Number(totals.total || 0);
    const clientValidationError = validateClientForAmount(totalUi);
    if (clientValidationError) {
      showSaleAlert(clientValidationError, true);
      return;
    }

    const ivaEnabled = getIvaEnabled();
    const bodegaId = document.getElementById("bodega_id")?.value;
    const fechaVenta = document.getElementById("fecha_venta")?.value;
    const tipoDocumento =
      document.getElementById("tipo_documento")?.value || "FACTURA";
    const numFactura = document.getElementById("num_factura")?.value || null;
    const observacionesVenta = getSaleObservations();
    const { clientId, clientEmailId, emailDestino } = getClientContext();

    if (!bodegaId || !fechaVenta) {
      showSaleAlert("Completa los datos de la venta.", true);
      return;
    }

    const payload = {
      caja_id: getCajaId(),
      client_email_id: clientEmailId,
      email_destino: emailDestino,
      client_id: clientId || null,
      user_id: window.AUTH_USER_ID || null,
      bodega_id: bodegaId,
      fecha_venta: fechaVenta,
      tipo_documento: tipoDocumento,
      num_factura: numFactura,
      observaciones: observacionesVenta,
      iva_enabled: ivaEnabled,
      cart_snapshot: getCartSnapshot(),
      client_snapshot: getSelectedClientSnapshot(),
      items: cart.map((item) => {
        const qty = Number(item.cantidad) || 1;
        const lineSubtotal =
          Number(item.lineSubtotal ?? 0) ||
          (Number(item.total ?? 0) + Number(item.descuento ?? 0));

        const precioEfectivo = qty > 0
          ? lineSubtotal / qty
          : Number(item.precio_unitario ?? 0);

        return {
          producto_id: item.producto_id,
          descripcion: item.descripcion,
          cantidad: qty,
          precio_unitario: Number(precioEfectivo || 0).toFixed(2),
          descuento: Number(item.descuento || 0),
          iva_porcentaje: item.iva_porcentaje ?? 15,
          percha_id: item.percha_id ?? null,
        };
      }),
      payments,
    };

    const routes = window.SALES_ROUTES || {};
    const editingQueueId = getQueuedSaleEditingId();
    const queueBase = routes.queueBase || "/api/ventas/queue";
    const url = editingQueueId
      ? `${queueBase}/${editingQueueId}/requeue`
      : (routes.queueStore || "/api/ventas/queue");
    const csrfToken =
      window.CSRF_TOKEN ||
      document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ||
      "";

    const res = await fetch(url, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-TOKEN": csrfToken,
      },
      body: JSON.stringify(payload),
    });

    if (res.status === 422) {
      const data = await res.json();
      const msg = (data?.message || "").toLowerCase();
      const hasCajaError =
        msg.includes("no hay caja abierta") ||
        msg.includes("caja") ||
        !!data?.errors?.caja_id;

      if (hasCajaError) {
        window.location.href = buildCashierOpenUrl();
        return;
      }

      const firstError = Object.values(data?.errors || {}).flat().find(Boolean);
      showSaleAlert(firstError || data?.message || "Error de validación en la factura en cola.", true);
      return;
    }

    if (!res.ok) {
      showSaleAlert("Ocurrió un error al poner la factura en cola.", true);
      return;
    }

    const data = await res.json();
    const ticketUrl = data?.data?.ticket_url;

    showSaleAlert(data.message || "Factura puesta en cola correctamente.");

    if (ticketUrl) {
      const frame = document.getElementById("ticketPrintFrame");
      if (frame) {
        frame.src = `${ticketUrl}${ticketUrl.includes("?") ? "&" : "?"}ts=${Date.now()}`;
      }
    }

    clearQueuedSaleEditingId();

    clearCart();
    const refInput = document.getElementById("payment_modal_referencia");
    if (refInput) refInput.value = "";
    const obsInput = document.getElementById("payment_modal_observaciones");
    if (obsInput) obsInput.value = "";

    closeSplitModal();
    resetSplitRows();
    await refreshQueuedSales();
  } catch (error) {
    console.error(error);
    showSaleAlert("Error de comunicación con el servidor.", true);
  } finally {
    submitting = false;
    updateSplitConfirmButton(lastSplitValidation.valid);
    if (simpleBtn) {
      simpleBtn.disabled = false;
      simpleBtn.classList.remove("opacity-50", "cursor-not-allowed");
    }
  }
}

function submitSimpleSale() {
  const totalUi = Number(getTotals().total || 0);
  const fechaVenta = document.getElementById("fecha_venta")?.value;

  try {
    const payments = buildSimplePayments(fechaVenta, totalUi);
    submitSaleWithPayments(payments);
  } catch (error) {
    showSaleAlert(error.message || "No se pudo registrar el pago.", true);
  }
}

function submitSplitSale() {
  const validation = refreshSplitSummary();
  if (!validation.valid) {
    showSaleAlert(validation.message, true);
    return;
  }

  const fechaVenta = document.getElementById("fecha_venta")?.value;
  submitSaleWithPayments(buildSplitPayments(fechaVenta));
}

export function initPayment() {
  const simpleMethodSelect = getSimpleMethodSelect();
  const simpleReceivedInput = document.getElementById("payment_modal_monto_recibido");
  const splitModal = getSplitModal();
  const splitContainer = getSplitRowsContainer();
  const splitMethodList = document.getElementById("split_payment_method_list");
  const splitOpenButton = document.getElementById("btn-open-split-payment");
  const splitAddButton = document.getElementById("btn-add-split-payment-row");
  const splitConfirmButton = document.getElementById("btn-confirm-split-payment");
  const simpleConfirmButton = document.getElementById("btn-confirm-payment");

  if (simpleMethodSelect) {
    simpleMethodSelect.addEventListener("change", recalcSimpleCambio);
  }

  if (simpleReceivedInput) {
    simpleReceivedInput.addEventListener("input", recalcSimpleCambio);
    simpleReceivedInput.addEventListener("focus", () => simpleReceivedInput.select());
    simpleReceivedInput.addEventListener("click", () => simpleReceivedInput.select());
  }

  if (splitOpenButton) {
    splitOpenButton.addEventListener("click", openSplitModal);
  }

  if (splitModal) {
    splitModal.querySelectorAll("[data-split-close]").forEach((button) => {
      button.addEventListener("click", closeSplitModal);
    });
  }

  if (splitAddButton) {
    splitAddButton.addEventListener("click", () => addSplitRow());
  }

  if (splitMethodList) {
    splitMethodList.addEventListener("click", (event) => {
      const button = event.target.closest("[data-split-method-button]");
      if (!button) return;

      const method = button.dataset.methodValue || "";
      const existingRow = findSplitRowByMethod(method);
      if (existingRow) {
        focusSplitRow(existingRow);
        return;
      }

      const row = addSplitRow(method);
      if (row) focusSplitRow(row);
    });
  }

  if (splitContainer) {
    splitContainer.addEventListener("click", (event) => {
      const removeButton = event.target.closest("[data-remove-split-payment-row]");
      if (!removeButton) return;

      const row = removeButton.closest("[data-split-payment-row]");
      if (!row || getSplitRows().length === 1) return;

      row.remove();
      refreshSplitSummary();
    });

    splitContainer.addEventListener("change", (event) => {
      if (event.target.closest("[data-split-payment-row]")) {
        refreshSplitSummary();
      }
    });

    splitContainer.addEventListener("input", (event) => {
      const row = event.target.closest("[data-split-payment-row]");
      if (!row) return;
      refreshSplitSummary();
    });
  }

  if (simpleConfirmButton) {
    simpleConfirmButton.addEventListener("click", submitSimpleSale);
  }

  if (splitConfirmButton) {
    splitConfirmButton.addEventListener("click", submitSplitSale);
  }

  window.addEventListener("pos:totals-updated", (event) => {
    const total = Number(event.detail?.total || 0);
    syncSimplePaymentToTotal(total);
    updateSplitModalHeader();
    syncSingleSplitToTotal(total);
    refreshSplitSummary();
  });

  recalcSimpleCambio();
  resetSplitRows();
}
