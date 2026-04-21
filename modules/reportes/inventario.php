<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($_SESSION['id_usuario']) || !tienePermiso('reportes_ver_inventario')) {
    echo "<div class='alert alert-danger'>No tienes permiso para acceder.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// 1. Obtener lista de productos y stock
$sql_productos = "SELECT nombre, stock, stock_minimo, precio_compra, precio_venta FROM productos ORDER BY nombre ASC";
$productos = $conexion->query($sql_productos);

// 2. Calcular valor total del inventario
$sql_valor = "SELECT SUM(stock * precio_compra) as valor_total FROM productos";
$resultado_valor = $conexion->query($sql_valor);
$valor_total = ($resultado_valor) ? $resultado_valor->fetch_assoc()['valor_total'] : 0;
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="mt-4"><i class="fas fa-boxes"></i> Reporte de Inventario</h1>
        <a href="index.php" class="btn btn-secondary">Volver al Menú de Reportes</a>
    </div>

    <div class="card shadow-sm my-4">
        <div class="card-body d-flex justify-content-start">
            <button onclick="window.print();" class="btn btn-info text-white me-2"><i class="fas fa-print"></i> Imprimir</button>
            <a href="exportar_inventario_excel.php" class="btn btn-success me-2"><i class="fas fa-file-excel"></i> Exportar a Excel</a>
            <a href="exportar_inventario_pdf.php" class="btn btn-danger"><i class="fas fa-file-pdf"></i> Exportar a PDF</a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body text-center">
            <h3>Valor Total del Inventario (a precio de costo): 
                <span class="text-success"><?php echo getMoneda(); ?><?php echo number_format($valor_total ?? 0, 2); ?></span>
            </h3>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h4>Estado Actual del Stock</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
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
                                <?php $is_low_stock = $p['stock'] <= $p['stock_minimo']; ?>
                                <tr class="<?php echo $is_low_stock ? 'table-danger' : ''; ?>">
                                    <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                                    <td><strong><?php echo $p['stock']; ?></strong></td>
                                    <td><?php echo $p['stock_minimo']; ?></td>
                                    <td><?php echo getMoneda(); ?><?php echo number_format($p['precio_compra'], 2); ?></td>
                                    <td><?php echo getMoneda(); ?><?php echo number_format($p['precio_venta'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No hay productos registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>