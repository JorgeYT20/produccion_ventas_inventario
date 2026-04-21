<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers_combos.php';

if (!isset($_SESSION['id_usuario']) || !tablaCombosDisponible($conexion)) {
    redirigir('/mi_sistema/modules/auth/login.php');
    exit;
}

$id_combo = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_combo > 0) {
    $stmt = $conexion->prepare("DELETE FROM combos WHERE id_combo = ?");
    $stmt->bind_param("i", $id_combo);
    $stmt->execute();
    $stmt->close();
}

redirigir('index.php');
