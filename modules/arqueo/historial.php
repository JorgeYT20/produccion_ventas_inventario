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

function normalizarFechaArqueo(?string $valor, string $hora = '00:00:00'): ?string
{
    if (!$valor) {
        return null;
    }

    $fecha = DateTime::createFromFormat('Y-m-d', $valor);
    if (!$fecha) {
        return null;
    }

    return $fecha->format('Y-m-d') . ' ' . $hora;
}

function ejecutarConsultaArqueo(mysqli $conexion, string $sql, string $types = '', array $params = []): mysqli_result
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

$filtroUsuario = isset($_GET['id_usuario']) ? max(0, (int)$_GET['id_usuario']) : 0;
$filtroEstado = trim((string)($_GET['estado'] ?? ''));
$fechaInicio = $_GET['fecha_inicio'] ?? '';
$fechaFin = $_GET['fecha_fin'] ?? '';

$where = ['1 = 1'];
$types = '';
$params = [];

$fechaInicioSql = normalizarFechaArqueo($fechaInicio);
$fechaFinSql = normalizarFechaArqueo($fechaFin, '23:59:59');

if ($filtroUsuario > 0) {
    $where[] = 't.id_usuario = ?';
    $types .= 'i';
    $params[] = $filtroUsuario;
}

if (in_array($filtroEstado, ['abierto', 'cerrado'], true)) {
    $where[] = 't.estado = ?';
    $types .= 's';
    $params[] = $filtroEstado;
}

if ($fechaInicioSql) {
    $where[] = 't.fecha_apertura >= ?';
    $types .= 's';
    $params[] = $fechaInicioSql;
}

if ($fechaFinSql) {
    $where[] = 't.fecha_apertura <= ?';
    $types .= 's';
    $params[] = $fechaFinSql;
}

$whereSql = ' WHERE ' . implode(' AND ', $where);

$usuarios = $conexion->query("SELECT id_usuario, nombre_completo FROM usuarios WHERE activo = 1 ORDER BY nombre_completo ASC");

$sqlCards = "SELECT
                COUNT(*) AS total_arqueos,
                COALESCE(SUM(COALESCE(t.monto_final_real, t.monto_final_sistema, t.monto_inicial)), 0) AS total_dinero_manejado,
                COALESCE(SUM(CASE WHEN t.diferencia < 0 THEN ABS(t.diferencia) ELSE 0 END), 0) AS total_faltantes,
                COALESCE(SUM(CASE WHEN t.diferencia > 0 THEN t.diferencia ELSE 0 END), 0) AS total_sobrantes
             FROM turnos_caja t" . $whereSql;
$cards = ejecutarConsultaArqueo($conexion, $sqlCards, $types, $params)->fetch_assoc();

$sqlHistorial = "SELECT
                    t.id_turno,
                    u.nombre_completo AS cajero,
                    t.fecha_apertura,
                    t.fecha_cierre,
                    t.monto_inicial,
                    t.monto_final_sistema,
                    t.monto_final_real,
                    t.diferencia,
                    t.estado
                 FROM turnos_caja t
                 JOIN usuarios u ON u.id_usuario = t.id_usuario" . $whereSql . "
                 ORDER BY t.fecha_apertura DESC, t.id_turno DESC";
$arqueos = ejecutarConsultaArqueo($conexion, $sqlHistorial, $types, $params);
?>

<style>
    .diferencia-perfecta {
        color: #198754;
        font-weight: 700;
    }

    .diferencia-faltante {
        color: #dc3545;
        font-weight: 700;
    }

    .diferencia-sobrante {
        color: #d39e00;
        font-weight: 700;
    }
</style>

