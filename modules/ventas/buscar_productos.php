<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers_presentaciones.php';

$term = $_GET['term'] ?? '';
$exact = isset($_GET['exact']); // Nueva bandera

if ($exact) {
    // BÚSQUEDA POR ESCÁNER (Coincidencia exacta)
    $sql = "SELECT id_producto, nombre, precio_venta, stock, imagen
            FROM productos 
            WHERE (codigo_barra = ? OR id_producto = ?) 
            AND activo = 1 LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ss", $term, $term);
} else {
    // BÚSQUEDA POR NOMBRE (Sugerencias)
    $sql = "SELECT id_producto, nombre, precio_venta, stock, imagen
            FROM productos 
            WHERE nombre LIKE ? 
            AND activo = 1 LIMIT 10";
    $stmt = $conexion->prepare($sql);
    $likeTerm = "%$term%";
    $stmt->bind_param("s", $likeTerm);
}

$stmt->execute();
$resultado = $stmt->get_result();
$productos = [];

while ($fila = $resultado->fetch_assoc()) {
    $productos[] = $fila;
}

$stmt->close();
$productos = anexarPresentacionesAProductos($conexion, $productos);

echo json_encode($productos);
