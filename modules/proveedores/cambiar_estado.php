<?php
require_once __DIR__ . '/../../config/database.php';
if (!isset($_SESSION['id_usuario']) || !tienePermiso('proveedores_cambiar_estado')) {
    redirigir('/mi_sistema/index.php');
}

if (isset($_GET['id']) && isset($_GET['estado'])) {
    $id_proveedor = (int)$_GET['id'];
    $estado = (int)$_GET['estado'];

    $stmt = $conexion->prepare("UPDATE proveedores SET activo = ? WHERE id_proveedor = ?");
    $stmt->bind_param("ii", $estado, $id_proveedor);
    $stmt->execute();
    $stmt->close();
}
redirigir('index.php');