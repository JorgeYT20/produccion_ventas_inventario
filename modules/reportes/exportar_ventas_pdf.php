<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers_ventas.php';
require_once __DIR__ . '/../../dompdf/autoload.inc.php';

use Dompdf\Dompdf;

if (!isset($_SESSION['id_usuario']) || !tienePermiso('reportes_ver_ventas')) {
    exit;
}

$filtros = obtenerFiltrosReporteVentas($_GET);
$reporte = obtenerDatosReporteVentas($conexion, $filtros);
$resumen = $reporte['resumen'];
$generadoPor = $_SESSION['nombre_usuario'] ?? ($_SESSION['usuario']['nombre_completo'] ?? 'Sistema');
$fechaGeneracion = date('d/m/Y H:i');

$html = '<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
h1, h2, h3 { margin-bottom: 6px; }
.muted { color: #6b7280; }
table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
th, td { border: 1px solid #d1d5db; padding: 7px; text-align: left; }
th { background: #f3f4f6; }
.cards td { width: 20%; }
</style>';

$html .= '<h1>' . htmlspecialchars(getConfig('nombre_tienda') ?? 'Licorería') . '</h1>';
$html .= '<p class="muted">Reporte profesional de ventas</p>';
$html .= '<p><strong>Generado:</strong> ' . $fechaGeneracion . '<br><strong>Usuario:</strong> ' . htmlspecialchars($generadoPor) . '<br><strong>Periodo:</strong> ' . htmlspecialchars($filtros['fecha_inicio']) . ' al ' . htmlspecialchars($filtros['fecha_fin']) . '</p>';

$html .= '<h2>Resumen ejecutivo</h2>';
$html .= '<table class="cards"><tr>';
$html .= '<td><strong>Total ingresos</strong><br>' . getMoneda() . number_format((float)$resumen['total_ingresos'], 2) . '</td>';
$html .= '<td><strong>Número ventas</strong><br>' . (int)$resumen['numero_ventas'] . '</td>';
$html .= '<td><strong>Ticket promedio</strong><br>' . getMoneda() . number_format((float)$resumen['ticket_promedio'], 2) . '</td>';
$html .= '<td><strong>Venta máxima</strong><br>' . getMoneda() . number_format((float)$resumen['venta_maxima'], 2) . '</td>';
$html .= '<td><strong>Venta mínima</strong><br>' . getMoneda() . number_format((float)$resumen['venta_minima'], 2) . '</td>';
$html .= '</tr></table>';

$html .= '<h2>Desglose por método de pago</h2><table><tr><th>Método</th><th>Ventas</th><th>Total</th><th>%</th></tr>';
foreach ($reporte['metodos_pago'] as $fila) {
    $html .= '<tr><td>' . htmlspecialchars(etiquetaMetodoPagoReporte($fila['metodo_pago'])) . '</td><td>' . (int)$fila['numero_ventas'] . '</td><td>' . getMoneda() . number_format((float)$fila['total'], 2) . '</td><td>' . number_format(porcentajeReporte((float)$fila['total'], (float)$resumen['total_ingresos']), 2) . '%</td></tr>';
}
$html .= '</table>';

$html .= '<h2>Ventas por turno</h2><table><tr><th>Turno</th><th>Número de ventas</th><th>Total ventas</th></tr>';
foreach ($reporte['ventas_turno'] as $fila) {
    $html .= '<tr><td>' . (!empty($fila['id_arqueo']) ? 'Turno #' . (int)$fila['id_arqueo'] : 'Sin turno') . '</td><td>' . (int)$fila['numero_ventas'] . '</td><td>' . getMoneda() . number_format((float)$fila['total_ventas'], 2) . '</td></tr>';
}
$html .= '</table>';

$html .= '<h2>Ventas por cajero</h2><table><tr><th>Cajero</th><th>Número de ventas</th><th>Total vendido</th></tr>';
foreach ($reporte['ventas_cajero'] as $fila) {
    $html .= '<tr><td>' . htmlspecialchars($fila['cajero']) . '</td><td>' . (int)$fila['numero_ventas'] . '</td><td>' . getMoneda() . number_format((float)$fila['total_vendido'], 2) . '</td></tr>';
}
$html .= '</table>';

$html .= '<h2>Top 10 productos más vendidos</h2><table><tr><th>Producto</th><th>Cantidad vendida</th><th>Total generado</th></tr>';
foreach ($reporte['top_productos'] as $fila) {
    $html .= '<tr><td>' . htmlspecialchars($fila['producto']) . '</td><td>' . (int)$fila['cantidad_vendida'] . '</td><td>' . getMoneda() . number_format((float)$fila['total_generado'], 2) . '</td></tr>';
}
$html .= '</table>';

$html .= '<h2>Detalle de ventas</h2><table><tr><th>Fecha</th><th>N° Venta</th><th>Cajero</th><th>Método</th><th>Turno</th><th>Total</th><th>Estado</th></tr>';
foreach ($reporte['detalle_ventas'] as $venta) {
    $html .= '<tr><td>' . date('d/m/Y H:i', strtotime($venta['fecha_venta'])) . '</td><td>#' . (int)$venta['id_venta'] . '</td><td>' . htmlspecialchars($venta['cajero']) . '</td><td>' . htmlspecialchars(etiquetaMetodoPagoReporte($venta['metodo_pago'])) . '</td><td>' . (!empty($venta['id_arqueo']) ? 'Turno #' . (int)$venta['id_arqueo'] : 'Sin turno') . '</td><td>' . getMoneda() . number_format((float)$venta['total_venta'], 2) . '</td><td>' . htmlspecialchars(ucfirst($venta['estado'])) . '</td></tr>';
}
$html .= '</table>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('reporte_ventas_' . date('Y-m-d_His') . '.pdf', ['Attachment' => true]);
