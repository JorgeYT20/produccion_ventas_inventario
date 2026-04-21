<?php
require_once __DIR__ . '/../../config/database.php';
if (!isset($_SESSION['id_usuario'])) { redirigir('/mi_sistema/modules/auth/login.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $moneda = $_POST['moneda'];
    
    $stmt = $conexion->prepare("UPDATE usuarios SET moneda = ? WHERE id_usuario = ?");
    $stmt->bind_param("si", $moneda, $_SESSION['id_usuario']);
    if ($stmt->execute()) {
        // Actualizar la sesión inmediatamente
        $_SESSION['moneda_usuario'] = $moneda;
    }
    $stmt->close();
}
redirigir('index.php');