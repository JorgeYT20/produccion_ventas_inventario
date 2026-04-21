<?php
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['id_usuario'])) {
    redirigir('/mi_sistema/modules/auth/login.php');
    exit;
}

// CAMBIO CRÍTICO: Si no tiene permiso para el Dashboard, mándalo a Productos
// No lo mandes al Login, porque causarás el bucle infinito.
if (!tienePermiso('dashboard_ver')) {
    redirigir('/mi_sistema/modules/productos/index.php');
    exit;
}

// --- Consultas para todos los paneles de resumen ---
// 1. Contar ventas completadas
$ventas_res = $conexion->query("SELECT COUNT(id_venta) as total FROM ventas WHERE estado = 'completada'");
$total_ventas = $ventas_res ? $ventas_res->fetch_assoc()['total'] : 0;

// 2. Contar compras recibidas
$compras_res = $conexion->query("SELECT COUNT(id_compra) as total FROM compras WHERE estado = 'recibida'");
$total_compras = $compras_res ? $compras_res->fetch_assoc()['total'] : 0;

// 3. Contar productos activos
$productos_res = $conexion->query("SELECT COUNT(id_producto) as total FROM productos WHERE activo = 1");
$total_productos = $productos_res ? $productos_res->fetch_assoc()['total'] : 0;

// 4. Contar productos con bajo stock
$productos_bajos_res = $conexion->query("SELECT COUNT(id_producto) as total FROM productos WHERE activo = 1 AND stock <= stock_minimo");
$total_productos_bajos = $productos_bajos_res ? $productos_bajos_res->fetch_assoc()['total'] : 0;

// --- Lógica para la gráfica de ventas ---
$sql_ventas_dias = "SELECT DATE(fecha_venta) as dia, SUM(total_venta) as total
                    FROM ventas
                    WHERE fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 5 DAY) AND estado = 'completada'
                    GROUP BY DATE(fecha_venta)
                    ORDER BY dia ASC";
$resultado_ventas = $conexion->query($sql_ventas_dias);
$dias_labels = [];
for ($i = 4; $i >= 0; $i--) {
    $fecha = date('Y-m-d', strtotime("-$i days"));
    $dias_labels[$fecha] = 0;
}
if ($resultado_ventas) {
    while ($fila = $resultado_ventas->fetch_assoc()) {
        $dias_labels[$fila['dia']] = $fila['total'];
    }
}
$labels_json = json_encode(array_keys($dias_labels));
$data_json = json_encode(array_values($dias_labels));

// --- Lógica para la fecha actual en español ---
// 1. Corregir la hora según tu ubicación (Ejemplo: América/Lima)
date_default_timezone_set('America/Lima'); 

// 2. Función para nombres en español
function fechaEnEspañol() {
    $dias = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];
    $meses = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
    
    return [
        'dia_nombre' => $dias[date('w')],
        'dia_num'    => date('d'),
        'mes_nombre' => $meses[date('n')],
        'año'        => date('Y')
    ];
}

$fecha = fechaEnEspañol();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">

    <div class="row align-items-center mb-4 mt-4">
        <div class="col-md-8">
            <h1 class="fw-bold text-dark mb-1">¡Hola, <?php echo htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Johnny'); ?>! 👋</h1>
            <p class="text-muted">Esto es lo que está pasando hoy en Licorería<strong> PUNTO DE ENCUENTRO</strong>.</p>
        </div>

        <div class="col-md-4 text-md-end">
            <div class="d-inline-block bg-white shadow-sm rounded-4 p-3 border-start border-primary border-4">
                <div class="d-flex align-items-center text-start">
                    <div class="me-3 text-center">
                        <span class="d-block fw-bold fs-2 text-primary" style="line-height: 1;">
                            <?php echo $fecha['dia_num']; ?>
                        </span>
                        <span class="text-uppercase fw-bold text-muted small">
                            <?php echo substr($fecha['mes_nombre'], 0, 3); ?>
                        </span>
                    </div>
                    <div class="border-start ps-3">
                        <span class="d-block fw-bold text-dark text-capitalize">
                            <?php echo $fecha['dia_nombre']; ?>
                        </span>
                        <span class="text-muted small">Año <?php echo $fecha['año']; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card text-white bg-success h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-3 fw-bold"><?php echo $total_ventas; ?></div>
                        <div>Ventas Realizadas</div>
                    </div>
                    <i class="fas fa-shopping-cart fa-3x opacity-50"></i>
                </div>
                <a class="card-footer text-white d-flex justify-content-between" href="/mi_sistema/modules/ventas/listado_ventas.php">
                    <span>Ver Detalles</span> <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card text-white bg-danger h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-3 fw-bold"><?php echo $total_compras; ?></div>
                        <div>Compras Realizadas</div>
                    </div>
                    <i class="fas fa-truck fa-3x opacity-50"></i>
                </div>
                <a class="card-footer text-white d-flex justify-content-between" href="/mi_sistema/modules/compras/index.php">
                    <span>Ver Detalles</span> <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card text-white bg-warning h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-3 fw-bold"><?php echo $total_productos; ?></div>
                        <div>Productos en Inventario</div>
                    </div>
                    <i class="fas fa-box-open fa-3x opacity-50"></i>
                </div>
                <a class="card-footer text-white d-flex justify-content-between" href="/mi_sistema/modules/productos/index.php">
                    <span>Ver Detalles</span> <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card text-white bg-info h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-3 fw-bold"><?php echo $total_productos_bajos; ?></div>
                        <div>Productos Bajos de Stock</div>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
                </div>
                <a class="card-footer text-white d-flex justify-content-between" href="/mi_sistema/modules/reportes/stock_bajo.php">
                    <span>Ver Detalles</span>
                    <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Ventas de los Últimos 5 Días
                </div>
                <div class="card-body">
                    <canvas id="graficoVentas" width="100%" height="30"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const labelsVentas = <?php echo $labels_json; ?>;
    const dataVentas = <?php echo $data_json; ?>;
</script>

<?php
echo '<script src="/mi_sistema/assets/js/dashboard.js"></script>';
require_once __DIR__ . '/includes/footer.php';
?>