<?php
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['id_usuario']) || !tienePermiso('compras_recibir')) {
    redirigir('/mi_sistema/index.php');
}

if (!isset($_GET['id'])) {
    redirigir('index.php');
}
$id_compra = (int)$_GET['id'];

$conexion->begin_transaction();
try {
    $stmt_detalles = $conexion->prepare("SELECT id_producto, cantidad FROM detalle_compras WHERE id_compra = ?");
    $stmt_detalles->bind_param("i", $id_compra);
    $stmt_detalles->execute();
    $detalles = $stmt_detalles->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_detalles->close();

    if (empty($detalles)) {
        throw new Exception("No se encontraron detalles para esta compra.");
    }

    foreach ($detalles as $detalle) {
        $id_producto = $detalle['id_producto'];
        $cantidad = $detalle['cantidad'];

        $stmt_stock_actual = $conexion->prepare("SELECT stock FROM productos WHERE id_producto = ? FOR UPDATE");
        $stmt_stock_actual->bind_param("i", $id_producto);
        $stmt_stock_actual->execute();
        $stock_anterior = $stmt_stock_actual->get_result()->fetch_assoc()['stock'];
        $stmt_stock_actual->close();

        $stock_nuevo = $stock_anterior + $cantidad;

        $stmt_kardex = $conexion->prepare(
            "INSERT INTO movimientos_inventario 
                (id_producto, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, id_usuario, referencia_id) 
             VALUES (?, 'compra', ?, ?, ?, ?, ?)"
        );
        $stmt_kardex->bind_param("iiiiii", $id_producto, $cantidad, $stock_anterior, $stock_nuevo, $_SESSION['id_usuario'], $id_compra);
        $stmt_kardex->execute();
        $stmt_kardex->close();

        $stmt_stock_update = $conexion->prepare("UPDATE productos SET stock = ? WHERE id_producto = ?");
        $stmt_stock_update->bind_param("ii", $stock_nuevo, $id_producto);
        $stmt_stock_update->execute();
        $stmt_stock_update->close();
    }

    // Esta es la consulta que causaba el error. Ahora funcionará.
    $stmt_compra = $conexion->prepare("UPDATE compras SET estado = 'recibida', fecha_recepcion = CURRENT_TIMESTAMP WHERE id_compra = ? AND estado = 'solicitada'");
    $stmt_compra->bind_param("i", $id_compra);
    $stmt_compra->execute();
    
    if ($stmt_compra->affected_rows > 0) {
        $conexion->commit();
        $_SESSION['mensaje'] = 'La compra ha sido marcada como recibida y el stock ha sido actualizado.';
        $_SESSION['mensaje_tipo'] = 'success';
    } else {
        $conexion->rollback();
        $_SESSION['mensaje'] = 'La compra no pudo ser actualizada (posiblemente ya estaba recibida).';
        $_SESSION['mensaje_tipo'] = 'warning';
    }
    $stmt_compra->close();

} catch (Exception $e) {
    $conexion->rollback();
    $_SESSION['mensaje'] = 'Error al procesar la recepción: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'danger';
}

redirigir('index.php');