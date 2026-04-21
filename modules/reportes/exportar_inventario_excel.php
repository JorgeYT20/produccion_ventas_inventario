<?php
require_once __DIR__ . '/../../config/database.php';
if (!isset($_SESSION['id_usuario']) || !tienePermiso('reportes_ver_inventario')) { exit; }

// Obtener lista de productos y stock
$sql_productos = "SELECT nombre, stock, stock_minimo, precio_compra, precio_venta FROM productos ORDER BY nombre ASC";
$productos = $conexion->query($sql_productos);

// Calcular valor total del inventario
$sql_valor = "SELECT SUM(stock * precio_compra) as valor_total FROM productos";
$resultado_valor = $conexion->query($sql_valor);
$valor_total = ($resultado_valor) ? $resultado_valor->fetch_assoc()['valor_total'] : 0;

// Encabezados para forzar la descarga en formato Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=reporte_inventario_" . date('Y-m-d') . ".xls");
?>
<h3>Reporte de Inventario</h3>
<p>Valor Total del Inventario (a precio de costo): <strong><?php echo getMoneda() . number_format($valor_total ?? 0, 2); ?></strong></p>
<table border="1">
    <thead>
        <tr>
            <th>Producto</th>
            <th>Stock Actual</th>
            <th>Stock Mínimo</th>
            <th>Precio Costo</th>
            <th>Precio Venta</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($productos && $productos->num_rows > 0): ?>
            <?php while($p = $productos->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                    <td><?php echo $p['stock']; ?></td>
                    <td><?php echo $p['stock_minimo']; ?></td>
                    <td><?php echo getMoneda() . number_format($p['precio_compra'], 2); ?></td>
                    <td><?php echo getMoneda() . number_format($p['precio_venta'], 2); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5">No hay productos registrados.</td></tr>
        <?php endif; ?>
    </tbody>
</table>