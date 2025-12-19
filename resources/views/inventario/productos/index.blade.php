<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">

            <h2 class="font-semibold text-2xl text-blue-900 leading-tight">
                {{ __('Productos') }}
            </h2>

            <button 
                onclick="window.location.href='{{ route('inventario.index') }}'"
                class="text-blue-700 hover:text-blue-900 transition flex items-center space-x-1"
                title="Regresar"
            >   
                <x-heroicon-s-arrow-left class="w-5 h-5" />
                <span>Atrás</span>
            </button>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">

            <!-- BOTÓN CREAR -->
            <div class="flex justify-end mb-4">
                <button
                    id="btn-open-create"
                    type="button"
                    class="bg-blue-700 hover:bg-blue-800 text-white px-4 py-2 rounded-md shadow"
                    >
                    + Nuevo Producto
                </button>
            </div>
            <!-- FILTROS -->
            <div class="flex flex-col md:flex-row md:items-center md:space-x-4 mb-4">

                <!-- BUSCADOR -->
                <input 
                    id="buscar-input"
                    type="text"
                    placeholder="Buscar por nombre o código..."
                    class="border px-3 py-2 rounded w-full md:w-1/3"
                    oninput="aplicarFiltros()"
                >

                <!-- SELECT CATEGORÍAS -->
                <select 
                    id="categoria-select"
                    class="border px-3 py-2 rounded w-full md:w-1/4 mt-3 md:mt-0"
                    onchange="aplicarFiltros()"
                >
                    <option value="">Todas las categorías</option>
                </select>
            </div>
            <!-- TABLA -->
            <div class="bg-white shadow rounded-lg overflow-hidden border border-gray-200">
                <table class="min-w-full">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-blue-900">Nombre</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-blue-900">Código interno</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-blue-900">Categoría</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-blue-900">Stock mínimo</th>
                            <th class="px-4 py-2 text-center text-sm font-semibold text-blue-900">Acciones</th>
                        </tr>
                    </thead>

                    <tbody id="tabla-productos"></tbody>
                </table>
            </div>
            <!-- Paginación -->
            <div id="paginacion" class="flex justify-center mt-4 space-x-2"></div>

        </div>
    </div>

    <!-- MODALES -->
    @include('inventario.productos.modals.assign')
    @include('inventario.productos.modals.create')
    @include('inventario.productos.modals.edit')

    <!-- JS CRUD -->
   <script>
