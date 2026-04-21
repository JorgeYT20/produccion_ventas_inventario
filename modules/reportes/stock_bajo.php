<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($_SESSION['id_usuario']) || !tienePermiso('reportes_ver_stock_bajo')) {
    echo "<div class='alert alert-danger'>No tienes permiso para acceder.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Consulta para obtener productos donde el stock es menor o igual al stock mínimo
$sql_productos = "SELECT nombre, stock, stock_minimo, precio_compra 
                  FROM productos 
                  WHERE activo = 1 AND stock <= stock_minimo 
                  ORDER BY stock ASC";
$productos = $conexion->query($sql_productos);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="mt-4"><i class="fas fa-exclamation-triangle text-warning"></i> Reporte de Productos con Bajo Stock</h1>
        <a href="/mi_sistema/index.php" class="btn btn-secondary">Volver al Dashboard</a>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-header">
            <h4>Productos que requieren reabastecimiento</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Producto</th>
                            <th>Stock Actual</th>
                            <th>Stock Mínimo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($productos && $productos->num_rows > 0): ?>
                            <?php while($p = $productos->fetch_assoc()): ?>
                                <tr class="table-danger">
                                    <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                                    <td><strong><?php echo $p['stock']; ?></strong></td>
                                    <td><?php echo $p['stock_minimo']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center">¡Felicidades! No hay productos con bajo stock.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>