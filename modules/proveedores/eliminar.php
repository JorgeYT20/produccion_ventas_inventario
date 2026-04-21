<?php
require_once __DIR__ . '/../../config/database.php';

// Proteger
if (!isset($_SESSION['id_usuario'])) {
    redirigir('/mi_sistema/modules/auth/login.php');
}

if (isset($_GET['id'])) {
    $id_proveedor = (int)$_GET['id'];

    // Antes de borrar, verificar que no tenga compras asociadas
    $stmt_check = $conexion->prepare("SELECT COUNT(*) as total FROM compras WHERE id_proveedor = ?");
    $stmt_check->bind_param("i", $id_proveedor);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result()->fetch_assoc();

    if ($result_check['total'] > 0) {
        $_SESSION['mensaje'] = 'No se puede eliminar el proveedor porque tiene compras asociadas.';
        $_SESSION['mensaje_tipo'] = 'danger';
    } else {
        $stmt = $conexion->prepare("DELETE FROM proveedores WHERE id_proveedor = ?");
        $stmt->bind_param("i", $id_proveedor);
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = 'Proveedor eliminado correctamente.';
            $_SESSION['mensaje_tipo'] = 'success';
        } else {
            $_SESSION['mensaje'] = 'Error al eliminar el proveedor.';
            $_SESSION['mensaje_tipo'] = 'danger';
        }
        $stmt->close();
    }
    
    $stmt_check->close();
    $conexion->close();
    redirigir('index.php');
} else {
    redirigir('index.php');
}