(() => {
  // ==============================
  // Config / Helpers
  // ==============================
  const CSRF_TOKEN =
    document.querySelector('meta[name="csrf-token"]')?.content || "{{ csrf_token() }}";

  const $ = (id) => document.getElementById(id);

  const toInt = (v) => (v !== "" && v !== null && v !== undefined ? parseInt(v, 10) : null);
  const toFloat = (v) => (v !== "" && v !== null && v !== undefined ? parseFloat(v) : null);
  const orNull = (v) => (v === "" ? null : v);

  function swalSuccess(title = "Listo", text = "") {
    return Swal.fire({ icon: "success", title, text, timer: 1500, showConfirmButton: false });
  }
  function swalError(title = "Error", text = "Intenta nuevamente") {
    return Swal.fire({ icon: "error", title, text });
  }
  async function swalConfirm(text = "¿Estás seguro?") {
    const { isConfirmed } = await Swal.fire({
      icon: "warning",
      title: "Confirmar",
      text,
      showCancelButton: true,
      confirmButtonText: "Sí",
      cancelButtonText: "Cancelar",
    });
    return isConfirmed;
  }
  async function swalLoading(promise, text = "Procesando…") {
    Swal.fire({ title: text, allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    try {
      const result = await promise;
      Swal.close();
      return result;
    } catch (e) {
      Swal.close();
      throw e;
    }
  }

  async function readJsonSafe(res) {
    const t = await res.text();
    try { return t ? JSON.parse(t) : null; } catch { return t || null; }
  }

  // ==============================
  // Variables Globales
  // ==============================
  let PRODUCTOS = [];
  let PRODUCTOS_FILTRADOS = [];
  let PAGINA_ACTUAL = 1;
  const ITEMS_POR_PAGINA = 10;

  // ==============================
  // Modales (Create)
  // ==============================
  function openCreateModal() {
    const el = $("modal-create");
    if (!el) return console.error("No existe #modal-create");
    el.classList.remove("hidden");
  }

  function closeCreateModal() {
    const el = $("modal-create");
    if (!el) return;
    el.classList.add("hidden");
  }

  // ==============================
  // Init
  // ==============================
  document.addEventListener("DOMContentLoaded", () => {
    cargarProductos();

    // FIX: bindear el click por JS (evita problemas con onclick / submits)
    const btn = $("btn-open-create");
    if (btn) {
      btn.addEventListener("click", (ev) => {
        ev.preventDefault();
        ev.stopPropagation();
        openCreateModal();
      });
    }

    $("form-create-product")?.addEventListener("submit", handleCreateProduct);
    $("form-edit")?.addEventListener("submit", handleEditProduct);

    // Por si tu modal assign tiene form con id="form-assign"
    $("form-assign")?.addEventListener("submit", submitAssign);
  });

  // ==============================
  // Cargar Productos
  // ==============================
  async function cargarProductos() {
    try {
      const res = await fetch("/productos/list");
      if (!res.ok) throw new Error("No se pudo cargar /productos/list");
      const data = await res.json();

      PRODUCTOS = Array.isArray(data) ? data : [];
      PRODUCTOS_FILTRADOS = PRODUCTOS;

      cargarCategoriasUnicas(PRODUCTOS);
      renderPagina();
    } catch (e) {
      console.error(e);
      swalError("Error", "No se pudieron cargar los productos");
    }
  }

  // ==============================
  // Render Tabla
  // ==============================
  function renderTabla(lista) {
    let rows = "";

    lista.forEach((p) => {
      rows += `
        <tr class="border-b">
          <td class="px-4 py-2">${p.nombre ?? "-"}</td>
          <td class="px-4 py-2">${p.codigo_interno ?? "-"}</td>
          <td class="px-4 py-2">${p.categoria ?? "-"}</td>
          <td class="px-4 py-2">${p.stock_minimo ?? 0}</td>
          <td class="px-4 py-2 text-center">
            <button onclick="openAssignModal(${p.id})" class="text-green-600 hover:underline mr-3">
              Asignar Stock
            </button>
            <button onclick="openEditModal(${p.id})" class="text-blue-600 hover:underline mr-3">
              Editar
            </button>
            <button onclick="eliminarProducto(${p.id})" class="text-red-600 hover:underline">
              Eliminar
            </button>
          </td>
        </tr>`;
    });

    $("tabla-productos").innerHTML = rows;
  }

  // ==============================
  // Paginación
  // ==============================
  function renderPagina() {
    const inicio = (PAGINA_ACTUAL - 1) * ITEMS_POR_PAGINA;
    const fin = inicio + ITEMS_POR_PAGINA;

    const paginaItems = PRODUCTOS_FILTRADOS.slice(inicio, fin);
    renderTabla(paginaItems);
    renderControlesPaginacion();
  }

  function renderControlesPaginacion() {
    const totalPaginas = Math.ceil(PRODUCTOS_FILTRADOS.length / ITEMS_POR_PAGINA);
    const cont = $("paginacion");

    if (!cont) return;
    if (totalPaginas <= 1) {
      cont.innerHTML = "";
      return;
    }

    let html = "";

    html += `
      <button 
        class="px-3 py-1 border rounded ${PAGINA_ACTUAL === 1 ? "opacity-50 cursor-not-allowed" : ""}"
        onclick="cambiarPagina(${PAGINA_ACTUAL - 1})"
        ${PAGINA_ACTUAL === 1 ? "disabled" : ""}
      >Anterior</button>
    `;

    for (let i = 1; i <= totalPaginas; i++) {
      html += `
        <button
          class="px-3 py-1 border rounded ${i === PAGINA_ACTUAL ? "bg-blue-600 text-white" : ""}"
          onclick="cambiarPagina(${i})"
        >${i}</button>
      `;
    }

    html += `
      <button 
        class="px-3 py-1 border rounded ${PAGINA_ACTUAL === totalPaginas ? "opacity-50 cursor-not-allowed" : ""}"
        onclick="cambiarPagina(${PAGINA_ACTUAL + 1})"
        ${PAGINA_ACTUAL === totalPaginas ? "disabled" : ""}
      >Siguiente</button>
    `;

    cont.innerHTML = html;
  }

  function cambiarPagina(num) {
    const totalPaginas = Math.max(1, Math.ceil(PRODUCTOS_FILTRADOS.length / ITEMS_POR_PAGINA));
    PAGINA_ACTUAL = Math.min(Math.max(1, num), totalPaginas);
    renderPagina();
  }

  // ==============================
  // Filtros
  // ==============================
  function cargarCategoriasUnicas(data) {
    const select = $("categoria-select");
    if (!select) return;

    select.innerHTML = `<option value="">Todas las categorías</option>`;

    const categorias = [...new Set(data.map((p) => p.categoria).filter((c) => c))];
    categorias.forEach((cat) => {
      const opt = document.createElement("option");
      opt.value = cat;
      opt.textContent = cat;
      select.appendChild(opt);
    });
  }

  function aplicarFiltros() {
    const texto = ($("buscar-input")?.value || "").trim().toLowerCase();
    const categoria = $("categoria-select")?.value || "";

    const norm = (v) => (v ?? "").toString().toLowerCase();

    let filtrados = PRODUCTOS;

    if (texto !== "") {
      filtrados = filtrados.filter(
        (p) =>
          norm(p.nombre).includes(texto) ||
          norm(p.codigo_interno).includes(texto) ||
          norm(p.codigo_barras).includes(texto)
      );
    }

    if (categoria !== "") {
      filtrados = filtrados.filter((p) => p.categoria === categoria);
    }

    PRODUCTOS_FILTRADOS = filtrados;
    PAGINA_ACTUAL = 1;
    renderPagina();
  }

  // ==============================
  // Crear Producto
  // ==============================
  async function handleCreateProduct(e) {
    e.preventDefault();
    const fd = new FormData(e.target);

    const productPayload = {
      nombre: fd.get("nombre"),
      descripcion: orNull(fd.get("descripcion")),
      codigo_barras: orNull(fd.get("codigo_barras")),
      codigo_interno: orNull(fd.get("codigo_interno")),
      categoria: orNull(fd.get("categoria")),
      unidad_medida: fd.get("unidad_medida"),
      stock_minimo: toInt(fd.get("stock_minimo")),
      iva_porcentaje: toFloat(fd.get("iva_porcentaje")) ?? 15,
      estado: true,
    };

    try {
      const resProd = await swalLoading(
        fetch("/productos/store", {
          method: "POST",
          headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": CSRF_TOKEN },
          body: JSON.stringify(productPayload),
        }),
        "Creando producto…"
      );

      if (!resProd.ok) {
        const err = await readJsonSafe(resProd);
        throw new Error(err?.message || "Error al crear producto");
      }

      const producto = await resProd.json();

      const pricePayload = {
        producto_id: producto.id,
        precio_unitario: toFloat(fd.get("precio_unitario")),
        moneda: fd.get("moneda"),
        precio_por_cantidad: toFloat(fd.get("precio_por_cantidad")),
        cantidad_min: toInt(fd.get("cantidad_min")),
        cantidad_max: toInt(fd.get("cantidad_max")),
        precio_por_caja: toFloat(fd.get("precio_por_caja")),
        unidades_por_caja: toInt(fd.get("unidades_por_caja")),
      };

      const resPrice = await swalLoading(
        fetch("/producto-precios", {
          method: "POST",
          headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": CSRF_TOKEN },
          body: JSON.stringify(pricePayload),
        }),
        "Guardando precios…"
      );

      if (!resPrice.ok) {
        const err = await readJsonSafe(resPrice);
        throw new Error(err?.message || "Error al crear precios");
      }

      closeCreateModal();
      e.target.reset();

      await swalSuccess("Guardado", "Producto creado correctamente");
      cargarProductos();
    } catch (err) {
      console.error(err);
      swalError("Error al guardar", err.message || "Intenta nuevamente");
    }
  }

  // ==============================
  // Editar Producto
  // ==============================
  async function openEditModal(id) {
    try {
      const [resProd, resPrice] = await Promise.all([
        fetch(`/productos/show/${id}`),
        fetch(`/producto-precios/producto/${id}`),
      ]);

      if (!resProd.ok) throw new Error("No se pudo cargar el producto");

      const p = await resProd.json();
      let price = null;
      if (resPrice.ok) {
        try { price = await resPrice.json(); } catch { price = null; }
      }

      $("edit-id").value = p.id;
      $("edit-nombre").value = p.nombre ?? "";
      $("edit-descripcion").value = p.descripcion ?? "";
      $("edit-codigo-barras").value = p.codigo_barras ?? "";
      $("edit-codigo-interno").value = p.codigo_interno ?? "";
      $("edit-categoria").value = p.categoria ?? "";
      $("edit-unidad-medida").value = p.unidad_medida ?? "";
      $("edit-stock-minimo").value = p.stock_minimo ?? 0;
      $("edit-iva-porcentaje").value = p.iva_porcentaje ?? 15;


      $("edit-price-id").value = price?.id ?? "";
      $("edit-precio-unitario").value = price?.precio_unitario ?? "";
      $("edit-moneda").value = price?.moneda ?? "USD";
      $("edit-cantidad-min").value = price?.cantidad_min ?? "";
      $("edit-cantidad-max").value = price?.cantidad_max ?? "";
      $("edit-precio-por-cantidad").value = price?.precio_por_cantidad ?? "";
      $("edit-unidades-por-caja").value = price?.unidades_por_caja ?? "";
      $("edit-precio-por-caja").value = price?.precio_por_caja ?? "";

      if (typeof window._editToggleApply === "function") window._editToggleApply();
      $("modal-edit").classList.remove("hidden");
    } catch (err) {
      console.error(err);
      swalError("Error", "No se pudo cargar el producto");
    }
  }

  function closeEditModal() {
    $("modal-edit")?.classList.add("hidden");
  }

  async function handleEditProduct(e) {
    e.preventDefault();

    const fd = new FormData(e.target);
    const id = fd.get("id");
    const priceId = fd.get("price_id");

    const productPayload = {
      nombre: fd.get("nombre"),
      descripcion: orNull(fd.get("descripcion")),
      codigo_barras: orNull(fd.get("codigo_barras")),
      codigo_interno: orNull(fd.get("codigo_interno")),
      categoria: orNull(fd.get("categoria")),
      unidad_medida: fd.get("unidad_medida"),
      stock_minimo: toInt(fd.get("stock_minimo")),
      iva_porcentaje: toFloat(fd.get("iva_porcentaje")) ?? 15,
    };

    try {
      const resProd = await swalLoading(
        fetch(`/productos/update/${id}`, {
          method: "PUT",
          headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": CSRF_TOKEN },
          body: JSON.stringify(productPayload),
        }),
        "Actualizando producto…"
      );

      if (!resProd.ok) {
        const err = await readJsonSafe(resProd);
        throw new Error(err?.message || "Error al actualizar producto");
      }

      const pricePayload = {
        producto_id: id,
        precio_unitario: toFloat(fd.get("precio_unitario")),
        moneda: fd.get("moneda"),
        precio_por_cantidad: toFloat(fd.get("precio_por_cantidad")),
        cantidad_min: toInt(fd.get("cantidad_min")),
        cantidad_max: toInt(fd.get("cantidad_max")),
        precio_por_caja: toFloat(fd.get("precio_por_caja")),
        unidades_por_caja: toInt(fd.get("unidades_por_caja")),
      };

      let url = "/producto-precios";
      let method = "POST";
      if (priceId) {
        url = `/producto-precios/${priceId}`;
        method = "PUT";
      }

      const resPrice = await swalLoading(
        fetch(url, {
          method,
          headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": CSRF_TOKEN },
          body: JSON.stringify(pricePayload),
        }),
        "Guardando precios…"
      );

      if (!resPrice.ok) {
        const err = await readJsonSafe(resPrice);
        throw new Error(err?.message || "Error al guardar precios");
      }

      closeEditModal();
      await swalSuccess("Actualizado", "Producto actualizado");
      cargarProductos();
    } catch (err) {
      console.error(err);
      swalError("Error al actualizar", err.message || "Intenta nuevamente");
    }
  }

  // ==============================
  // Eliminar producto
  // ==============================
  async function eliminarProducto(id) {
    const ok = await swalConfirm("¿Eliminar este producto?");
    if (!ok) return;

    try {
      const res = await swalLoading(
        fetch(`/productos/delete/${id}`, {
          method: "DELETE",
          headers: { "X-CSRF-TOKEN": CSRF_TOKEN },
        }),
        "Eliminando…"
      );

      if (!res.ok) {
        const err = await readJsonSafe(res);
        throw new Error(err?.message || "No se pudo eliminar");
      }

      const data = await readJsonSafe(res);
      await swalSuccess("Eliminado", data?.message || "Producto eliminado");
      cargarProductos();
    } catch (err) {
      console.error(err);
      swalError("Error al eliminar", err.message || "Intenta nuevamente");
    }
  }

  // ==================================
  // MODAL ASIGNAR STOCK
  // ==================================
  async function openAssignModal(productoId) {
    $("assign-producto-id").value = productoId;

    try {
      const bodegasRes = await fetch("/inventario/bodegas");
      const bodegas = bodegasRes.ok ? await bodegasRes.json() : [];
      const bodegaSelect = $("assign-bodega");
      bodegaSelect.innerHTML = `<option value="">Seleccione...</option>`;
      bodegas.forEach((b) => (bodegaSelect.innerHTML += `<option value="${b.id}">${b.nombre}</option>`));

      const perchasRes = await fetch("/inventario/perchas");
      const perchas = perchasRes.ok ? await perchasRes.json() : [];
      const perchaSelect = $("assign-percha");
      perchaSelect.innerHTML = `<option value="">Seleccione...</option>`;
      perchas.forEach((p) => (perchaSelect.innerHTML += `<option value="${p.id}">${p.codigo}</option>`));

      $("modal-assign").classList.remove("hidden");
    } catch (e) {
      console.error(e);
      swalError("Error", "No se pudo abrir el modal de asignación");
    }
  }

  function closeAssignModal() {
    $("modal-assign")?.classList.add("hidden");
  }

  async function submitAssign(e) {
    e.preventDefault();

    const payload = {
      producto_id: $("assign-producto-id").value,
      bodega_id: $("assign-bodega").value,
      percha_id: $("assign-percha").value,
      stock_actual: parseInt($("assign-stock").value, 10),
      stock_reservado: 0,
    };

    try {
      const res = await swalLoading(
        fetch("/inventario/store", {
          method: "POST",
          headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": CSRF_TOKEN },
          body: JSON.stringify(payload),
        }),
        "Asignando producto…"
      );

      if (!res.ok) {
        const err = await readJsonSafe(res);
        throw new Error(err?.message || "No se pudo asignar stock");
      }

      closeAssignModal();
      await swalSuccess("Asignado", "El producto fue asignado correctamente");
    } catch (err) {
      console.error(err);
      swalError("Error", err.message || "No se pudo asignar stock");
    }
  }

  // ==============================
  // Exponer funciones a window (para onclick inline)
  // ==============================
  window.openCreateModal = openCreateModal;
  window.closeCreateModal = closeCreateModal;

  window.aplicarFiltros = aplicarFiltros;
  window.cambiarPagina = cambiarPagina;

  window.openEditModal = openEditModal;
  window.closeEditModal = closeEditModal;

  window.openAssignModal = openAssignModal;
  window.closeAssignModal = closeAssignModal;
  window.submitAssign = submitAssign;

  window.eliminarProducto = eliminarProducto;
})();
</script>


</x-app-layout>
