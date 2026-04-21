<?php
require_once __DIR__ . '/../../config/database.php';

// Seguridad: Solo usuarios logueados pueden acceder
if (!isset($_SESSION['id_usuario'])) {
    redirigir('/mi_sistema/modules/auth/login.php');
}

// Seguridad: Asumiremos que solo el admin puede borrar ventas
// (Puedes crear un permiso 'ventas_eliminar' si quieres más control)
if ($_SESSION['id_rol'] != 1) {
    $_SESSION['mensaje'] = 'No tienes permiso para realizar esta acción.';
    $_SESSION['mensaje_tipo'] = 'danger';
    redirigir('index.php');
}

if (!isset($_GET['id'])) {
    $_SESSION['mensaje'] = 'No se especificó un ID de venta.';
    $_SESSION['mensaje_tipo'] = 'warning';
    redirigir('/mi_sistema/index.php'); // Redirige al dashboard o a una página de ventas
}

$id_venta = (int)$_GET['id'];

// Primero, nos aseguramos de que no haya detalles (doble seguridad)
$stmt_check = $conexion->prepare("SELECT COUNT(*) as total FROM detalle_ventas WHERE id_venta = ?");
$stmt_check->bind_param("i", $id_venta);
$stmt_check->execute();
$detalle_count = $stmt_check->get_result()->fetch_assoc()['total'];
$stmt_check->close();

if ($detalle_count > 0) {
    $_SESSION['mensaje'] = 'No se puede eliminar la venta porque tiene productos en su detalle. Contacte al administrador.';
    $_SESSION['mensaje_tipo'] = 'danger';
    redirigir('/mi_sistema/index.php');
}

// Proceder a eliminar la venta del encabezado
$stmt_delete = $conexion->prepare("DELETE FROM ventas WHERE id_venta = ?");
$stmt_delete->bind_param("i", $id_venta);

if ($stmt_delete->execute()) {
    $_SESSION['mensaje'] = "La venta fallida #{$id_venta} ha sido eliminada correctamente.";
    $_SESSION['mensaje_tipo'] = 'success';
} else {
    $_SESSION['mensaje'] = "Error al eliminar la venta.";
    $_SESSION['mensaje_tipo'] = 'danger';
}
$stmt_delete->close();

// Redirigir a una página relevante, como el dashboard o un listado de ventas
redirigir('/mi_sistema/index.php');

?>