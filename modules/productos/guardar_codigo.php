<?php
// 1. Evitamos cualquier salida de texto previa que rompa el JSON
ob_start();

// 2. Configuración de errores para ver qué pasa
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 3. Incluimos la base de datos
// Según tu VS Code: mi_sistema/modules/productos/guardar_codigo.php
// Para llegar a: mi_sistema/config/database.php
require_once "../../config/database.php";

// 4. Limpiamos cualquier buffer por si el include soltó algún espacio
ob_clean();
header('Content-Type: application/json');

$response = ['success' => false, 'error' => 'Inicio de proceso'];

if (isset($_POST['id']) && !empty($_POST['id'])) {
    $id = $conexion->real_escape_string($_POST['id']);
    
    // Generamos el código (99 + 6 ceros + ID)
    $nuevoCodigo = "99" . str_pad($id, 6, "0", STR_PAD_LEFT);

    // IMPORTANTE: Verifica que tu tabla sea 'productos' y tu columna 'id_producto'
    $sql = "UPDATE productos SET codigo_barra = '$nuevoCodigo' WHERE id_producto = '$id'";
    
    if ($conexion->query($sql)) {
        if ($conexion->affected_rows > 0) {
            $response = ['success' => true, 'codigo' => $nuevoCodigo];
        } else {
            $response = ['success' => false, 'error' => 'No se encontró el producto o ya tiene ese código'];
        }
    } else {
        $response = ['success' => false, 'error' => $conexion->error];
    }
} else {
    $response = ['success' => false, 'error' => 'No se recibió el ID del producto'];
}

echo json_encode($response);
exit;