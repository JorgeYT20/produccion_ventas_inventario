<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';
if (!isset($_SESSION['id_usuario'])) { redirigir('/mi_sistema/modules/auth/login.php'); }

// Obtener datos del usuario actual
$stmt = $conexion->prepare("SELECT nombre_completo, username, moneda FROM usuarios WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION['id_usuario']);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
?>
<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-user-edit"></i> Mi Perfil</h4>
    </div>
    <div class="card-body">
        <form action="guardar.php" method="POST">
            <div class="mb-3">
                <label class="form-label">Nombre Completo</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario['nombre_completo']); ?>" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Nombre de Usuario</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario['username']); ?>" disabled>
            </div>
            <div class="mb-3">
                <label for="moneda" class="form-label">Moneda Preferida</label>
                <select name="moneda" id="moneda" class="form-select">
                    <option value="$" <?php echo ($usuario['moneda'] == '$') ? 'selected' : ''; ?>>Dólar ($)</option>
                    <option value="€" <?php echo ($usuario['moneda'] == '€') ? 'selected' : ''; ?>>Euro (€)</option>
                    <option value="S/" <?php echo ($usuario['moneda'] == 'S/') ? 'selected' : ''; ?>>Soles (S/)</option>
                    </select>
            </div>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>