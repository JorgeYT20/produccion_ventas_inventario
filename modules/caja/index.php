<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($_SESSION['id_usuario']) || !tienePermiso('caja_gestionar')) {
    echo "<div class='alert alert-danger'>No tienes permiso para acceder a esta sección.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}
$id_usuario_actual = $_SESSION['id_usuario'];

// Verificar si el usuario tiene un turno de caja abierto
$stmt_turno = $conexion->prepare("SELECT * FROM turnos_caja WHERE id_usuario = ? AND estado = 'abierto'");
$stmt_turno->bind_param("i", $id_usuario_actual);
$stmt_turno->execute();
$turno_abierto = $stmt_turno->get_result()->fetch_assoc();
$stmt_turno->close();

?>

<div class="container-fluid">
    <h1 class="mt-4"><i class="fas fa-calculator"></i> Arqueo de Caja</h1>
    
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?php echo $_SESSION['mensaje_tipo']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['mensaje']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); ?>
    <?php endif; ?>

    <?php if (!$turno_abierto): // Si NO hay un turno abierto ?>
    
        <div class="card shadow-sm mt-4">
            <div class="card-header">
                <h4>Iniciar Turno</h4>
            </div>
            <div class="card-body">
                <p>No tienes un turno de caja activo. Ingresa el monto inicial para comenzar.</p>
                <form action="abrir_caja.php" method="POST">
                    <div class="mb-3">
                        <label for="monto_inicial" class="form-label">Monto Inicial (Fondo de caja)</label>
                        <div class="input-group">
                            <span class="input-group-text"><?php echo getMoneda(); ?></span>
                            <input type="number" step="0.01" class="form-control" name="monto_inicial" id="monto_inicial" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-play-circle"></i> Abrir Caja</button>
                </form>
            </div>
        </div>

    <?php else: // Si SÍ hay un turno abierto ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h4>Turno Activo</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>Cajero:</strong> <?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></p>
                        <p><strong>Fecha de Apertura:</strong> <?php echo date('d/m/Y H:i:s', strtotime($turno_abierto['fecha_apertura'])); ?></p>
                        <p><strong>Monto Inicial:</strong> <?php echo getMoneda(); ?><?php echo number_format($turno_abierto['monto_inicial'], 2); ?></p>
                        <hr>
                        <h5>Cerrar Turno</h5>
                        <form action="cerrar_caja.php" method="POST">
                            <input type="hidden" name="id_turno" value="<?php echo $turno_abierto['id_turno']; ?>">
                            <div class="mb-3">
                                <label for="monto_final_real" class="form-label">Monto Final Contado (Dinero en caja)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><?php echo getMoneda(); ?></span>
                                    <input type="number" step="0.01" class="form-control" name="monto_final_real" id="monto_final_real" required>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger"><i class="fas fa-stop-circle"></i> Cerrar Caja y Realizar Arqueo</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h4>Registrar Movimiento de Caja</h4>
                    </div>
                    <div class="card-body">
                        <form action="registrar_movimiento.php" method="POST">
                            <input type="hidden" name="id_turno" value="<?php echo $turno_abierto['id_turno']; ?>">
                            <div class="mb-3">
                                <label for="tipo_movimiento" class="form-label">Tipo de Movimiento</label>
                                <select name="tipo_movimiento" class="form-select" required>
                                    <option value="ingreso">Ingreso (Ej: Aporte de cambio)</option>
                                    <option value="egreso">Egreso (Ej: Pago a delivery)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="monto" class="form-label">Monto</label>
                                <input type="number" step="0.01" class="form-control" name="monto" required>
                            </div>
                             <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <input type="text" class="form-control" name="descripcion" required>
                            </div>
                            <button type="submit" class="btn btn-info text-white"><i class="fas fa-save"></i> Registrar Movimiento</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>