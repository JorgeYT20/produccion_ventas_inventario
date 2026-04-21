<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($_SESSION['id_usuario']) || !tienePermiso('productos_ver_detalle')) {
    echo "<div class='alert alert-danger'>No tienes permiso para acceder a esta sección.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

if (!isset($_GET['id'])) {
    redirigir('index.php');
}
$id_producto = (int)$_GET['id'];

// Obtener información del producto
$stmt_prod = $conexion->prepare("SELECT nombre, stock FROM productos WHERE id_producto = ?");
$stmt_prod->bind_param("i", $id_producto);
$stmt_prod->execute();
$producto = $stmt_prod->get_result()->fetch_assoc();
$stmt_prod->close();

if (!$producto) {
    echo "<div class='alert alert-danger'>Producto no encontrado.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Obtener movimientos del producto (Kardex)
$stmt_mov = $conexion->prepare("SELECT m.*, u.nombre_completo 
                                FROM movimientos_inventario m
                                JOIN usuarios u ON m.id_usuario = u.id_usuario
                                WHERE m.id_producto = ? 
                                ORDER BY m.fecha DESC");
$stmt_mov->bind_param("i", $id_producto);
$stmt_mov->execute();
$movimientos = $stmt_mov->get_result();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h2 class="m-0"><i class="fas fa-history"></i> Kardex del Producto</h2>
            <h4><?php echo htmlspecialchars($producto['nombre']); ?> - Stock Actual: <?php echo $producto['stock']; ?></h4>
        </div>
        <a href="index.php" class="btn btn-secondary">Volver al Listado</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo de Movimiento</th>
                        <th>Cantidad</th>
                        <th>Stock Anterior</th>
                        <th>Stock Nuevo</th>
                        <th>Usuario</th>
                        <th>Referencia ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($movimientos->num_rows > 0): ?>
                        <?php while($mov = $movimientos->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($mov['fecha'])); ?></td>
                            <td><span class="badge bg-info text-dark"><?php echo ucfirst($mov['tipo_movimiento']); ?></span></td>
                            <td><strong><?php echo $mov['cantidad']; ?></strong></td>
                            <td><?php echo $mov['stock_anterior']; ?></td>
                            <td><?php echo $mov['stock_nuevo']; ?></td>
                            <td><?php echo htmlspecialchars($mov['nombre_completo']); ?></td>
                            <td><?php echo $mov['referencia_id']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay movimientos registrados para este producto.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>