<?php
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['id_usuario'])) {
    redirigir('/mi_sistema/modules/auth/login.php');
}
$id_usuario = $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monto_inicial = (float)$_POST['monto_inicial'];

    // Verificar que no tenga ya un turno abierto
    $stmt_check = $conexion->prepare("SELECT id_turno FROM turnos_caja WHERE id_usuario = ? AND estado = 'abierto'");
    $stmt_check->bind_param("i", $id_usuario);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $_SESSION['mensaje'] = 'Ya tienes un turno de caja abierto.';
        $_SESSION['mensaje_tipo'] = 'danger';
    } else {
        $stmt_insert = $conexion->prepare("INSERT INTO turnos_caja (id_usuario, monto_inicial) VALUES (?, ?)");
        $stmt_insert->bind_param("id", $id_usuario, $monto_inicial);
        if ($stmt_insert->execute()) {
            $_SESSION['mensaje'] = 'Turno de caja iniciado correctamente.';
            $_SESSION['mensaje_tipo'] = 'success';
        } else {
            $_SESSION['mensaje'] = 'Error al iniciar el turno.';
            $_SESSION['mensaje_tipo'] = 'danger';
        }
        $stmt_insert->close();
    }
    $stmt_check->close();
    redirigir('index.php');
}