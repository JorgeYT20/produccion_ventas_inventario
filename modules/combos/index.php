<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers_combos.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($_SESSION['id_usuario'])) {
    redirigir('/mi_sistema/modules/auth/login.php');
    exit;
}

$estructuraCombosOk = tablaCombosDisponible($conexion);
$combos = obtenerCombosActivos($conexion);
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="m-0"><i class="fas fa-layer-group"></i> Gestión de Combos</h2>
        <a href="crear.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo Combo
        </a>
    </div>
    <div class="card-body">
        <?php if (!$estructuraCombosOk): ?>
            <div class="alert alert-warning mb-0">Debes ejecutar el script <code>combos.sql</code> antes de usar este módulo.</div>
        <?php elseif (empty($combos)): ?>
            <div class="alert alert-info mb-0">Aún no hay combos creados.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Productos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($combos as $combo): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($combo['nombre']); ?></td>
                                <td><?php echo getMoneda() . number_format($combo['precio'], 2); ?></td>
                                <td>
                                    <?php foreach ($combo['productos'] as $producto): ?>
                                        <span class="badge bg-light text-dark border me-1 mb-1">
                                            <?php echo htmlspecialchars($producto['nombre']); ?> x<?php echo (int)$producto['cantidad']; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <a href="editar.php?id=<?php echo (int)$combo['id_combo']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="eliminar.php?id=<?php echo (int)$combo['id_combo']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este combo?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
