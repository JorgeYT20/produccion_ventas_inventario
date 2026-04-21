<?php
require_once __DIR__ . '/../../config/database.php';
if (!isset($_SESSION['id_usuario']) || !tienePermiso('usuarios_cambiar_estado')) {
    redirigir('/mi_sistema/index.php');
}

if (isset($_GET['id']) && isset($_GET['estado'])) {
    $id_usuario = (int)$_GET['id'];
    $estado = (int)$_GET['estado'];

    // Prevenir que un usuario se desactive a sí mismo
    if ($id_usuario == $_SESSION['id_usuario']) {
        redirigir('index.php');
    }

    $stmt = $conexion->prepare("UPDATE usuarios SET activo = ? WHERE id_usuario = ?");
    $stmt->bind_param("ii", $estado, $id_usuario);
    $stmt->execute();
    $stmt->close();
}
redirigir('index.php');