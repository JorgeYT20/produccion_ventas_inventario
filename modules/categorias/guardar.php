<?php
require_once __DIR__ . '/../../config/database.php';
if (!isset($_SESSION['id_usuario']) || !tienePermiso('categorias_crear_editar')) {
    redirigir('/mi_sistema/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $id_categoria = $_POST['id_categoria'] ?? null;

    if (!empty($id_categoria)) { // Actualizar
        $stmt = $conexion->prepare("UPDATE categorias SET nombre = ? WHERE id_categoria = ?");
        $stmt->bind_param("si", $nombre, $id_categoria);
    } else { // Crear
        $stmt = $conexion->prepare("INSERT INTO categorias (nombre) VALUES (?)");
        $stmt->bind_param("s", $nombre);
    }
    $stmt->execute();
    $stmt->close();
}
redirigir('index.php');