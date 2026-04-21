<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../dompdf/autoload.inc.php'; // Cargar Dompdf
use Dompdf\Dompdf;

if (!isset($_SESSION['id_usuario']) || !tienePermiso('reportes_ver_inventario')) { exit; }

// Obtener datos (mismas consultas que en el de Excel)
$sql_productos = "SELECT nombre, stock, stock_minimo, precio_compra, precio_venta FROM productos ORDER BY nombre ASC";
$productos = $conexion->query($sql_productos);
$sql_valor = "SELECT SUM(stock * precio_compra) as valor_total FROM productos";
$resultado_valor = $conexion->query($sql_valor);
$valor_total = ($resultado_valor) ? $resultado_valor->fetch_assoc()['valor_total'] : 0;

// Construir el HTML para el PDF
$html = '<h1>Reporte de Inventario</h1>';
$html .= '<p>Valor Total del Inventario (a precio de costo): <strong>' . getMoneda() . number_format($valor_total ?? 0, 2) . '</strong></p>';
$html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%">';
$html .= '<thead><tr><th>Producto</th><th>Stock Actual</th><th>Stock Mínimo</th><th>Precio Costo</th><th>Precio Venta</th></tr></thead>';
$html .= '<tbody>';
if ($productos && $productos->num_rows > 0) {
    while($p = $productos->fetch_assoc()){
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($p['nombre']) . '</td>';
        $html .= '<td>' . $p['stock'] . '</td>';
        $html .= '<td>' . $p['stock_minimo'] . '</td>';
        $html .= '<td>' . getMoneda() . number_format($p['precio_compra'], 2) . '</td>';
        $html .= '<td>' . getMoneda() . number_format($p['precio_venta'], 2) . '</td>';
        $html .= '</tr>';
    }
} else {
    $html .= '<tr><td colspan="5">No hay productos registrados.</td></tr>';
}
$html .= '</tbody></table>';

// Instanciar Dompdf y generar el PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("reporte_inventario_" . date('Y-m-d') . ".pdf", ["Attachment" => true]);
?>