<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="m-0"><i class="fas fa-cash-register"></i> Historial de Arqueos</h1>
            <p class="text-muted mb-0">Auditoría de turnos, diferencias y ventas conciliadas por caja.</p>
        </div>
        <a href="/mi_sistema/modules/caja/index.php" class="btn btn-outline-secondary">Volver a Caja</a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="id_usuario">Cajero</label>
                    <select name="id_usuario" id="id_usuario" class="form-select">
                        <option value="0">Todos</option>
                        <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                            <option value="<?php echo (int)$usuario['id_usuario']; ?>" <?php echo $filtroUsuario === (int)$usuario['id_usuario'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($usuario['nombre_completo']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="fecha_inicio">Fecha inicio</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="<?php echo htmlspecialchars($fechaInicio); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="fecha_fin">Fecha fin</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="<?php echo htmlspecialchars($fechaFin); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="estado">Estado</label>
                    <select name="estado" id="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="abierto" <?php echo $filtroEstado === 'abierto' ? 'selected' : ''; ?>>Abierto</option>
                        <option value="cerrado" <?php echo $filtroEstado === 'cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                    <a href="historial.php" class="btn btn-outline-secondary"><i class="fas fa-eraser"></i> Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Total arqueos</div>
                    <div class="fs-3 fw-bold"><?php echo (int)$cards['total_arqueos']; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Total dinero manejado</div>
                    <div class="fs-3 fw-bold text-primary"><?php echo getMoneda() . number_format((float)$cards['total_dinero_manejado'], 2); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Total faltantes</div>
                    <div class="fs-3 fw-bold text-danger"><?php echo getMoneda() . number_format((float)$cards['total_faltantes'], 2); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Total sobrantes</div>
                    <div class="fs-3 fw-bold text-warning"><?php echo getMoneda() . number_format((float)$cards['total_sobrantes'], 2); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID Arqueo</th>
                        <th>Cajero</th>
                        <th>Fecha apertura</th>
                        <th>Fecha cierre</th>
                        <th>Monto apertura</th>
                        <th>Monto sistema</th>
                        <th>Monto cierre</th>
                        <th>Diferencia</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($arqueos && $arqueos->num_rows > 0): ?>
                        <?php while ($arqueo = $arqueos->fetch_assoc()): ?>
                            <?php
                                $diferencia = (float)($arqueo['diferencia'] ?? 0);
                                $claseDiferencia = $diferencia == 0
                                    ? 'diferencia-perfecta'
                                    : ($diferencia < 0 ? 'diferencia-faltante' : 'diferencia-sobrante');
                            ?>
                            <tr>
                                <td>#<?php echo (int)$arqueo['id_turno']; ?></td>
                                <td><?php echo htmlspecialchars($arqueo['cajero']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($arqueo['fecha_apertura'])); ?></td>
                                <td><?php echo !empty($arqueo['fecha_cierre']) ? date('d/m/Y H:i', strtotime($arqueo['fecha_cierre'])) : '<span class="text-muted">En curso</span>'; ?></td>
                                <td><?php echo getMoneda() . number_format((float)$arqueo['monto_inicial'], 2); ?></td>
                                <td><?php echo getMoneda() . number_format((float)($arqueo['monto_final_sistema'] ?? 0), 2); ?></td>
                                <td><?php echo getMoneda() . number_format((float)($arqueo['monto_final_real'] ?? 0), 2); ?></td>
                                <td class="<?php echo $claseDiferencia; ?>"><?php echo getMoneda() . number_format($diferencia, 2); ?></td>
                                <td>
                                    <span class="badge <?php echo $arqueo['estado'] === 'cerrado' ? 'bg-success-subtle text-success-emphasis border border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'; ?>">
                                        <?php echo htmlspecialchars(ucfirst($arqueo['estado'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-dark btn-ver-arqueo"
                                        data-id-turno="<?php echo (int)$arqueo['id_turno']; ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#detalleArqueoModal">
                                        <i class="fas fa-eye"></i> Ver detalle
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No se encontraron arqueos con los filtros aplicados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="detalleArqueoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Detalle de arqueo</h5>
                    <small class="text-muted" id="detalleArqueoLabel">Cargando...</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detalleArqueoEstado" class="alert alert-light border">Selecciona un arqueo para ver su resumen.</div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <div class="text-muted small">Ventas del turno</div>
                                <div class="fs-4 fw-bold" id="detalleNumeroVentas">0</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <div class="text-muted small">Total efectivo</div>
                                <div class="fs-4 fw-bold text-success" id="detalleTotalEfectivo"><?php echo getMoneda(); ?>0.00</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <div class="text-muted small">Total digital</div>
                                <div class="fs-4 fw-bold text-primary" id="detalleTotalDigital"><?php echo getMoneda(); ?>0.00</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <div class="text-muted small">Total general</div>
                                <div class="fs-4 fw-bold" id="detalleTotalGeneral"><?php echo getMoneda(); ?>0.00</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-7">
                        <h6 class="fw-semibold">Ventas del turno</h6>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>N° Venta</th>
                                        <th>Método</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody id="detalleArqueoVentas"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <h6 class="fw-semibold">Productos vendidos</h6>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody id="detalleArqueoProductos"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    async function fetchDetalleArqueo(idTurno) {
        const response = await fetch(`obtener_detalle.php?id_turno=${encodeURIComponent(idTurno)}`, {
            headers: { 'Accept': 'application/json' }
        });

        const rawText = await response.text();
        let data;

        try {
            data = rawText ? JSON.parse(rawText) : {};
        } catch (error) {
            console.error('Respuesta no válida del detalle de arqueo', { idTurno, rawText });
            throw new Error('La respuesta del servidor no es JSON válido.');
        }

        if (!response.ok || !data.success) {
            throw new Error(data?.error || 'No se pudo obtener el detalle del arqueo.');
        }

        return data;
    }

    function renderDetalleArqueo(data) {
        const resumen = data.resumen || {};
        const ventas = Array.isArray(data.ventas) ? data.ventas : [];
        const productos = Array.isArray(data.productos) ? data.productos : [];

        document.getElementById('detalleArqueoLabel').textContent = `Turno #${data.turno.id_turno} - ${data.turno.nombre_completo}`;
        document.getElementById('detalleNumeroVentas').textContent = Number(resumen.numero_ventas || 0);
        document.getElementById('detalleTotalEfectivo').textContent = `<?php echo getMoneda(); ?>${Number(resumen.total_efectivo || 0).toFixed(2)}`;
        document.getElementById('detalleTotalDigital').textContent = `<?php echo getMoneda(); ?>${Number(resumen.total_digital || 0).toFixed(2)}`;
        document.getElementById('detalleTotalGeneral').textContent = `<?php echo getMoneda(); ?>${Number(resumen.total_general || 0).toFixed(2)}`;

        const ventasBody = document.getElementById('detalleArqueoVentas');
        ventasBody.innerHTML = '';
        ventas.forEach(venta => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${new Date(venta.fecha_venta).toLocaleString('es-PE')}</td>
                <td>#${venta.id_venta}</td>
                <td>${venta.metodo_pago}</td>
                <td><?php echo getMoneda(); ?>${Number(venta.total_venta || 0).toFixed(2)}</td>
            `;
            ventasBody.appendChild(tr);
        });
        if (ventas.length === 0) {
            ventasBody.innerHTML = '<tr><td colspan="4" class="text-muted">No hay ventas en este turno.</td></tr>';
        }

        const productosBody = document.getElementById('detalleArqueoProductos');
        productosBody.innerHTML = '';
        productos.forEach(producto => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${producto.nombre}</td>
                <td>${producto.cantidad_vendida}</td>
            `;
            productosBody.appendChild(tr);
        });
        if (productos.length === 0) {
            productosBody.innerHTML = '<tr><td colspan="2" class="text-muted">No hay productos vendidos en este turno.</td></tr>';
        }

        const estado = document.getElementById('detalleArqueoEstado');
        estado.className = 'alert alert-success';
        estado.textContent = `Turno ${data.turno.estado} | Apertura: ${new Date(data.turno.fecha_apertura).toLocaleString('es-PE')}`;
    }

    document.addEventListener('click', async (event) => {
        const boton = event.target.closest('.btn-ver-arqueo');
        if (!boton) {
            return;
        }

        const idTurno = boton.getAttribute('data-id-turno');
        const estado = document.getElementById('detalleArqueoEstado');
        estado.className = 'alert alert-light border';
        estado.textContent = 'Cargando detalle del arqueo...';
        document.getElementById('detalleArqueoVentas').innerHTML = '';
        document.getElementById('detalleArqueoProductos').innerHTML = '';

        try {
            const data = await fetchDetalleArqueo(idTurno);
            renderDetalleArqueo(data);
        } catch (error) {
            console.error('Error cargando detalle del arqueo', error);
            estado.className = 'alert alert-danger';
            estado.textContent = error.message || 'No se pudo cargar el detalle.';
        }
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
