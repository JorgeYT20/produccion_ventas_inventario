<?php
require_once __DIR__ . '/../../config/database.php';
if (!isset($_SESSION['id_usuario']) || !tienePermiso('clientes_crear_editar')) {
    redirigir('/mi_sistema/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = $_POST['id_cliente'] ?? null;
    $nombre = trim($_POST['nombre_cliente']);
    $documento = trim($_POST['documento_identidad']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $direccion = trim($_POST['direccion']);

    if (!empty($id_cliente)) { // Actualizar
        $stmt = $conexion->prepare("UPDATE clientes SET nombre_cliente = ?, documento_identidad = ?, telefono = ?, email = ?, direccion = ? WHERE id_cliente = ?");
        $stmt->bind_param("sssssi", $nombre, $documento, $telefono, $email, $direccion, $id_cliente);
    } else { // Crear
        $stmt = $conexion->prepare("INSERT INTO clientes (nombre_cliente, documento_identidad, telefono, email, direccion) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nombre, $documento, $telefono, $email, $direccion);
    }
    $stmt->execute();
    $stmt->close();
}
redirigir('index.php');