<?php
require_once __DIR__ . '/../../config/database.php';
if (!isset($_SESSION['id_usuario']) || !tienePermiso('clientes_cambiar_estado')) {
    redirigir('/mi_sistema/index.php');
}

if (isset($_GET['id']) && isset($_GET['estado'])) {
    $id_cliente = (int)$_GET['id'];
    $estado = (int)$_GET['estado'];

    $stmt = $conexion->prepare("UPDATE clientes SET activo = ? WHERE id_cliente = ?");
    $stmt->bind_param("ii", $estado, $id_cliente);
    $stmt->execute();
    $stmt->close();
}
redirigir('index.php');