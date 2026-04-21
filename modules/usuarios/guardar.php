<?php
require_once __DIR__ . '/../../config/database.php';
if (!isset($_SESSION['id_usuario']) || !tienePermiso('usuarios_crear_editar')) {
    redirigir('/mi_sistema/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_POST['id_usuario'] ?? null;
    $nombre = trim($_POST['nombre_completo']);
    $username = trim($_POST['username']);
    $id_rol = (int)$_POST['id_rol'];
    $password = $_POST['password'];

    // Si es un usuario nuevo o se especificó una nueva contraseña
    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if (!empty($id_usuario)) { // Actualizar con contraseña
            $stmt = $conexion->prepare("UPDATE usuarios SET nombre_completo = ?, username = ?, id_rol = ?, password = ? WHERE id_usuario = ?");
            $stmt->bind_param("ssisi", $nombre, $username, $id_rol, $hash, $id_usuario);
        } else { // Crear usuario
            $stmt = $conexion->prepare("INSERT INTO usuarios (nombre_completo, username, id_rol, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $nombre, $username, $id_rol, $hash);
        }
    } else { // Actualizar sin contraseña
        if (!empty($id_usuario)) {
            $stmt = $conexion->prepare("UPDATE usuarios SET nombre_completo = ?, username = ?, id_rol = ? WHERE id_usuario = ?");
            $stmt->bind_param("ssii", $nombre, $username, $id_rol, $id_usuario);
        }
    }
    
    if (isset($stmt)) {
        $stmt->execute();
        $stmt->close();
    }
}
redirigir('index.php');