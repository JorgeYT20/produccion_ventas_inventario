<?php
require_once __DIR__ . '/../../config/database.php';

// Proteger el script
if (!isset($_SESSION['id_usuario'])) {
    redirigir('/mi_sistema/modules/auth/login.php');
}
if (!tienePermiso('proveedores_crear_editar')) {
    $_SESSION['mensaje'] = 'No tienes permiso para realizar esta acción.';
    $_SESSION['mensaje_tipo'] = 'danger';
    redirigir('index.php');
}

// Asegurarse de que los datos se envíen por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recolectar datos del formulario
    $id_proveedor = $_POST['id_proveedor'] ?? null;
    $nombre = trim($_POST['nombre_proveedor']);
    $ruc = trim($_POST['ruc']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $direccion = trim($_POST['direccion']);

    // Si hay un ID, es una actualización (UPDATE)
    if (!empty($id_proveedor)) {
        $stmt = $conexion->prepare("UPDATE proveedores SET nombre_proveedor = ?, ruc = ?, telefono = ?, email = ?, direccion = ? WHERE id_proveedor = ?");
        $stmt->bind_param("sssssi", $nombre, $ruc, $telefono, $email, $direccion, $id_proveedor);
    } 
    // Si no hay ID, es una creación (INSERT)
    else {
        $stmt = $conexion->prepare("INSERT INTO proveedores (nombre_proveedor, ruc, telefono, email, direccion) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nombre, $ruc, $telefono, $email, $direccion);
    }

    // Ejecutar la consulta y preparar mensaje
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = 'Proveedor guardado correctamente.';
        $_SESSION['mensaje_tipo'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al guardar el proveedor: ' . $stmt->error;
        $_SESSION['mensaje_tipo'] = 'danger';
    }
    $stmt->close();
    $conexion->close();

}

// Redirigir siempre de vuelta a la lista
redirigir('index.php');