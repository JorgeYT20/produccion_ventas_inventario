<?php
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(403);
    exit;
}

header('Content-Type: application/json');
$term = $_GET['term'] ?? '';

if (strlen($term) < 2) {
    echo json_encode([]);
    exit;
}

// Para compras, no necesitamos filtrar por stock > 0
$stmt = $conexion->prepare("SELECT id_producto, nombre, precio_compra 
                            FROM productos WHERE nombre LIKE ? OR codigo_barra LIKE ? LIMIT 10");
$searchTerm = "%{$term}%";
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$resultado = $stmt->get_result();
$productos = [];
while ($row = $resultado->fetch_assoc()) {
    $productos[] = $row;
}

echo json_encode($productos);