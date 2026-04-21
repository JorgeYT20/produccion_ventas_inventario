<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($_SESSION['id_usuario']) || !tienePermiso('usuarios_ver_lista')) {
    echo "<div class='alert alert-danger'>No tienes permiso para acceder.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Obtener la lista de roles para el formulario
$roles_res = $conexion->query("SELECT * FROM roles ORDER BY nombre_rol ASC");

// Consulta corregida para incluir id_rol
$sql_usuarios = "SELECT u.id_usuario, u.nombre_completo, u.username, u.activo, u.id_rol, r.nombre_rol 
                 FROM usuarios u 
                 JOIN roles r ON u.id_rol = r.id_rol 
                 ORDER BY u.nombre_completo ASC";
$usuarios_res = $conexion->query($sql_usuarios);
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="m-0"><i class="fas fa-users-cog"></i> Gestión de Usuarios</h2>
        <?php if (tienePermiso('usuarios_crear_editar')): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#usuarioModal">
                <i class="fas fa-plus"></i> Agregar Usuario
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre Completo</th>
                        <th>Username</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($usuarios_res && $usuarios_res->num_rows > 0): ?>
                        <?php while($usuario = $usuarios_res->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['nombre_completo']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['username']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['nombre_rol']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $usuario['activo'] ? 'success' : 'danger'; ?>">
                                    <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if (tienePermiso('usuarios_crear_editar')): ?>
                                    <button type="button" class="btn btn-sm btn-warning edit-user-btn" 
                                            data-bs-toggle="modal" data-bs-target="#usuarioModal"
                                            data-id="<?php echo $usuario['id_usuario']; ?>"
                                            data-nombre="<?php echo htmlspecialchars($usuario['nombre_completo']); ?>"
                                            data-username="<?php echo htmlspecialchars($usuario['username']); ?>"
                                            data-rol="<?php echo $usuario['id_rol']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if (tienePermiso('usuarios_cambiar_estado') && $_SESSION['id_usuario'] != $usuario['id_usuario']): ?>
                                    <?php if ($usuario['activo']): ?>
                                        <a href="cambiar_estado.php?id=<?php echo $usuario['id_usuario']; ?>&estado=0" class="btn btn-sm btn-danger" title="Desactivar"><i class="fas fa-ban"></i></a>
                                    <?php else: ?>
                                        <a href="cambiar_estado.php?id=<?php echo $usuario['id_usuario']; ?>&estado=1" class="btn btn-sm btn-success" title="Activar"><i class="fas fa-check"></i></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="usuarioModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="usuarioModalLabel">Agregar Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="guardar.php" method="POST">
        <div class="modal-body">
            <input type="hidden" name="id_usuario" id="id_usuario">
            <div class="mb-3"><label for="nombre_completo" class="form-label">Nombre Completo</label><input type="text" class="form-control" name="nombre_completo" id="nombre_completo" required></div>
            <div class="mb-3"><label for="username" class="form-label">Username</label><input type="text" class="form-control" name="username" id="username" required></div>
            <div class="mb-3"><label for="id_rol" class="form-label">Rol</label>
                <select name="id_rol" id="id_rol" class="form-select" required>
                    <?php if ($roles_res) { mysqli_data_seek($roles_res, 0); } ?>
                    <?php while($rol = $roles_res->fetch_assoc()): ?>
                        <option value="<?php echo $rol['id_rol']; ?>"><?php echo htmlspecialchars($rol['nombre_rol']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3"><label for="password" class="form-label">Contraseña</label><input type="password" class="form-control" name="password" id="password"><small class="form-text text-muted">Dejar en blanco para no cambiar la contraseña al editar.</small></div>
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
echo '<script src="/mi_sistema/assets/js/usuarios.js"></script>';
require_once __DIR__ . '/../../includes/footer.php'; 
?>