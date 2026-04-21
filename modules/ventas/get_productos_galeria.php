<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers_presentaciones.php';
header('Content-Type: application/json');

$buscar = $_GET['buscar'] ?? '';

$sql = "SELECT id_producto, nombre, precio_venta, imagen, stock
        FROM productos
        WHERE activo = 1";

$params = [];
$types = '';

if ($buscar !== '') {
    $sql .= " AND (nombre LIKE ? OR codigo_barra LIKE ?)";
    $likeBuscar = '%' . $buscar . '%';
    $params[] = $likeBuscar;
    $params[] = $likeBuscar;
    $types .= 'ss';
}

$sql .= " ORDER BY nombre ASC LIMIT 24";
$stmt = $conexion->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$productos = [];
while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
}

$stmt->close();

$productos = anexarPresentacionesAProductos($conexion, $productos);

echo json_encode($productos);
