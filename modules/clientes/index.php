<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($_SESSION['id_usuario']) || !tienePermiso('clientes_ver_lista')) {
    echo "<div class='alert alert-danger'>No tienes permiso para acceder.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Añadimos una columna 'activo' a la tabla si no existe
$check_column = $conexion->query("SHOW COLUMNS FROM `clientes` LIKE 'activo'");
if ($check_column->num_rows == 0) {
    $conexion->query("ALTER TABLE `clientes` ADD `activo` BOOLEAN NOT NULL DEFAULT TRUE AFTER `direccion`");
}

$ver_inactivos = isset($_GET['ver']) && $_GET['ver'] == 'inactivos';
$filtro_activo = $ver_inactivos ? 0 : 1;
$stmt = $conexion->prepare("SELECT * FROM clientes WHERE activo = ? ORDER BY nombre_cliente ASC");
$stmt->bind_param("i", $filtro_activo);
$stmt->execute();
$resultado = $stmt->get_result();
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="m-0"><i class="fas fa-users"></i> Gestión de Clientes</h2>
        <div>
            <?php if ($ver_inactivos): ?>
                <a href="index.php" class="btn btn-info">Ver Activos</a>
            <?php else: ?>
                <a href="index.php?ver=inactivos" class="btn btn-secondary">Ver Inactivos</a>
            <?php endif; ?>
            <?php if (tienePermiso('clientes_crear_editar')): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#clienteModal">
                    <i class="fas fa-plus"></i> Agregar Cliente
                </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Nombre</th>
                    <th>Documento</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($cliente = $resultado->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cliente['nombre_cliente']); ?></td>
                    <td><?php echo htmlspecialchars($cliente['documento_identidad']); ?></td>
                    <td><?php echo htmlspecialchars($cliente['telefono']); ?></td>
                    <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                    <td>
                        <span class="badge bg-<?php echo $cliente['activo'] ? 'success' : 'danger'; ?>">
                            <?php echo $cliente['activo'] ? 'Activo' : 'Inactivo'; ?>
                        </span>
                    </td>
                    <td>
                        <?php if (tienePermiso('clientes_crear_editar')): ?>
                            <button type="button" class="btn btn-sm btn-warning edit-cliente-btn" 
                                    data-bs-toggle="modal" data-bs-target="#clienteModal"
                                    data-id="<?php echo $cliente['id_cliente']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($cliente['nombre_cliente']); ?>"
                                    data-documento="<?php echo htmlspecialchars($cliente['documento_identidad']); ?>"
                                    data-telefono="<?php echo htmlspecialchars($cliente['telefono']); ?>"
                                    data-email="<?php echo htmlspecialchars($cliente['email']); ?>"
                                    data-direccion="<?php echo htmlspecialchars($cliente['direccion']); ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                        <?php endif; ?>
                        <?php if (tienePermiso('clientes_cambiar_estado')): ?>
                            <?php if ($cliente['activo']): ?>
                                <a href="cambiar_estado.php?id=<?php echo $cliente['id_cliente']; ?>&estado=0" class="btn btn-sm btn-danger" title="Desactivar"><i class="fas fa-ban"></i></a>
                            <?php else: ?>
                                <a href="cambiar_estado.php?id=<?php echo $cliente['id_cliente']; ?>&estado=1" class="btn btn-sm btn-success" title="Activar"><i class="fas fa-check"></i></a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="clienteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clienteModalLabel">Agregar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="guardar.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_cliente" id="id_cliente">
                    <div class="mb-3"><label for="nombre_cliente" class="form-label">Nombre</label><input type="text" class="form-control" name="nombre_cliente" id="nombre_cliente" required></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="documento_identidad" class="form-label">Documento</label><input type="text" class="form-control" name="documento_identidad" id="documento_identidad"></div>
                        <div class="col-md-6 mb-3"><label for="telefono" class="form-label">Teléfono</label><input type="text" class="form-control" name="telefono" id="telefono"></div>
                    </div>
                    <div class="mb-3"><label for="email" class="form-label">Email</label><input type="email" class="form-control" name="email" id="email"></div>
                    <div class="mb-3"><label for="direccion" class="form-label">Dirección</label><textarea class="form-control" name="direccion" id="direccion" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php 
echo '<script src="/mi_sistema/assets/js/clientes.js"></script>';
require_once __DIR__ . '/../../includes/footer.php'; 
?>