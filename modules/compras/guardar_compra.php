<?php
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['id_usuario'])) {
    redirigir('/mi_sistema/modules/auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_proveedor = (int)$_POST['id_proveedor'];
    $total_compra = (float)$_POST['total_compra'];
    $productos_compra = json_decode($_POST['productos_compra'], true);

    $conexion->begin_transaction();
    try {
        // Insertar en la tabla 'compras'
        $stmt_compra = $conexion->prepare("INSERT INTO compras (id_proveedor, id_usuario, total_compra, estado) VALUES (?, ?, ?, 'solicitada')");
        $stmt_compra->bind_param("iid", $id_proveedor, $_SESSION['id_usuario'], $total_compra);
        $stmt_compra->execute();
        $id_compra = $conexion->insert_id;
        $stmt_compra->close();

        // Insertar en 'detalle_compras'
        $stmt_detalle = $conexion->prepare("INSERT INTO detalle_compras (id_compra, id_producto, cantidad, costo_unitario) VALUES (?, ?, ?, ?)");
        foreach ($productos_compra as $producto) {
            $stmt_detalle->bind_param("iiid", $id_compra, $producto['id'], $producto['cantidad'], $producto['costo']);
            $stmt_detalle->execute();
        }
        $stmt_detalle->close();

        $conexion->commit();
        $_SESSION['mensaje'] = 'Orden de compra creada con éxito.';
        $_SESSION['mensaje_tipo'] = 'success';
    } catch (Exception $e) {
        $conexion->rollback();
        $_SESSION['mensaje'] = 'Error al crear la orden de compra: ' . $e->getMessage();
        $_SESSION['mensaje_tipo'] = 'danger';
    }
    redirigir('index.php');
}