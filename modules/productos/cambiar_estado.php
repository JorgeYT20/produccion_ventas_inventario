<?php
require_once __DIR__ . '/../../config/database.php';
if (!isset($_SESSION['id_usuario']) || (!usuarioTieneRol([1, 2]) && !tienePermiso('productos_cambiar_estado'))) {
    redirigir('/mi_sistema/index.php');
}

if (isset($_GET['id']) && isset($_GET['estado'])) {
    $id_producto = (int)$_GET['id'];
    $estado = (int)$_GET['estado'];

    $stmt = $conexion->prepare("UPDATE productos SET activo = ? WHERE id_producto = ?");
    $stmt->bind_param("ii", $estado, $id_producto);
    $stmt->execute();
    $stmt->close();
}
redirigir('index.php');
