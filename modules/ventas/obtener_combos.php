<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../combos/helpers_combos.php';
header('Content-Type: application/json');

echo json_encode(obtenerCombosActivos($conexion));
