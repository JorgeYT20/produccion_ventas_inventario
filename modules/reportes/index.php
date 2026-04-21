<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($_SESSION['id_usuario'])) {
    redirigir('/mi_sistema/modules/auth/login.php');
}

if (!tienePermiso('reportes_ver_ventas') && !tienePermiso('reportes_ver_inventario')) {
    echo "<div class='alert alert-danger'>No tienes permiso para acceder a esta sección.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}
?>

<div class="container-fluid">
    <h1 class="mt-4"><i class="fas fa-chart-line"></i> Módulo de Reportes</h1>
    <p>Selecciona un reporte para visualizar los datos consolidados de tu negocio.</p>

    <div class="row">
        <?php if (tienePermiso('reportes_ver_ventas')): ?>
        <div class="col-md-4">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-dollar-sign fa-3x text-success mb-3"></i>
                    <h5 class="card-title">Reporte de Ventas</h5>
                    <p class="card-text">Analiza los ingresos, productos más vendidos y rendimiento por fechas.</p>
                    <a href="ventas.php" class="btn btn-primary">Ir al Reporte</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (tienePermiso('reportes_ver_inventario')): ?>
        <div class="col-md-4">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-boxes fa-3x text-warning mb-3"></i>
                    <h5 class="card-title">Reporte de Inventario</h5>
                    <p class="card-text">Consulta el stock actual y el valor total de tus activos.</p>
                    <a href="inventario.php" class="btn btn-primary">Ir al Reporte</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>