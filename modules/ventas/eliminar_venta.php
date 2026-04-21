<?php
require_once __DIR__ . '/../../config/database.php';

$transaccionAbierta = false;

try {
    if (!isset($_SESSION['usuario']) && usuarioActual()) {
        $_SESSION['usuario'] = usuarioActual();
    }

    if (!isset($_SESSION['id_usuario'])) {
        throw new Exception('Debes iniciar sesión para realizar esta acción.');
    }

    if (!isset($_SESSION['usuario']) || (int)($_SESSION['usuario']['id_rol'] ?? 0) !== 1) {
        throw new Exception('No tienes permisos para eliminar ventas');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido.');
    }

    $idVenta = isset($_POST['id_venta']) ? (int)$_POST['id_venta'] : 0;
    if ($idVenta <= 0) {
        throw new Exception('Venta no válida.');
    }

    $conexion->begin_transaction();
    $transaccionAbierta = true;

    $stmtVenta = $conexion->prepare("SELECT id_venta FROM ventas WHERE id_venta = ? LIMIT 1");
    $stmtVenta->bind_param('i', $idVenta);
    $stmtVenta->execute();
    $venta = $stmtVenta->get_result()->fetch_assoc();
    $stmtVenta->close();

    if (!$venta) {
        throw new Exception('La venta indicada no existe.');
    }

    $stmtDetalle = $conexion->prepare("SELECT id_producto, cantidad FROM detalle_ventas WHERE id_venta = ?");
    $stmtDetalle->bind_param('i', $idVenta);
    $stmtDetalle->execute();
    $detalles = $stmtDetalle->get_result();

    $cantidades = [];
    while ($detalle = $detalles->fetch_assoc()) {
        $idProducto = (int)$detalle['id_producto'];
        $cantidades[$idProducto] = ($cantidades[$idProducto] ?? 0) + (int)$detalle['cantidad'];
    }
    $stmtDetalle->close();

    $stmtRestaurar = $conexion->prepare("UPDATE productos SET stock = stock + ? WHERE id_producto = ?");
    foreach ($cantidades as $idProducto => $cantidad) {
        $stmtRestaurar->bind_param('ii', $cantidad, $idProducto);
        $stmtRestaurar->execute();
    }
    $stmtRestaurar->close();

    $stmtMovimientos = $conexion->prepare("DELETE FROM movimientos_inventario WHERE tipo_movimiento = 'venta' AND referencia_id = ?");
    $stmtMovimientos->bind_param('i', $idVenta);
    $stmtMovimientos->execute();
    $stmtMovimientos->close();

    $stmtEliminarPagos = $conexion->prepare("DELETE FROM venta_pagos WHERE id_venta = ?");
    $stmtEliminarPagos->bind_param('i', $idVenta);
    $stmtEliminarPagos->execute();
    $stmtEliminarPagos->close();

    $stmtEliminarDetalle = $conexion->prepare("DELETE FROM detalle_ventas WHERE id_venta = ?");
    $stmtEliminarDetalle->bind_param('i', $idVenta);
    $stmtEliminarDetalle->execute();
    $stmtEliminarDetalle->close();

    $stmtEliminarVenta = $conexion->prepare("DELETE FROM ventas WHERE id_venta = ?");
    $stmtEliminarVenta->bind_param('i', $idVenta);
    $stmtEliminarVenta->execute();
    $stmtEliminarVenta->close();

    $conexion->commit();
    $transaccionAbierta = false;
    $_SESSION['mensaje'] = 'La venta fue eliminada y el stock se restauró correctamente.';
    $_SESSION['mensaje_tipo'] = 'success';
} catch (Throwable $e) {
    if ($transaccionAbierta) {
        $conexion->rollback();
    }
    $_SESSION['mensaje'] = 'No se pudo eliminar la venta: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'danger';
}

redirigir('/mi_sistema/modules/ventas/historial.php');
