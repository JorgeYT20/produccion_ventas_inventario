<?php
require_once __DIR__ . '/../../config/database.php';

// Proteger
if (!isset($_SESSION['id_usuario'])) {
    redirigir('/mi_sistema/modules/auth/login.php');
}

// VERIFICACIÓN DE PERMISO PARA ELIMINAR
if (!tienePermiso('productos_eliminar')) {
    $_SESSION['mensaje'] = 'No tienes permiso para realizar esta acción.';
    $_SESSION['mensaje_tipo'] = 'danger';
    redirigir('index.php');
}

if (isset($_GET['id'])) {
    $id_producto = (int)$_GET['id'];
    
    $stmt = $conexion->prepare("DELETE FROM productos WHERE id_producto = ?");
    $stmt->bind_param("i", $id_producto);

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = 'Producto eliminado correctamente.';
        $_SESSION['mensaje_tipo'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al eliminar el producto. Es posible que esté asociado a otros registros.';
        $_SESSION['mensaje_tipo'] = 'danger';
    }
    
    $stmt->close();
    $conexion->close();
    redirigir('index.php');
} else {
    redirigir('index.php');
}