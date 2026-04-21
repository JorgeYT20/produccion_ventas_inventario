<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';
if (!isset($_SESSION['id_usuario'])) { redirigir('/mi_sistema/modules/auth/login.php'); }
if (!tienePermiso('categorias_ver_lista')) {
    echo "<div class='alert alert-danger'>No tienes permiso para acceder a esta sección.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}
$sql = "SELECT * FROM categorias ORDER BY nombre ASC";
$resultado = $conexion->query($sql);
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="m-0"><i class="fas fa-tags"></i> Gestión de Categorías</h2>
        <?php if (tienePermiso('categorias_crear_editar')): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoriaModal">
            <i class="fas fa-plus"></i> Agregar Categoría
        </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($cat = $resultado->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cat['nombre']); ?></td>
                    <td>
                        <?php if ($cat['activo']): ?>
                            <span class="badge bg-success">Activo</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (tienePermiso('categorias_crear_editar')): ?>
                        <button type="button" class="btn btn-sm btn-warning edit-cat-btn" data-bs-toggle="modal" data-bs-target="#categoriaModal" data-id="<?php echo $cat['id_categoria']; ?>" data-nombre="<?php echo htmlspecialchars($cat['nombre']); ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php endif; ?>
                        <?php if (tienePermiso('categorias_cambiar_estado')): ?>
                            <?php if ($cat['activo']): ?>
                                <a href="cambiar_estado.php?id=<?php echo $cat['id_categoria']; ?>&estado=0" class="btn btn-sm btn-danger"><i class="fas fa-ban"></i> Desactivar</a>
                            <?php else: ?>
                                <a href="cambiar_estado.php?id=<?php echo $cat['id_categoria']; ?>&estado=1" class="btn btn-sm btn-success"><i class="fas fa-check"></i> Activar</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="categoriaModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="categoriaModalLabel">Agregar Categoría</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="guardar.php" method="POST">
        <div class="modal-body">
            <input type="hidden" name="id_categoria" id="id_categoria">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre de la Categoría</label>
                <input type="text" class="form-control" name="nombre" id="nombre_categoria" required>
            </div>
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
echo '<script src="/mi_sistema/assets/js/categorias.js"></script>';
require_once __DIR__ . '/../../includes/footer.php'; 
?>