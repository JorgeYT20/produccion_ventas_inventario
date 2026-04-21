<?php
require_once __DIR__ . '/../../config/database.php';
if (!isset($_SESSION['id_usuario']) || !tienePermiso('categorias_cambiar_estado')) {
    redirigir('/mi_sistema/index.php');
}

if (isset($_GET['id']) && isset($_GET['estado'])) {
    $id_categoria = (int)$_GET['id'];
    $estado = (int)$_GET['estado']; // 0 para inactivo, 1 para activo

    $stmt = $conexion->prepare("UPDATE categorias SET activo = ? WHERE id_categoria = ?");
    $stmt->bind_param("ii", $estado, $id_categoria);
    $stmt->execute();
    $stmt->close();
}
redirigir('index.php');