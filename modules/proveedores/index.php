<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($_SESSION['id_usuario'])) { redirigir('/mi_sistema/modules/auth/login.php'); }
if (!tienePermiso('proveedores_ver_lista')) {
    echo "<div class='alert alert-danger'>No tienes permiso para acceder a esta sección.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Filtro para ver activos o inactivos
$ver_inactivos = isset($_GET['ver']) && $_GET['ver'] == 'inactivos';
$filtro_activo = $ver_inactivos ? 0 : 1;

$stmt = $conexion->prepare("SELECT * FROM proveedores WHERE activo = ? ORDER BY nombre_proveedor ASC");
$stmt->bind_param("i", $filtro_activo);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado === false) {
    die("Error en la consulta de proveedores: " . $stmt->error);
}
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="m-0"><i class="fas fa-truck-loading"></i> Gestión de Proveedores</h2>
        <div>
            <?php if ($ver_inactivos): ?>
                <a href="index.php" class="btn btn-info">Ver Activos</a>
            <?php else: ?>
                <a href="index.php?ver=inactivos" class="btn btn-secondary">Ver Inactivos</a>
            <?php endif; ?>
            <?php if (tienePermiso('proveedores_crear_editar')): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#proveedorModal">
                    <i class="fas fa-plus"></i> Agregar Proveedor
                </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>RUC</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultado->num_rows > 0): ?>
                        <?php while($proveedor = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($proveedor['nombre_proveedor']); ?></td>
                            <td><?php echo htmlspecialchars($proveedor['ruc']); ?></td>
                            <td><?php echo htmlspecialchars($proveedor['telefono']); ?></td>
                            <td><?php echo htmlspecialchars($proveedor['email']); ?></td>
                            <td>
                                <?php if ($proveedor['activo']): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (tienePermiso('proveedores_crear_editar')): ?>
                                    <button type="button" class="btn btn-sm btn-warning edit-proveedor-btn" 
                                            data-bs-toggle="modal" data-bs-target="#proveedorModal"
                                            data-id="<?php echo $proveedor['id_proveedor']; ?>"
                                            data-nombre="<?php echo htmlspecialchars($proveedor['nombre_proveedor']); ?>"
                                            data-ruc="<?php echo htmlspecialchars($proveedor['ruc']); ?>"
                                            data-telefono="<?php echo htmlspecialchars($proveedor['telefono']); ?>"
                                            data-email="<?php echo htmlspecialchars($proveedor['email']); ?>"
                                            data-direccion="<?php echo htmlspecialchars($proveedor['direccion']); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if (tienePermiso('proveedores_cambiar_estado')): ?>
                                    <?php if ($proveedor['activo']): ?>
                                        <a href="cambiar_estado.php?id=<?php echo $proveedor['id_proveedor']; ?>&estado=0" class="btn btn-sm btn-danger" title="Desactivar"><i class="fas fa-ban"></i></a>
                                    <?php else: ?>
                                        <a href="cambiar_estado.php?id=<?php echo $proveedor['id_proveedor']; ?>&estado=1" class="btn btn-sm btn-success" title="Activar"><i class="fas fa-check"></i></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No hay proveedores <?php echo $ver_inactivos ? 'inactivos' : 'activos'; ?>.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="proveedorModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="proveedorModalLabel">Agregar Nuevo Proveedor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form action="guardar.php" method="POST">
            <input type="hidden" name="id_proveedor" id="id_proveedor">
            <div class="mb-3">
                <label for="nombre_proveedor" class="form-label">Nombre del Proveedor</label>
                <input type="text" class="form-control" name="nombre_proveedor" id="nombre_proveedor" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="ruc" class="form-label">RUC / Documento</label>
                    <input type="text" class="form-control" name="ruc" id="ruc">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="telefono" class="form-label">Teléfono</label>
                    <input type="text" class="form-control" name="telefono" id="telefono">
                </div>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" name="email" id="email">
            </div>
            <div class="mb-3">
                <label for="direccion" class="form-label">Dirección</label>
                <textarea class="form-control" name="direccion" id="direccion" rows="2"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php 
echo '<script src="/mi_sistema/assets/js/proveedores.js"></script>';
require_once __DIR__ . '/../../includes/footer.php'; 
?>