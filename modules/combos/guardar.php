<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers_combos.php';

if (!isset($_SESSION['id_usuario'])) {
    redirigir('/mi_sistema/modules/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !tablaCombosDisponible($conexion)) {
    redirigir('index.php');
}

$id_combo = !empty($_POST['id_combo']) ? (int)$_POST['id_combo'] : null;
$nombre = trim($_POST['nombre'] ?? '');
$precio = (float)($_POST['precio'] ?? 0);
$productos = $_POST['id_producto'] ?? [];
$cantidades = $_POST['cantidad'] ?? [];

$detalle = [];
foreach ($productos as $index => $idProducto) {
    $idProducto = (int)$idProducto;
    $cantidad = (int)($cantidades[$index] ?? 0);

    if ($idProducto <= 0 || $cantidad <= 0) {
        continue;
    }

    if (isset($detalle[$idProducto])) {
        $_SESSION['mensaje'] = 'No se permiten productos duplicados dentro del combo.';
        $_SESSION['mensaje_tipo'] = 'danger';
        redirigir($id_combo ? 'editar.php?id=' . $id_combo : 'crear.php');
    }

    $detalle[$idProducto] = $cantidad;
}

if ($nombre === '' || $precio <= 0 || empty($detalle)) {
    $_SESSION['mensaje'] = 'Debes completar nombre, precio y al menos un producto para el combo.';
    $_SESSION['mensaje_tipo'] = 'danger';
    redirigir($id_combo ? 'editar.php?id=' . $id_combo : 'crear.php');
}

$conexion->begin_transaction();

try {
    if ($id_combo) {
        $stmt = $conexion->prepare("UPDATE combos SET nombre = ?, precio = ? WHERE id_combo = ?");
        $stmt->bind_param("sdi", $nombre, $precio, $id_combo);
        $stmt->execute();
        $stmt->close();

        $stmtDelete = $conexion->prepare("DELETE FROM combo_detalle WHERE id_combo = ?");
        $stmtDelete->bind_param("i", $id_combo);
        $stmtDelete->execute();
        $stmtDelete->close();
    } else {
        $stmt = $conexion->prepare("INSERT INTO combos (nombre, precio, activo) VALUES (?, ?, 1)");
        $stmt->bind_param("sd", $nombre, $precio);
        $stmt->execute();
        $id_combo = (int)$conexion->insert_id;
        $stmt->close();
    }

    $stmtDetalle = $conexion->prepare("INSERT INTO combo_detalle (id_combo, id_producto, cantidad) VALUES (?, ?, ?)");
    foreach ($detalle as $idProducto => $cantidad) {
        $stmtDetalle->bind_param("iii", $id_combo, $idProducto, $cantidad);
        $stmtDetalle->execute();
    }
    $stmtDetalle->close();

    $conexion->commit();
    $_SESSION['mensaje'] = 'Combo guardado correctamente.';
    $_SESSION['mensaje_tipo'] = 'success';
} catch (Throwable $e) {
    $conexion->rollback();
    $_SESSION['mensaje'] = 'Error al guardar el combo: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'danger';
}

redirigir('index.php');
