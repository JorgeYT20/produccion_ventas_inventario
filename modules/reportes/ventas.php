<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers_ventas.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($_SESSION['id_usuario']) || !tienePermiso('reportes_ver_ventas')) {
    echo "<div class='alert alert-danger'>No tienes permiso para acceder.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$filtros = obtenerFiltrosReporteVentas($_GET);
$reporte = obtenerDatosReporteVentas($conexion, $filtros);
$resumen = $reporte['resumen'];
$opciones = $reporte['opciones'];
$queryExport = http_build_query([
    'fecha_inicio' => $filtros['fecha_inicio'],
    'fecha_fin' => $filtros['fecha_fin'],
    'id_usuario' => $filtros['id_usuario'],
    'metodo_pago' => $filtros['metodo_pago'],
    'id_arqueo' => $filtros['id_arqueo']
]);
?>

<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="m-0"><i class="fas fa-chart-bar"></i> Reporte Profesional de Ventas</h1>
            <p class="text-muted mb-0">Auditoría de ingresos, rendimiento por cajero y validación por turno.</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">Volver al Menú</a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="fecha_inicio">Fecha inicio</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="<?php echo htmlspecialchars($filtros['fecha_inicio']); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="fecha_fin">Fecha fin</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="<?php echo htmlspecialchars($filtros['fecha_fin']); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="id_usuario">Cajero</label>
                    <select name="id_usuario" id="id_usuario" class="form-select">
                        <option value="0">Todos</option>
                        <?php foreach ($opciones['cajeros'] as $cajero): ?>
                            <option value="<?php echo (int)$cajero['id_usuario']; ?>" <?php echo $filtros['id_usuario'] === (int)$cajero['id_usuario'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cajero['nombre_completo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="metodo_pago">Método de pago</label>
                    <select name="metodo_pago" id="metodo_pago" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($opciones['metodos_pago'] as $valor => $label): ?>
                            <option value="<?php echo htmlspecialchars($valor); ?>" <?php echo $filtros['metodo_pago'] === $valor ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="id_arqueo">Turno</label>
                    <select name="id_arqueo" id="id_arqueo" class="form-select" <?php echo $reporte['tiene_arqueo'] ? '' : 'disabled'; ?>>
                        <option value="0">Todos</option>
                        <?php foreach ($opciones['turnos'] as $turno): ?>
                            <option value="<?php echo (int)$turno['id_turno']; ?>" <?php echo $filtros['id_arqueo'] === (int)$turno['id_turno'] ? 'selected' : ''; ?>>
                                <?php echo 'Turno #' . (int)$turno['id_turno'] . ' - ' . htmlspecialchars($turno['nombre_completo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Generar reporte</button>
                    <a href="ventas.php" class="btn btn-outline-secondary"><i class="fas fa-eraser"></i> Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body d-flex flex-wrap gap-2">
            <button onclick="window.print();" class="btn btn-info text-white"><i class="fas fa-print"></i> Imprimir</button>
            <a href="exportar_ventas_excel.php?<?php echo htmlspecialchars($queryExport); ?>" class="btn btn-success"><i class="fas fa-file-excel"></i> Exportar Excel</a>
            <a href="exportar_ventas_pdf.php?<?php echo htmlspecialchars($queryExport); ?>" class="btn btn-danger"><i class="fas fa-file-pdf"></i> Exportar PDF</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4 col-xl">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Total de ingresos</div>
                    <div class="fs-3 fw-bold text-success"><?php echo getMoneda() . number_format((float)$resumen['total_ingresos'], 2); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-xl">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Número de ventas</div>
                    <div class="fs-3 fw-bold"><?php echo (int)$resumen['numero_ventas']; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-xl">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Ticket promedio</div>
                    <div class="fs-3 fw-bold"><?php echo getMoneda() . number_format((float)$resumen['ticket_promedio'], 2); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Venta máxima</div>
                    <div class="fs-3 fw-bold text-primary"><?php echo getMoneda() . number_format((float)$resumen['venta_maxima'], 2); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Venta mínima</div>
                    <div class="fs-3 fw-bold text-dark"><?php echo getMoneda() . number_format((float)$resumen['venta_minima'], 2); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold">Desglose por método de pago</div>
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Método</th>
                                <th>Ventas</th>
                                <th>Total</th>
                                <th>% del total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reporte['metodos_pago'] as $fila): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(etiquetaMetodoPagoReporte($fila['metodo_pago'])); ?></td>
                                    <td><?php echo (int)$fila['numero_ventas']; ?></td>
                                    <td><?php echo getMoneda() . number_format((float)$fila['total'], 2); ?></td>
                                    <td><?php echo number_format(porcentajeReporte((float)$fila['total'], (float)$resumen['total_ingresos']), 2); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold">Ventas por turno</div>
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Turno</th>
                                <th>Número de ventas</th>
                                <th>Total ventas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($reporte['ventas_turno'])): ?>
                                <?php foreach ($reporte['ventas_turno'] as $fila): ?>
                                    <tr>
                                        <td><?php echo !empty($fila['id_arqueo']) ? 'Turno #' . (int)$fila['id_arqueo'] : 'Sin turno'; ?></td>
                                        <td><?php echo (int)$fila['numero_ventas']; ?></td>
                                        <td><?php echo getMoneda() . number_format((float)$fila['total_ventas'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-muted">No hay datos de turnos para este filtro.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold">Ventas por cajero</div>
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Cajero</th>
                                <th>Número de ventas</th>
                                <th>Total vendido</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reporte['ventas_cajero'] as $fila): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($fila['cajero']); ?></td>
                                    <td><?php echo (int)$fila['numero_ventas']; ?></td>
                                    <td><?php echo getMoneda() . number_format((float)$fila['total_vendido'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold">Top 10 productos más vendidos</div>
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad vendida</th>
                                <th>Total generado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reporte['top_productos'] as $fila): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($fila['producto']); ?></td>
                                    <td><?php echo (int)$fila['cantidad_vendida']; ?></td>
                                    <td><?php echo getMoneda() . number_format((float)$fila['total_generado'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header bg-white fw-semibold">Detalle completo de ventas</div>
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>N° Venta</th>
                        <th>Cajero</th>
                        <th>Método de pago</th>
                        <th>Turno</th>
                        <th>Total</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reporte['detalle_ventas'] as $venta): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></td>
                            <td>#<?php echo (int)$venta['id_venta']; ?></td>
                            <td><?php echo htmlspecialchars($venta['cajero']); ?></td>
                            <td><?php echo htmlspecialchars(etiquetaMetodoPagoReporte($venta['metodo_pago'])); ?></td>
                            <td><?php echo !empty($venta['id_arqueo']) ? 'Turno #' . (int)$venta['id_arqueo'] : 'Sin turno'; ?></td>
                            <td class="fw-semibold"><?php echo getMoneda() . number_format((float)$venta['total_venta'], 2); ?></td>
                            <td><span class="badge bg-success-subtle text-success-emphasis border border-success-subtle"><?php echo htmlspecialchars(ucfirst($venta['estado'])); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
