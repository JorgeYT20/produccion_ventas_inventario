<?php
require_once __DIR__ . '/../../config/database.php';
if (!isset($_SESSION['usuario']) && function_exists('usuarioActual')) {
    $usuarioSesion = usuarioActual();
    if ($usuarioSesion) {
        $_SESSION['usuario'] = $usuarioSesion;
    }
}

if (!isset($_SESSION['usuario'])) {
    header('Location: /mi_sistema/modules/auth/login.php');
    exit;
}

if (!in_array((int)($_SESSION['usuario']['id_rol'] ?? 0), [1, 2], true)) {
    http_response_code(403);
    die('Acceso denegado');
}
require_once __DIR__ . '/../../includes/header.php';

if (false && (!isset($_SESSION['id_usuario']) || (!tienePermiso('ventas_ver_listado') && !usuarioTieneRol([1, 2])))) {
    echo "<div class='alert alert-danger'>No tienes permiso para acceder a esta seccion.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

function columnaExisteListado(mysqli $conexion, string $tabla, string $columna): bool
{
    static $cache = [];
    $cacheKey = $tabla . '.' . $columna;

    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $stmt = $conexion->prepare("SELECT 1
                                FROM information_schema.columns
                                WHERE table_schema = DATABASE()
                                  AND table_name = ?
                                  AND column_name = ?
                                LIMIT 1");
    $stmt->bind_param('ss', $tabla, $columna);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $cache[$cacheKey] = $resultado && $resultado->num_rows > 0;
    $stmt->close();

    return $cache[$cacheKey];
}

function tablaExisteListado(mysqli $conexion, string $tabla): bool
{
    static $cache = [];

    if (array_key_exists($tabla, $cache)) {
        return $cache[$tabla];
    }

    $stmt = $conexion->prepare("SELECT 1
                                FROM information_schema.tables
                                WHERE table_schema = DATABASE()
                                  AND table_name = ?
                                LIMIT 1");
    $stmt->bind_param('s', $tabla);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $cache[$tabla] = $resultado && $resultado->num_rows > 0;
    $stmt->close();

    return $cache[$tabla];
}

function normalizarFechaFiltro(?string $valor): ?string
{
    if (!$valor) {
        return null;
    }

    $fecha = DateTime::createFromFormat('Y-m-d\TH:i', $valor);
    if (!$fecha) {
        return null;
    }

    return $fecha->format('Y-m-d H:i:s');
}

function construirFiltrosVentas(bool $ventasTieneArqueo): array
{
    $condiciones = ["v.estado = 'completada'"];
    $tipos = '';
    $parametros = [];

    $filtroCajero = isset($_GET['id_usuario']) ? (int)$_GET['id_usuario'] : 0;
    $filtroTurno = isset($_GET['id_arqueo']) ? (int)$_GET['id_arqueo'] : 0;
    $fechaInicio = normalizarFechaFiltro($_GET['fecha_inicio'] ?? null);
    $fechaFin = normalizarFechaFiltro($_GET['fecha_fin'] ?? null);

    if ($filtroCajero > 0) {
        $condiciones[] = 'v.id_usuario = ?';
        $tipos .= 'i';
        $parametros[] = $filtroCajero;
    }

    if ($ventasTieneArqueo && $filtroTurno > 0) {
        $condiciones[] = 'v.id_arqueo = ?';
        $tipos .= 'i';
        $parametros[] = $filtroTurno;
    }

    if ($fechaInicio) {
        $condiciones[] = 'v.fecha_venta >= ?';
        $tipos .= 's';
        $parametros[] = $fechaInicio;
    }

    if ($fechaFin) {
        $condiciones[] = 'v.fecha_venta <= ?';
        $tipos .= 's';
        $parametros[] = $fechaFin;
    }

    return [
        'where' => ' WHERE ' . implode(' AND ', $condiciones),
        'types' => $tipos,
        'params' => $parametros
    ];
}

function ejecutarConsultaPreparada(mysqli $conexion, string $sql, string $types = '', array $params = []): mysqli_result
{
    $stmt = $conexion->prepare($sql);

    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();
    $stmt->close();

    return $resultado;
}

$esAdministrador = isset($_SESSION['usuario']) && (int)($_SESSION['usuario']['id_rol'] === 1);
$ventasTieneArqueo = columnaExisteListado($conexion, 'ventas', 'id_arqueo');
$detalleTieneAuditoria = columnaExisteListado($conexion, 'detalle_ventas', 'descripcion_item');
$ventaPagosDisponible = tablaExisteListado($conexion, 'venta_pagos');
$filtros = construirFiltrosVentas($ventasTieneArqueo);

$usuarios = $conexion->query("SELECT id_usuario, nombre_completo FROM usuarios ORDER BY nombre_completo ASC");
$turnos = $conexion->query("SELECT t.id_turno, t.fecha_apertura, t.fecha_cierre, u.nombre_completo
                            FROM turnos_caja t
                            JOIN usuarios u ON u.id_usuario = t.id_usuario
                            ORDER BY t.fecha_apertura DESC
                            LIMIT 100");

$sqlMetodoNormalizado = "REPLACE(REPLACE(REPLACE(REPLACE(LOWER(%s), '_', ''), '/', ''), '-', ''), ' ', '')";

if ($ventaPagosDisponible) {
    $sqlResumenPagos = "SELECT
                            COALESCE(SUM(CASE WHEN " . sprintf($sqlMetodoNormalizado, 'vp.metodo_pago') . " = 'efectivo' THEN vp.monto ELSE 0 END), 0) AS total_efectivo,
                            COALESCE(SUM(CASE WHEN " . sprintf($sqlMetodoNormalizado, 'vp.metodo_pago') . " IN ('yape', 'plin', 'yapeplin', 'transferencia', 'tarjeta', 'tarjetacredito', 'tarjetadebito') THEN vp.monto ELSE 0 END), 0) AS total_digital
                       FROM ventas v
                       LEFT JOIN venta_pagos vp ON vp.id_venta = v.id_venta" . $filtros['where'];
    $resumenPagos = ejecutarConsultaPreparada($conexion, $sqlResumenPagos, $filtros['types'], $filtros['params'])->fetch_assoc();

    $sqlResumenBruto = "SELECT COALESCE(SUM(v.total_venta), 0) AS total_bruto
                        FROM ventas v" . $filtros['where'];
    $resumenBruto = ejecutarConsultaPreparada($conexion, $sqlResumenBruto, $filtros['types'], $filtros['params'])->fetch_assoc();

    $resumen = [
        'total_efectivo' => (float)($resumenPagos['total_efectivo'] ?? 0),
        'total_digital' => (float)($resumenPagos['total_digital'] ?? 0),
        'total_bruto' => (float)($resumenBruto['total_bruto'] ?? 0)
    ];
} else {
    $sqlResumen = "SELECT
                        COALESCE(SUM(CASE WHEN " . sprintf($sqlMetodoNormalizado, 'v.metodo_pago') . " = 'efectivo' THEN v.total_venta ELSE 0 END), 0) AS total_efectivo,
                        COALESCE(SUM(CASE WHEN " . sprintf($sqlMetodoNormalizado, 'v.metodo_pago') . " IN ('yape', 'plin', 'yapeplin', 'transferencia', 'tarjeta', 'tarjetacredito', 'tarjetadebito') THEN v.total_venta ELSE 0 END), 0) AS total_digital,
                        COALESCE(SUM(v.total_venta), 0) AS total_bruto
                   FROM ventas v" . $filtros['where'];
    $resumen = ejecutarConsultaPreparada($conexion, $sqlResumen, $filtros['types'], $filtros['params'])->fetch_assoc();
}

$sqlVentas = "SELECT v.id_venta,
                     v.fecha_venta,
                     v.total_venta,
                     GROUP_CONCAT(DISTINCT vp.metodo_pago SEPARATOR ' + ') AS metodos_pago,
                     v.metodo_pago AS metodo_antiguo,
                     " . ($ventasTieneArqueo ? "v.id_arqueo" : "NULL") . " AS id_arqueo,
                     IFNULL(c.nombre_cliente, 'Venta generica') AS cliente,
                     u.nombre_completo AS cajero
              FROM ventas v
              LEFT JOIN clientes c ON c.id_cliente = v.id_cliente
              JOIN usuarios u ON u.id_usuario = v.id_usuario
              LEFT JOIN venta_pagos vp ON vp.id_venta = v.id_venta" .
              $filtros['where'] .
              " GROUP BY v.id_venta
                ORDER BY v.fecha_venta DESC, v.id_venta DESC";
$ventas = ejecutarConsultaPreparada($conexion, $sqlVentas, $filtros['types'], $filtros['params']);

$valorFechaInicio = htmlspecialchars($_GET['fecha_inicio'] ?? '');
$valorFechaFin = htmlspecialchars($_GET['fecha_fin'] ?? '');
$valorUsuario = isset($_GET['id_usuario']) ? (int)$_GET['id_usuario'] : 0;
$valorTurno = isset($_GET['id_arqueo']) ? (int)$_GET['id_arqueo'] : 0;
?>

<style>
    .audit-card {
        border: 0;
        border-radius: 1rem;
        box-shadow: 0 0.35rem 1rem rgba(15, 23, 42, 0.06);
    }

    .audit-card .display-6 {
        font-weight: 700;
    }

    .filters-card {
        border: 0;
        border-radius: 1rem;
        box-shadow: 0 0.35rem 1rem rgba(15, 23, 42, 0.05);
    }
</style>

<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="m-0"><i class="fas fa-history"></i> Historial de Ventas</h2>
            <small class="text-muted">Auditoria por cajero, rango horario y turno de caja.</small>
        </div>
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-cash-register"></i> Volver al POS
        </a>
    </div>

    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?php echo $_SESSION['mensaje_tipo'] ?? 'info'; ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($_SESSION['mensaje']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); ?>
    <?php endif; ?>

    <?php if (!$ventasTieneArqueo): ?>
        <div class="alert alert-warning">
            Ejecuta <strong>ventas_auditoria.sql</strong> para habilitar la auditoria por turno y la vinculacion automatica con caja.
        </div>
    <?php endif; ?>

    <?php if (!$detalleTieneAuditoria): ?>
        <div class="alert alert-info">
            Los detalles nuevos guardaran la descripcion auditada de combos y presentaciones. Las ventas antiguas seguiran mostrandose como productos estandar.
        </div>
    <?php endif; ?>

    <div class="card filters-card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="id_usuario" class="form-label">Cajero</label>
                    <select name="id_usuario" id="id_usuario" class="form-select">
                        <option value="0">Todos</option>
                        <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                            <option value="<?php echo (int)$usuario['id_usuario']; ?>" <?php echo $valorUsuario === (int)$usuario['id_usuario'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($usuario['nombre_completo']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="fecha_inicio" class="form-label">Fecha / hora inicio</label>
                    <input type="datetime-local" class="form-control" name="fecha_inicio" id="fecha_inicio" value="<?php echo $valorFechaInicio; ?>">
                </div>

                <div class="col-md-3">
                    <label for="fecha_fin" class="form-label">Fecha / hora fin</label>
                    <input type="datetime-local" class="form-control" name="fecha_fin" id="fecha_fin" value="<?php echo $valorFechaFin; ?>">
                </div>

                <div class="col-md-3">
                    <label for="id_arqueo" class="form-label">Turno</label>
                    <select name="id_arqueo" id="id_arqueo" class="form-select" <?php echo $ventasTieneArqueo ? '' : 'disabled'; ?>>
                        <option value="0">Todos</option>
                        <?php while ($turno = $turnos->fetch_assoc()): ?>
                            <option value="<?php echo (int)$turno['id_turno']; ?>" <?php echo $valorTurno === (int)$turno['id_turno'] ? 'selected' : ''; ?>>
                                <?php echo 'Turno #' . (int)$turno['id_turno'] . ' - ' . htmlspecialchars($turno['nombre_completo']) . ' - ' . date('d/m H:i', strtotime($turno['fecha_apertura'])); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-dark">
                        <i class="fas fa-filter"></i> Aplicar filtros
                    </button>
                    <a href="historial.php" class="btn btn-outline-secondary">
                        <i class="fas fa-eraser"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card audit-card h-100">
                <div class="card-body">
                    <div class="text-muted mb-1">Efectivo en turno</div>
                    <div class="display-6 text-success"><?php echo getMoneda() . number_format((float)$resumen['total_efectivo'], 2); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card audit-card h-100">
                <div class="card-body">
                    <div class="text-muted mb-1">Digital (Yape / Plin)</div>
                    <div class="display-6 text-primary"><?php echo getMoneda() . number_format((float)$resumen['total_digital'], 2); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card audit-card h-100">
                <div class="card-body">
                    <div class="text-muted mb-1">Total bruto</div>
                    <div class="display-6 text-dark"><?php echo getMoneda() . number_format((float)$resumen['total_bruto'], 2); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card filters-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Cajero</th>
                            <th>Turno</th>
                            <th>Total</th>
                            <th>Metodo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($ventas && $ventas->num_rows > 0): ?>
                            <?php while ($venta = $ventas->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw-semibold">#<?php echo (int)$venta['id_venta']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></td>
                                    <td><?php echo htmlspecialchars($venta['cliente']); ?></td>
                                    <td><?php echo htmlspecialchars($venta['cajero']); ?></td>
                                    <td>
                                        <?php if (!empty($venta['id_arqueo'])): ?>
                                            <span class="badge bg-light text-dark border">Turno #<?php echo (int)$venta['id_arqueo']; ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Sin turno</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold"><?php echo getMoneda() . number_format((float)$venta['total_venta'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($venta['metodos_pago'] ?: $venta['metodo_antiguo'] ?: '-'); ?></td>
                                    <td>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-dark btn-ver-detalle"
                                                data-id-venta="<?php echo (int)$venta['id_venta']; ?>"
                                                data-bs-toggle="modal"
                                                data-bs-target="#detalleVentaModal">
                                                <i class="fas fa-eye"></i> Ver detalle
                                            </button>
                                            <a href="ticket.php?id_venta=<?php echo (int)$venta['id_venta']; ?>" class="btn btn-sm btn-info text-white" target="_blank">
                                                <i class="fas fa-receipt"></i> Ticket
                                            </a>
                                            <?php if ($esAdministrador): ?>
                                                <form action="eliminar_venta.php" method="POST" onsubmit="return confirm('Esta accion restaurara stock y eliminara la venta. Deseas continuar?');">
                                                    <input type="hidden" name="id_venta" value="<?php echo (int)$venta['id_venta']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No se encontraron ventas con los filtros aplicados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="detalleVentaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Detalle de venta</h5>
                    <small class="text-muted" id="detalleVentaLabelSecundario">Cargando informacion...</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detalleVentaEstado" class="alert alert-light border">Selecciona una venta para ver el detalle.</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Cantidad</th>
                                <th>Precio unitario</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="detalleVentaBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
    async function fetchDetalleVenta(idVenta) {
        const response = await fetch(`obtener_detalle_venta.php?id_venta=${encodeURIComponent(idVenta)}`, {
            headers: { 'Accept': 'application/json' }
        });

        const rawText = await response.text();
        let data;

        try {
            data = rawText ? JSON.parse(rawText) : {};
        } catch (error) {
            console.error('Respuesta no valida del detalle de venta', { idVenta, rawText });
            throw new Error('La respuesta del servidor no es JSON valido.');
        }

        if (!response.ok || !data.success) {
            throw new Error(data?.error || 'No se pudo obtener el detalle de la venta.');
        }

        return data;
    }

    function renderDetalleVenta(items) {
        const tbody = document.getElementById('detalleVentaBody');
        const estado = document.getElementById('detalleVentaEstado');

        tbody.innerHTML = '';

        if (!Array.isArray(items) || items.length === 0) {
            estado.className = 'alert alert-warning';
            estado.textContent = 'La venta no tiene detalle disponible.';
            return;
        }

        estado.className = 'alert alert-success';
        estado.textContent = `Se encontraron ${items.length} item(s) en la venta.`;

        items.forEach(item => {
            const tr = document.createElement('tr');
            const esCombo = item.tipo_item === 'combo';
            const detalleExtra = esCombo && Array.isArray(item.componentes) && item.componentes.length > 0
                ? item.componentes.map(componente => `${componente.producto} x${componente.cantidad}`).join(', ')
                : (item.detalle || '');

            tr.innerHTML = `
                <td>
                    <div class="fw-semibold">${item.nombre}</div>
                    <small class="text-muted">${detalleExtra}</small>
                </td>
                <td>${item.cantidad}</td>
                <td><?php echo getMoneda(); ?>${Number(item.precio_unitario || 0).toFixed(2)}</td>
                <td class="fw-bold"><?php echo getMoneda(); ?>${Number(item.subtotal || 0).toFixed(2)}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    document.addEventListener('click', async (event) => {
        const boton = event.target.closest('.btn-ver-detalle');
        if (!boton) {
            return;
        }

        const idVenta = boton.getAttribute('data-id-venta');
        const estado = document.getElementById('detalleVentaEstado');
        const labelSecundario = document.getElementById('detalleVentaLabelSecundario');
        document.getElementById('detalleVentaBody').innerHTML = '';

        estado.className = 'alert alert-light border';
        estado.textContent = 'Cargando detalle de la venta...';
        labelSecundario.textContent = `Venta #${idVenta}`;

        try {
            const data = await fetchDetalleVenta(idVenta);
            renderDetalleVenta(data.items || []);
        } catch (error) {
            console.error('Error cargando detalle de venta', error);
            estado.className = 'alert alert-danger';
            estado.textContent = error.message || 'No se pudo cargar el detalle.';
        }
    });
</script>
