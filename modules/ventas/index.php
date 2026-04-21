<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($_SESSION['id_usuario']) || !tienePermiso('ventas_crear')) {
    echo "<div class='alert alert-danger'>No tienes permiso para acceder a esta seccion.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$clientes_sql = "SELECT id_cliente, nombre_cliente FROM clientes WHERE activo = 1 ORDER BY nombre_cliente ASC";
$clientes_resultado = $conexion->query($clientes_sql);
?>

<style>
    #resultados_busqueda {
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border: 1px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
        margin-top: 5px;
    }

    .list-group-item-action:hover {
        background-color: #f8f9fa;
        border-left: 4px solid #0d6efd;
        transition: all 0.2s ease;
    }

    @media (min-width: 768px) {
        .col-md-5 .card {
            position: sticky;
            top: 20px;
        }
    }

    .img-miniatura {
        width: 45px;
        height: 45px;
        object-fit: cover;
        border-radius: 6px;
        background-color: #f0f0f0;
        border: 1px solid #ddd;
    }

    #galeria-productos,
    #galeria-combos {
        max-height: 65vh;
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 5px;
        padding-bottom: 20px;
    }

    #galeria-productos::-webkit-scrollbar,
    #galeria-combos::-webkit-scrollbar {
        width: 6px;
    }

    #galeria-productos::-webkit-scrollbar-thumb,
    #galeria-combos::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 10px;
    }

    .resumen-fijo {
        position: sticky;
        top: 20px;
        z-index: 100;
    }

    .badge-presentacion {
        font-size: 0.75rem;
        background: #e8f0ff;
        color: #0d6efd;
    }

    .producto-card .card {
        border-radius: 12px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .producto-card .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
    }

    .combo-card .card {
        border-radius: 14px;
        background: #fffdf2;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .combo-card .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.75rem 1.25rem rgba(255, 193, 7, 0.18);
    }

    .combo-detalle {
        min-height: 38px;
    }

    .presentacion-option {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        background: #fff;
        transition: all 0.2s ease;
        text-align: left;
    }

    .presentacion-option:hover:not(:disabled) {
        border-color: #0d6efd;
        background: #f8fbff;
        transform: translateY(-1px);
    }

    .presentacion-option:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-7">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h4 class="card-title">Punto de Venta</h4>
                    <div class="mb-3">
                        <label for="buscar_producto" class="form-label">Buscar Producto (Nombre o Codigo)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" id="buscar_producto" class="form-control" placeholder="Escribe para buscar...">
                        </div>
                        <div id="resultados_busqueda" class="list-group position-absolute" style="z-index: 1000; width: 95%;"></div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Producto</th>
                                    <th>Precio</th>
                                    <th>Cantidad</th>
                                    <th>Subtotal</th>
                                    <th>Accion</th>
                                </tr>
                            </thead>
                            <tbody id="carrito_tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <ul class="nav nav-pills mt-3" id="ventas-tabs">
                <li class="nav-item">
                    <button type="button" class="nav-link active" data-vista="productos">Productos</button>
                </li>
                <li class="nav-item ms-2">
                    <button type="button" class="nav-link" data-vista="combos">Combos</button>
                </li>
            </ul>

            <div id="galeria-productos" class="row g-3 mt-3"></div>
            <div id="galeria-combos" class="row g-3 mt-3 d-none"></div>
        </div>

        <div class="col-md-5">
            <div class="card shadow-sm resumen-fijo">
                <div class="card-body">
                    <h4 class="card-title">Resumen de Venta</h4>

                    <div class="mb-3">
                        <label for="descuento_global" class="form-label">Descuento (S/)</label>
                        <input type="number" id="descuento_global" class="form-control" value="0.00" min="0" step="0.10">
                    </div>

                    <div class="d-flex justify-content-between fs-4">
                        <strong>TOTAL:</strong>
                        <strong id="total_venta">S/ 0.00</strong>
                    </div>
                    <hr>
                    <div class="d-grid">
                        <button id="btn_finalizar_venta" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#pagoModal" disabled>
                            <i class="fas fa-check"></i> Finalizar Venta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="presentacionesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Seleccionar presentacion</h5>
                    <small class="text-muted" id="presentaciones_modal_producto"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="presentaciones_modal_body" class="d-grid gap-2"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="pagoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Procesar Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h3 class="text-center">Total a Pagar: <span id="modal_total_pagar"></span></h3>
                <div class="mb-3 d-none">
                    <label for="metodo_pago" class="form-label">Metodo de Pago</label>
                    <select id="metodo_pago" class="form-select">
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta_credito">Tarjeta de Credito</option>
                        <option value="yape_plin">Yape / Plin</option>
                        <option value="transferencia">Transferencia</option>
                    </select>
                </div>
                <div id="pago_efectivo_div" class="d-none">
                    <div class="mb-3">
                        <label for="monto_recibido" class="form-label">Monto Recibido</label>
                        <input type="number" step="0.01" class="form-control" id="monto_recibido">
                    </div>
                    <h4>Cambio: <span id="cambio_cliente"><?php echo getMoneda(); ?>0.00</span></h4>
                </div>
                <div id="contenedor_pagos">
                    <div class="fila-pago row mb-2">
                        <div class="col-6">
                            <select class="form-select metodo-pago">
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta_credito">Tarjeta</option>
                                <option value="yape_plin">Yape / Plin</option>
                                <option value="transferencia">Transferencia</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <input type="number" step="0.01" class="form-control monto-pago" placeholder="Monto">
                        </div>
                        <div class="col-2">
                            <button type="button" class="btn btn-danger btn-sm btn-eliminar-pago">X</button>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm" id="btn_agregar_pago">
                    + Agregar otro metodo
                </button>

                <hr>
                <h5>Total Pagado: <span id="total_pagado_modal">S/ 0.00</span></h5>
                <h5 id="cambio_container" style="display:none;">
                    Cambio: <span id="cambio_modal">S/ 0.00</span>
                </h5>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btn_confirmar_venta" class="btn btn-primary">Confirmar Venta</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
    let carrito = [];
    let presentacionesModalInstance = null;
    let productoActualPresentaciones = null;
    let combosDisponibles = [];
    let comboDetectionEnabled = true;
    let vistaGaleriaActual = 'productos';
    const MONEDA = '<?php echo getMoneda(); ?>';
    const DESCUENTO_DECIMALES = 2;

    function obtenerDescuentoGlobal() {
        const descuentoInput = document.getElementById('descuento_global');
        const descuento = parseFloat(descuentoInput?.value) || 0;
        return Math.max(0, descuento);
    }

    function calcularSubtotalCarrito() {
        return carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
    }

    function calcularTotalesVenta() {
        const subtotal = calcularSubtotalCarrito();
        const descuentoSolicitado = obtenerDescuentoGlobal();
        const descuentoAplicado = Math.min(subtotal, descuentoSolicitado);
        const total = Math.max(0, subtotal - descuentoAplicado);

        return { subtotal, descuento: descuentoAplicado, total };
    }

    function actualizarModalPago() {
        const modalTotalPagar = document.getElementById('modal_total_pagar');
        const { total } = calcularTotalesVenta();

        if (modalTotalPagar) {
            modalTotalPagar.textContent = `${MONEDA}${total.toFixed(2)}`;
        }
    }

    function agregarFilaPago() {
        const filaOriginal = document.querySelector('.fila-pago');
        const nuevaFila = filaOriginal.cloneNode(true);
        nuevaFila.querySelector('.monto-pago').value = '';
        document.getElementById('contenedor_pagos').appendChild(nuevaFila);
    }

    function eliminarFilaPago(btn) {
        const filas = document.querySelectorAll('.fila-pago');
        if (filas.length > 1) {
            btn.closest('.fila-pago').remove();
            actualizarTotalPagado();
        }
    }

    function actualizarTotalPagado() {
        let totalPagado = 0;
        let hayEfectivo = false;
        const { total } = calcularTotalesVenta();

        document.querySelectorAll('.fila-pago').forEach(fila => {
            const metodo = fila.querySelector('.metodo-pago').value;
            const monto = parseFloat(fila.querySelector('.monto-pago').value || 0);

            totalPagado += monto;

            if (metodo === 'efectivo') {
                hayEfectivo = true;
            }
        });

        document.getElementById('total_pagado_modal').textContent =
            MONEDA + totalPagado.toFixed(2);

        const cambioContainer = document.getElementById('cambio_container');
        const cambioSpan = document.getElementById('cambio_modal');

        if (hayEfectivo && totalPagado > total) {
            const cambio = totalPagado - total;
            cambioSpan.textContent = MONEDA + cambio.toFixed(2);
            cambioContainer.style.display = 'block';
        } else {
            cambioContainer.style.display = 'none';
        }
    }

    function reiniciarFilasPago() {
        const contenedor = document.getElementById('contenedor_pagos');
        const filas = contenedor?.querySelectorAll('.fila-pago') || [];

        filas.forEach((fila, index) => {
            if (index === 0) {
                fila.querySelector('.metodo-pago').value = 'efectivo';
                fila.querySelector('.monto-pago').value = '';
            } else {
                fila.remove();
            }
        });

        actualizarTotalPagado();
    }

    function emitirBeep() {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();

        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(880, audioCtx.currentTime);
        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);
        gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
        oscillator.start();
        oscillator.stop(audioCtx.currentTime + 0.1);
    }

    async function fetchJson(url) {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        });

        const rawText = await response.text();
        let data;

        try {
            data = rawText ? JSON.parse(rawText) : [];
        } catch (error) {
            console.error('Respuesta no valida en JSON', {
                url,
                status: response.status,
                body: rawText
            });
            throw new Error('La respuesta del servidor no es JSON valido.');
        }

        if (!response.ok) {
            console.error('Error HTTP en fetchJson', { url, status: response.status, data });
            throw new Error(data?.error || `Error HTTP ${response.status}`);
        }

        if (!Array.isArray(data) && typeof data !== 'object') {
            console.error('Estructura inesperada recibida', { url, data });
            throw new Error('La estructura de datos recibida no es valida.');
        }

        return data;
    }

    function obtenerPresentacionesProducto(producto) {
        if (Array.isArray(producto.presentaciones) && producto.presentaciones.length > 0) {
            return producto.presentaciones;
        }

        return [{
            id_presentacion: null,
            tipo: 'Unidad',
            cantidad: 1,
            precio: parseFloat(producto.precio_venta)
        }];
    }

    function obtenerClaveCarrito(item) {
        return `${item.id}_${item.id_presentacion ?? 'unidad'}`;
    }

    function obtenerStockMaxPresentacion(stockTotal, unidadesPorPresentacion) {
        return Math.floor(stockTotal / unidadesPorPresentacion);
    }

    function obtenerCantidadRealItem(item) {
        if (item.tipo_item === 'combo') {
            return 0;
        }
        return item.cantidad * item.unidades_por_presentacion;
    }

    function obtenerCantidadRealProductoEnCarrito(idProducto, indiceExcluir = null) {
        return carrito.reduce((sum, item, index) => {
            if (Number(item.id) !== Number(idProducto) || index === indiceExcluir) {
                return sum;
            }

            return sum + obtenerCantidadRealItem(item);
        }, 0);
    }

    function crearItemCarrito(producto, presentacion) {
        const unidadesPorPresentacion = parseInt(presentacion.cantidad, 10) || 1;
        const stockTotal = parseInt(producto.stock, 10) || 0;

        return {
            id: Number(producto.id_producto),
            id_presentacion: presentacion.id_presentacion ?? null,
            nombre: producto.nombre,
            precio: parseFloat(presentacion.precio),
            cantidad: 1,
            tipo_presentacion: presentacion.tipo || 'Unidad',
            unidades_por_presentacion: unidadesPorPresentacion,
            stockTotal: stockTotal,
            stockMax: obtenerStockMaxPresentacion(stockTotal, unidadesPorPresentacion)
        };
    }

    function agregarItemPresentacion(producto, presentacion) {
        const nuevoItem = crearItemCarrito(producto, presentacion);
        const cantidadRealNueva = nuevoItem.unidades_por_presentacion;
        const cantidadRealActual = obtenerCantidadRealProductoEnCarrito(nuevoItem.id);

        if (nuevoItem.stockMax <= 0) {
            alert(`Sin stock suficiente para la presentacion ${nuevoItem.tipo_presentacion}.`);
            return;
        }

        if (cantidadRealActual + cantidadRealNueva > nuevoItem.stockTotal) {
            alert(`Limite alcanzado: Solo quedan ${nuevoItem.stockTotal} unidades de ${nuevoItem.nombre}.`);
            return;
        }

        const clave = obtenerClaveCarrito(nuevoItem);
        const existente = carrito.find(item => obtenerClaveCarrito(item) === clave);

        if (existente) {
            const cantidadRealProyectada = obtenerCantidadRealProductoEnCarrito(existente.id) + existente.unidades_por_presentacion;
            if (cantidadRealProyectada > existente.stockTotal) {
                alert(`Limite alcanzado: Solo quedan ${existente.stockTotal} unidades para ${existente.nombre} (${existente.tipo_presentacion}).`);
                return;
            }

            existente.cantidad++;
        } else {
            carrito.push(nuevoItem);
        }

        renderizarCarrito();
        recomputarCombosAutomaticos();
        emitirBeep();
    }

    function obtenerCantidadUnidadesElegibles(idProducto, indiceExcluir = null) {
        return carrito.reduce((sum, item, index) => {
            if (index === indiceExcluir || item.tipo_item === 'combo') {
                return sum;
            }

            if (Number(item.id) !== Number(idProducto)) {
                return sum;
            }

            if (item.unidades_por_presentacion !== 1) {
                return sum;
            }

            return sum + item.cantidad;
        }, 0);
    }

    function consumirUnidadesProducto(idProducto, cantidadNecesaria) {
        let restante = cantidadNecesaria;

        for (let i = 0; i < carrito.length && restante > 0; i++) {
            const item = carrito[i];
            if (item.tipo_item === 'combo' || Number(item.id) !== Number(idProducto) || item.unidades_por_presentacion !== 1) {
                continue;
            }

            const descontar = Math.min(item.cantidad, restante);
            item.cantidad -= descontar;
            restante -= descontar;
        }

        carrito = carrito.filter(item => item.tipo_item === 'combo' || item.cantidad > 0);
        return restante === 0;
    }

    function agregarComboAlCarrito(combo, cantidadCombos) {
        if (cantidadCombos <= 0) {
            return;
        }

        const existente = carrito.find(item => item.tipo_item === 'combo' && Number(item.id_combo) === Number(combo.id_combo));
        if (existente) {
            existente.cantidad += cantidadCombos;
            return;
        }

        carrito.push({
            id: `combo_${combo.id_combo}`,
            tipo_item: 'combo',
            tipo: 'combo',
            id_combo: Number(combo.id_combo),
            nombre: `${combo.nombre} Combo`,
            precio: parseFloat(combo.precio),
            cantidad: cantidadCombos,
            tipo_presentacion: 'PROMO',
            unidades_por_presentacion: 0
        });
    }

    function recomputarCombosAutomaticos() {
        if (!comboDetectionEnabled || !Array.isArray(combosDisponibles) || combosDisponibles.length === 0) {
            renderizarCarrito();
            return;
        }

        comboDetectionEnabled = false;

        combosDisponibles.forEach(combo => {
            let repeticiones = Infinity;
            combo.productos.forEach(productoCombo => {
                const disponibles = obtenerCantidadUnidadesElegibles(productoCombo.id_producto);
                repeticiones = Math.min(repeticiones, Math.floor(disponibles / productoCombo.cantidad));
            });

            if (!Number.isFinite(repeticiones) || repeticiones <= 0) {
                return;
            }

            for (let i = 0; i < repeticiones; i++) {
                let puedeConsumir = true;
                combo.productos.forEach(productoCombo => {
                    if (obtenerCantidadUnidadesElegibles(productoCombo.id_producto) < productoCombo.cantidad) {
                        puedeConsumir = false;
                    }
                });

                if (!puedeConsumir) {
                    break;
                }

                combo.productos.forEach(productoCombo => {
                    consumirUnidadesProducto(productoCombo.id_producto, productoCombo.cantidad);
                });

                agregarComboAlCarrito(combo, 1);
            }
        });

        comboDetectionEnabled = true;
        renderizarCarrito();
    }

    function mostrarModalPresentaciones(producto) {
        const presentaciones = obtenerPresentacionesProducto(producto);
        const modalProducto = document.getElementById('presentaciones_modal_producto');
        const modalBody = document.getElementById('presentaciones_modal_body');

        productoActualPresentaciones = producto;
        modalProducto.textContent = producto.nombre;
        modalBody.innerHTML = '';

        presentaciones.forEach(presentacion => {
            const stockTotal = parseInt(producto.stock, 10) || 0;
            const stockReservado = obtenerCantidadRealProductoEnCarrito(producto.id_producto);
            const stockDisponible = Math.max(0, stockTotal - stockReservado);
            const stockMax = obtenerStockMaxPresentacion(stockDisponible, parseInt(presentacion.cantidad, 10) || 1);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn presentacion-option p-3';
            button.disabled = stockMax <= 0;
            button.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold">${presentacion.tipo}</div>
                        <small class="text-muted">${presentacion.cantidad} unidad(es) por venta</small>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-success">${MONEDA}${parseFloat(presentacion.precio).toFixed(2)}</div>
                        <small class="text-muted">Disponibles: ${stockMax}</small>
                    </div>
                </div>
            `;

            button.addEventListener('click', () => {
                agregarItemPresentacion(productoActualPresentaciones, presentacion);
                presentacionesModalInstance?.hide();
            });

            modalBody.appendChild(button);
        });

        presentacionesModalInstance?.show();
    }

    function agregarAlCarrito(producto) {
        const stockDisponible = parseInt(producto.stock, 10) || 0;
        if (stockDisponible <= 0) {
            alert('Sin Stock: Este producto no tiene unidades disponibles.');
            return;
        }

        if (producto.tiene_presentaciones) {
            mostrarModalPresentaciones(producto);
            return;
        }

        const presentacionUnidad = obtenerPresentacionesProducto(producto)[0];
        agregarItemPresentacion(producto, presentacionUnidad);
    }

    function eliminarDelCarrito(index) {
        carrito.splice(index, 1);
        renderizarCarrito();
    }

    function renderizarCarrito() {
        const carritoTbody = document.getElementById('carrito_tbody');
        const totalVentaSpan = document.getElementById('total_venta');
        const btnFinalizarVenta = document.getElementById('btn_finalizar_venta');
        const descuentoInput = document.getElementById('descuento_global');

        carritoTbody.innerHTML = '';

        carrito.forEach((item, index) => {
            const subtotal = item.precio * item.cantidad;
            const cantidadReal = item.tipo_item === 'combo' ? item.cantidad : item.cantidad * item.unidades_por_presentacion;
            const nombreMostrado = item.tipo_item === 'combo'
                ? item.nombre
                : `${item.nombre} (${item.tipo_presentacion})`;
            const badgeClase = item.tipo_item === 'combo' ? 'bg-warning text-dark' : 'badge-presentacion';
            const detalleSecundario = item.tipo_item === 'combo'
                ? 'Combo automatico'
                : `${item.unidades_por_presentacion} und.`;
            const cantidadAyuda = item.tipo_item === 'combo'
                ? `${item.cantidad} combo(s)`
                : `${cantidadReal} und. reales`;
            const inputCantidad = item.tipo_item === 'combo'
                ? `<input type="number" value="${item.cantidad}" min="1" class="form-control form-control-sm cantidad-input" data-index="${index}">`
                : `<input type="number" value="${item.cantidad}" min="1" class="form-control form-control-sm cantidad-input" data-index="${index}">`;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="align-middle">
                    <strong>${nombreMostrado}</strong><br>
                    <span class="badge ${badgeClase}">${item.tipo_presentacion}</span>
                </td>
                <td class="align-middle">
                    ${MONEDA}${item.precio.toFixed(2)}<br>
                    <small class="text-muted">${detalleSecundario}</small>
                </td>
                <td class="align-middle" style="width: 120px;">
                    ${inputCantidad}
                    <small class="text-muted">${cantidadAyuda}</small>
                </td>
                <td class="align-middle fw-bold">${MONEDA}${subtotal.toFixed(2)}</td>
                <td class="align-middle text-center">
                    <button class="btn btn-outline-danger btn-sm" onclick="eliminarDelCarrito(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            carritoTbody.appendChild(tr);
        });

        const { subtotal, descuento, total } = calcularTotalesVenta();

        if (descuentoInput) {
            if (carrito.length === 0) {
                descuentoInput.value = '0.00';
            } else if (obtenerDescuentoGlobal() > subtotal) {
                descuentoInput.value = descuento.toFixed(DESCUENTO_DECIMALES);
            }
        }

        totalVentaSpan.textContent = `${MONEDA}${total.toFixed(2)}`;
        btnFinalizarVenta.disabled = carrito.length === 0;
        actualizarModalPago();
    }

    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('cantidad-input')) {
            const index = Number(e.target.getAttribute('data-index'));
            const nuevaCantidad = parseInt(e.target.value, 10);
            const item = carrito[index];

            if (!item) {
                return;
            }

            if (nuevaCantidad < 1 || Number.isNaN(nuevaCantidad)) {
                alert('La cantidad minima es 1');
                e.target.value = 1;
                item.cantidad = 1;
                renderizarCarrito();
                return;
            }

            if (item.tipo_item === 'combo') {
                item.cantidad = nuevaCantidad;
                renderizarCarrito();
                return;
            }

            const stockOtrasPresentaciones = obtenerCantidadRealProductoEnCarrito(item.id, index);
            const maximoPermitido = Math.floor((item.stockTotal - stockOtrasPresentaciones) / item.unidades_por_presentacion);

            if (nuevaCantidad > maximoPermitido) {
                alert(`Limite alcanzado: Solo quedan ${item.stockTotal - stockOtrasPresentaciones} unidades de ${item.nombre} disponibles para ${item.tipo_presentacion}.`);
                e.target.value = item.cantidad;
                renderizarCarrito();
                return;
            }

            item.cantidad = nuevaCantidad;
            emitirBeep();
            recomputarCombosAutomaticos();
        }
    });

    function renderizarTarjetaProducto(p) {
        const imgUrl = p.imagen
            ? `../../assets/img/productos/${p.imagen}`
            : '../../assets/img/productos/default_producto.png';

        const precioBase = parseFloat(p.precio_venta).toFixed(2);
        const badgePresentaciones = p.tiene_presentaciones
            ? `<span class="badge bg-light text-primary border mb-2">${p.presentaciones.length} presentaciones</span>`
            : `<span class="badge bg-light text-secondary border mb-2">Unidad</span>`;

        const col = document.createElement('div');
        col.className = 'col-md-3 col-6 mb-3 producto-card';
        col.innerHTML = `
            <div class="card h-100 shadow-sm">
                <img src="${imgUrl}" style="height: 80px; object-fit: contain; padding: 5px;">
                <div class="card-body p-2 text-center">
                    ${badgePresentaciones}
                    <small class="fw-bold d-block text-truncate">${p.nombre}</small>
                    <span class="d-block mb-2 text-success fw-bold">${MONEDA}${precioBase}</span>
                    <button class="btn btn-primary btn-sm w-100 btn-agregar">
                        <i class="fas fa-plus"></i> Agregar
                    </button>
                </div>
            </div>
        `;

        col.querySelector('.btn-agregar').addEventListener('click', () => agregarAlCarrito(p));

        return col;
    }

    function obtenerResumenProductosCombo(combo) {
        if (!Array.isArray(combo.productos) || combo.productos.length === 0) {
            return 'Sin productos configurados.';
        }

        return combo.productos
            .map(producto => `${producto.cantidad}x ${producto.nombre}`)
            .join(', ');
    }

    function renderizarTarjetaCombo(combo) {
        const col = document.createElement('div');
        col.className = 'col-md-4 col-sm-6 mb-3 combo-card';
        col.innerHTML = `
            <div class="card h-100 border border-warning border-2 shadow-sm">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                        <span class="badge bg-warning text-dark">COMBO</span>
                        <span class="fw-bold text-success">${MONEDA}${parseFloat(combo.precio || 0).toFixed(2)}</span>
                    </div>
                    <h6 class="fw-bold mb-2">${combo.nombre}</h6>
                    <small class="text-muted combo-detalle d-block mb-3">${obtenerResumenProductosCombo(combo)}</small>
                    <button class="btn btn-warning text-dark btn-sm w-100 mt-auto btn-agregar-combo">
                        <i class="fas fa-bolt"></i> Agregar Combo
                    </button>
                </div>
            </div>
        `;

        col.querySelector('.btn-agregar-combo').addEventListener('click', () => {
            agregarComboAlCarritoDirecto(combo.id_combo);
        });

        return col;
    }

    function renderizarGaleriaCombos() {
        const galeriaCombos = document.getElementById('galeria-combos');
        if (!galeriaCombos) {
            return;
        }

        galeriaCombos.innerHTML = '';

        if (!Array.isArray(combosDisponibles) || combosDisponibles.length === 0) {
            galeriaCombos.innerHTML = '<div class="col-12"><div class="alert alert-warning mb-0">No hay combos disponibles en este momento.</div></div>';
            return;
        }

        combosDisponibles.forEach(combo => {
            galeriaCombos.appendChild(renderizarTarjetaCombo(combo));
        });
    }

    function filtrarVista(tipo) {
        const galeriaProductos = document.getElementById('galeria-productos');
        const galeriaCombos = document.getElementById('galeria-combos');
        const tabs = document.querySelectorAll('#ventas-tabs [data-vista]');

        vistaGaleriaActual = tipo === 'combos' ? 'combos' : 'productos';

        galeriaProductos?.classList.toggle('d-none', vistaGaleriaActual !== 'productos');
        galeriaCombos?.classList.toggle('d-none', vistaGaleriaActual !== 'combos');

        tabs.forEach(tab => {
            tab.classList.toggle('active', tab.dataset.vista === vistaGaleriaActual);
        });

        if (vistaGaleriaActual === 'combos') {
            renderizarGaleriaCombos();
        }
    }

    async function agregarComboAlCarritoDirecto(idCombo) {
        const combo = combosDisponibles.find(item => Number(item.id_combo) === Number(idCombo));

        if (!combo) {
            alert('No se encontro el combo seleccionado.');
            return;
        }

        try {
            for (const itemCombo of combo.productos || []) {
                for (let i = 0; i < (parseInt(itemCombo.cantidad, 10) || 0); i++) {
                    const data = await fetchJson(`buscar_productos.php?term=${encodeURIComponent(itemCombo.id_producto)}&exact=1`);

                    if (!Array.isArray(data) || data.length === 0) {
                        throw new Error(`No se encontro el producto ${itemCombo.nombre}.`);
                    }

                    agregarAlCarrito(data[0]);
                }
            }
        } catch (error) {
            console.error('Error agregando combo al carrito', error);
            alert(error.message || 'No se pudo agregar el combo al carrito.');
        }
    }

    function cargarGaleria(filtro = '') {
        const galeria = document.getElementById('galeria-productos');

        fetchJson(`get_productos_galeria.php?buscar=${encodeURIComponent(filtro)}`)
            .then(productos => {
                galeria.innerHTML = '';
                productos.forEach(producto => {
                    galeria.appendChild(renderizarTarjetaProducto(producto));
                });
            })
            .catch((error) => {
                console.error('Error cargando galeria', error);
                galeria.innerHTML = '<div class="col-12"><div class="alert alert-danger">No se pudieron cargar los productos.</div></div>';
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const buscarInput = document.getElementById('buscar_producto');
        const btnAgregarPago = document.getElementById('btn_agregar_pago');
        const btnConfirmarVenta = document.getElementById('btn_confirmar_venta');
        const idClienteSelect = document.getElementById('id_cliente');
        const descuentoInput = document.getElementById('descuento_global');
        const btnFinalizarVenta = document.getElementById('btn_finalizar_venta');
        const pagoModal = document.getElementById('pagoModal');
        const resultadosBusqueda = document.getElementById('resultados_busqueda');
        const presentacionesModalElement = document.getElementById('presentacionesModal');
        const tabsVista = document.querySelectorAll('#ventas-tabs [data-vista]');

        presentacionesModalInstance = presentacionesModalElement ? new bootstrap.Modal(presentacionesModalElement) : null;

        document.getElementById('metodo_pago')?.closest('.mb-3')?.remove();
        document.getElementById('pago_efectivo_div')?.remove();

        fetchJson('obtener_combos.php')
            .then(data => {
                combosDisponibles = Array.isArray(data) ? data : [];
                if (vistaGaleriaActual === 'combos') {
                    renderizarGaleriaCombos();
                }
            })
            .catch(error => {
                console.error('No se pudieron cargar los combos', error);
                combosDisponibles = [];
                if (vistaGaleriaActual === 'combos') {
                    renderizarGaleriaCombos();
                }
            });

        cargarGaleria();
        filtrarVista('productos');

        tabsVista.forEach(tab => {
            tab.addEventListener('click', () => {
                filtrarVista(tab.dataset.vista);
            });
        });

        buscarInput.addEventListener('input', e => {
            cargarGaleria(e.target.value);
        });

        buscarInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();

                const term = buscarInput.value.trim();
                if (!term) return;

                fetchJson(`buscar_productos.php?term=${encodeURIComponent(term)}&exact=1`)
                    .then(data => {
                        if (data.length > 0) {
                            agregarAlCarrito(data[0]);
                            buscarInput.value = '';
                            resultadosBusqueda.innerHTML = '';
                        }
                    })
                    .catch(error => {
                        console.error('Error en busqueda exacta', error);
                    });
            }
        });

        btnAgregarPago?.addEventListener('click', agregarFilaPago);

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-eliminar-pago')) {
                eliminarFilaPago(e.target);
            }
        });

        document.addEventListener('input', function(e){
            if(e.target.classList.contains('monto-pago')){
                actualizarTotalPagado();
            }
        });

        descuentoInput?.addEventListener('input', () => {
            renderizarCarrito();
        });

        descuentoInput?.addEventListener('blur', () => {
            const descuento = Math.max(0, parseFloat(descuentoInput.value) || 0);
            descuentoInput.value = descuento.toFixed(DESCUENTO_DECIMALES);
            renderizarCarrito();
        });

        btnFinalizarVenta?.addEventListener('click', () => {
            actualizarModalPago();
            reiniciarFilasPago();
        });

        pagoModal?.addEventListener('show.bs.modal', () => {
            actualizarModalPago();
            reiniciarFilasPago();
        });

        btnConfirmarVenta.addEventListener('click', function () {
            if (carrito.length === 0) return;

            this.disabled = true;
            const { descuento, total } = calcularTotalesVenta();
            const pagos = [];
            let sumaPagos = 0;

            document.querySelectorAll('.fila-pago').forEach(fila => {
                const metodo = fila.querySelector('.metodo-pago').value;
                const monto = parseFloat(fila.querySelector('.monto-pago').value || 0);

                if (monto > 0) {
                    pagos.push({ metodo, monto });
                    sumaPagos += monto;
                }
            });

            if (sumaPagos + 0.01 < total) {
                alert('La suma de los pagos debe cubrir el total de la venta');
                this.disabled = false;
                return;
            }

            const ventaData = {
                id_cliente: idClienteSelect?.value || null,
                pagos: pagos,
                descuento_global: descuento.toFixed(2),
                total_final: total.toFixed(2),
                carrito: carrito.map(item => {
                    if (item.tipo_item === 'combo' || item.tipo === 'combo') {
                        return {
                            id: item.id || `combo_${item.id_combo}`,
                            id_combo: item.id_combo,
                            tipo_item: 'combo',
                            tipo: 'combo',
                            cantidad: item.cantidad
                        };
                    }

                    return {
                        id: item.id,
                        id_producto: item.id,
                        id_presentacion: item.id_presentacion,
                        tipo_item: 'producto',
                        tipo: 'producto',
                        tipo_presentacion: item.tipo_presentacion,
                        cantidad_presentaciones: item.cantidad,
                        cantidad: item.cantidad,
                        cantidad_real: item.cantidad * item.unidades_por_presentacion
                    };
                })
            };

            fetch('procesar_venta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(ventaData)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = `ticket.php?id_venta=${data.id_venta}`;
                    } else {
                        alert('Error: ' + (data.error || 'Desconocido'));
                        this.disabled = false;
                    }
                })
                .catch(() => {
                    alert('Error de conexion');
                    this.disabled = false;
                });
        });

        buscarInput.addEventListener('input', function () {
            const term = this.value.trim();

            if (term.length < 2) {
                resultadosBusqueda.innerHTML = '';
                return;
            }

            fetchJson(`buscar_productos.php?term=${encodeURIComponent(term)}`)
                .then(data => {
                    resultadosBusqueda.innerHTML = '';

                    data.forEach(p => {
                        const item = document.createElement('a');
                        item.href = '#';
                        item.className = 'list-group-item list-group-item-action';

                        item.innerHTML = `
                            <div class="d-flex align-items-center">
                                <img src="../../assets/img/productos/${p.imagen || 'default_producto.png'}" class="img-miniatura me-2">
                                <div>
                                    <strong>${p.nombre}</strong><br>
                                    <small>${MONEDA}${parseFloat(p.precio_venta).toFixed(2)}</small>
                                    ${p.tiene_presentaciones ? '<span class="badge bg-primary ms-2">Presentaciones</span>' : ''}
                                </div>
                            </div>
                        `;

                        item.addEventListener('click', (e) => {
                            e.preventDefault();
                            agregarAlCarrito(p);
                            resultadosBusqueda.innerHTML = '';
                            buscarInput.value = '';
                        });

                        resultadosBusqueda.appendChild(item);
                    });
                })
                .catch(error => {
                    console.error('Error cargando sugerencias', error);
                    resultadosBusqueda.innerHTML = '';
                });
        });
    });
</script>
