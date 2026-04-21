<?php
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['id_usuario'])) {
    redirigir('/mi_sistema/modules/auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_turno = (int)$_POST['id_turno'];
    $tipo_movimiento = $_POST['tipo_movimiento'];
    $monto = (float)$_POST['monto'];
    $descripcion = trim($_POST['descripcion']);

    $stmt = $conexion->prepare("INSERT INTO movimientos_caja (id_turno, id_usuario, tipo_movimiento, monto, descripcion) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisds", $id_turno, $_SESSION['id_usuario'], $tipo_movimiento, $monto, $descripcion);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = 'Movimiento registrado correctamente.';
        $_SESSION['mensaje_tipo'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al registrar el movimiento.';
        $_SESSION['mensaje_tipo'] = 'danger';
    }
    $stmt->close();
    redirigir('index.php');
}