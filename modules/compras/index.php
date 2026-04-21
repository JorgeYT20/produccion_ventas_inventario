<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($_SESSION['id_usuario']) || !tienePermiso('compras_ver_lista')) {
    echo "<div class='alert alert-danger'>No tienes permiso para acceder a esta sección.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$sql = "SELECT c.*, p.nombre_proveedor 
        FROM compras c
        LEFT JOIN proveedores p ON c.id_proveedor = p.id_proveedor
        ORDER BY c.fecha_compra DESC";
$resultado = $conexion->query($sql);
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="m-0"><i class="fas fa-truck"></i> Gestión de Compras</h2>
        <?php if (tienePermiso('compras_crear')): ?>
            <a href="crear_compra.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Orden de Compra
            </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?php echo $_SESSION['mensaje_tipo']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['mensaje']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); ?>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Proveedor</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultado && $resultado->num_rows > 0): ?>
                        <?php while($compra = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $compra['id_compra']; ?></td>
                            <td><?php echo htmlspecialchars($compra['nombre_proveedor']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($compra['fecha_compra'])); ?></td>
                            <td><?php echo getMoneda(); ?><?php echo number_format($compra['total_compra'], 2); ?></td>
                            <td>
                                <?php 
                                    $estado = $compra['estado'];
                                    $clase_badge = 'bg-secondary';
                                    if ($estado == 'solicitada') $clase_badge = 'bg-warning text-dark';
                                    if ($estado == 'recibida') $clase_badge = 'bg-success';
                                    if ($estado == 'pagada') $clase_badge = 'bg-info text-dark';
                                ?>
                                <span class="badge <?php echo $clase_badge; ?>"><?php echo ucfirst($estado); ?></span>
                            </td>
                            <td>
                                <?php if ($compra['estado'] == 'solicitada' && tienePermiso('compras_recibir')): ?>
                                    <a href="recibir_compra.php?id=<?php echo $compra['id_compra']; ?>" class="btn btn-sm btn-success confirm-receive-btn" title="Marcar como Recibida">
                                        <i class="fas fa-check"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No hay órdenes de compra registradas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmReceiveButtons = document.querySelectorAll('.confirm-receive-btn');
    
    confirmReceiveButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            const href = this.href;

            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción marcará la orden como recibida y actualizará el stock. ¡No se puede deshacer!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, ¡recibir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            })
        });
    });
});
</script>