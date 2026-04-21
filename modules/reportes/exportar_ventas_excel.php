<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers_ventas.php';

if (!isset($_SESSION['id_usuario']) || !tienePermiso('reportes_ver_ventas')) {
    exit;
}

$filtros = obtenerFiltrosReporteVentas($_GET);
$reporte = obtenerDatosReporteVentas($conexion, $filtros);
$resumen = $reporte['resumen'];
$generadoPor = $_SESSION['nombre_usuario'] ?? ($_SESSION['usuario']['nombre_completo'] ?? 'Sistema');
$fechaGeneracion = date('d/m/Y H:i');

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=reporte_ventas_" . date('Y-m-d_His') . ".xls");
?>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body>
    <h2><?php echo htmlspecialchars(getConfig('nombre_tienda') ?? 'Licorería'); ?></h2>
    <p>Reporte profesional de ventas</p>
    <p>Generado: <?php echo $fechaGeneracion; ?></p>
    <p>Usuario: <?php echo htmlspecialchars($generadoPor); ?></p>
    <p>Periodo: <?php echo htmlspecialchars($filtros['fecha_inicio']); ?> al <?php echo htmlspecialchars($filtros['fecha_fin']); ?></p>

    <table border="1">
        <tr><th colspan="5">Resumen ejecutivo</th></tr>
        <tr>
            <th>Total ingresos</th>
            <th>Número ventas</th>
            <th>Ticket promedio</th>
            <th>Venta máxima</th>
            <th>Venta mínima</th>
        </tr>
        <tr>
            <td><?php echo getMoneda() . number_format((float)$resumen['total_ingresos'], 2); ?></td>
            <td><?php echo (int)$resumen['numero_ventas']; ?></td>
            <td><?php echo getMoneda() . number_format((float)$resumen['ticket_promedio'], 2); ?></td>
            <td><?php echo getMoneda() . number_format((float)$resumen['venta_maxima'], 2); ?></td>
            <td><?php echo getMoneda() . number_format((float)$resumen['venta_minima'], 2); ?></td>
        </tr>
    </table>
    <br>

    <table border="1">
        <tr><th colspan="4">Desglose por método de pago</th></tr>
        <tr><th>Método</th><th>Ventas</th><th>Total</th><th>% del total</th></tr>
        <?php foreach ($reporte['metodos_pago'] as $fila): ?>
            <tr>
                <td><?php echo htmlspecialchars(etiquetaMetodoPagoReporte($fila['metodo_pago'])); ?></td>
                <td><?php echo (int)$fila['numero_ventas']; ?></td>
                <td><?php echo getMoneda() . number_format((float)$fila['total'], 2); ?></td>
                <td><?php echo number_format(porcentajeReporte((float)$fila['total'], (float)$resumen['total_ingresos']), 2); ?>%</td>
            </tr>
        <?php endforeach; ?>
    </table>
    <br>

    <table border="1">
        <tr><th colspan="3">Ventas por turno</th></tr>
        <tr><th>Turno</th><th>Número ventas</th><th>Total ventas</th></tr>
        <?php foreach ($reporte['ventas_turno'] as $fila): ?>
            <tr>
                <td><?php echo !empty($fila['id_arqueo']) ? 'Turno #' . (int)$fila['id_arqueo'] : 'Sin turno'; ?></td>
                <td><?php echo (int)$fila['numero_ventas']; ?></td>
                <td><?php echo getMoneda() . number_format((float)$fila['total_ventas'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <br>

    <table border="1">
        <tr><th colspan="3">Ventas por cajero</th></tr>
        <tr><th>Cajero</th><th>Número ventas</th><th>Total vendido</th></tr>
        <?php foreach ($reporte['ventas_cajero'] as $fila): ?>
            <tr>
                <td><?php echo htmlspecialchars($fila['cajero']); ?></td>
                <td><?php echo (int)$fila['numero_ventas']; ?></td>
                <td><?php echo getMoneda() . number_format((float)$fila['total_vendido'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <br>

    <table border="1">
        <tr><th colspan="3">Top 10 productos más vendidos</th></tr>
        <tr><th>Producto</th><th>Cantidad vendida</th><th>Total generado</th></tr>
        <?php foreach ($reporte['top_productos'] as $fila): ?>
            <tr>
                <td><?php echo htmlspecialchars($fila['producto']); ?></td>
                <td><?php echo (int)$fila['cantidad_vendida']; ?></td>
                <td><?php echo getMoneda() . number_format((float)$fila['total_generado'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <br>

    <table border="1">
        <tr><th colspan="7">Detalle de ventas</th></tr>
        <tr><th>Fecha</th><th>N° Venta</th><th>Cajero</th><th>Método</th><th>Turno</th><th>Total</th><th>Estado</th></tr>
        <?php foreach ($reporte['detalle_ventas'] as $venta): ?>
            <tr>
                <td><?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></td>
                <td>#<?php echo (int)$venta['id_venta']; ?></td>
                <td><?php echo htmlspecialchars($venta['cajero']); ?></td>
                <td><?php echo htmlspecialchars(etiquetaMetodoPagoReporte($venta['metodo_pago'])); ?></td>
                <td><?php echo !empty($venta['id_arqueo']) ? 'Turno #' . (int)$venta['id_arqueo'] : 'Sin turno'; ?></td>
                <td><?php echo getMoneda() . number_format((float)$venta['total_venta'], 2); ?></td>
                <td><?php echo htmlspecialchars(ucfirst($venta['estado'])); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
