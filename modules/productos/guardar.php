<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../ventas/helpers_presentaciones.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_usuario'])) {
    header('Location: /mi_sistema/modules/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$id_producto = !empty($_POST['id_producto']) ? (int)$_POST['id_producto'] : null;

if ($id_producto) {
    $puedeGuardarProducto = usuarioTieneRol([1]) || tienePermiso('productos_editar');
} else {
    $puedeGuardarProducto = usuarioTieneRol([1]) || tienePermiso('productos_crear');
}

if (!$puedeGuardarProducto) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

$nombre = trim($_POST['nombre'] ?? '');
$codigo_barra = trim($_POST['codigo_barra'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$id_categoria = !empty($_POST['id_categoria']) ? (int)$_POST['id_categoria'] : null;
$precio_venta = (float)($_POST['precio_venta'] ?? 0);
$precio_compra = (float)($_POST['precio_compra'] ?? 0);
$stock = (int)($_POST['stock'] ?? 0);
$stock_minimo = (int)($_POST['stock_minimo'] ?? 0);

$presentacion_tipos = $_POST['presentacion_tipo'] ?? [];
$presentacion_cantidades = $_POST['presentacion_cantidad'] ?? [];
$presentacion_precios = $_POST['presentacion_precio'] ?? [];

$presentaciones = [];
$totalPresentaciones = max(count($presentacion_tipos), count($presentacion_cantidades), count($presentacion_precios));

for ($i = 0; $i < $totalPresentaciones; $i++) {
    $tipo = trim($presentacion_tipos[$i] ?? '');
    $cantidad = (int)($presentacion_cantidades[$i] ?? 0);
    $precio = (float)($presentacion_precios[$i] ?? 0);

    if ($tipo === '' || $cantidad <= 1 || $precio <= 0) {
        continue;
    }

    $presentaciones[] = [
        'tipo' => $tipo,
        'cantidad' => $cantidad,
        'precio' => $precio
    ];
}

$nombre_imagen = null;
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
    $nombre_original = $_FILES['imagen']['name'];
    $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
    $nombre_base = pathinfo($nombre_original, PATHINFO_FILENAME);
    $nombre_limpio = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nombre_base)));
    $nombre_imagen = $nombre_limpio . "." . $extension;
    $ruta_destino = "../../assets/img/productos/" . $nombre_imagen;

    $contador = 1;
    while (file_exists($ruta_destino)) {
        $nombre_imagen = $nombre_limpio . "_" . $contador . "." . $extension;
        $ruta_destino = "../../assets/img/productos/" . $nombre_imagen;
        $contador++;
    }

    if (!is_dir("../../assets/img/productos/")) {
        mkdir("../../assets/img/productos/", 0777, true);
    }

    move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino);
}

$conexion->begin_transaction();

try {
    if ($id_producto) {
        if ($nombre_imagen) {
            $sql_img = "SELECT imagen FROM productos WHERE id_producto = ?";
            $stmt_img = $conexion->prepare($sql_img);
            $stmt_img->bind_param("i", $id_producto);
            $stmt_img->execute();
            $producto_actual = $stmt_img->get_result()->fetch_assoc();
            $stmt_img->close();

            if ($producto_actual && !empty($producto_actual['imagen']) && $producto_actual['imagen'] !== 'default_producto.png') {
                $ruta_antigua = "../../assets/img/productos/" . $producto_actual['imagen'];
                if (file_exists($ruta_antigua)) {
                    unlink($ruta_antigua);
                }
            }

            $sql = "UPDATE productos
                    SET nombre=?, codigo_barra=?, descripcion=?, id_categoria=?, precio_venta=?, precio_compra=?, stock=?, stock_minimo=?, imagen=?
                    WHERE id_producto=?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sssiddiisi", $nombre, $codigo_barra, $descripcion, $id_categoria, $precio_venta, $precio_compra, $stock, $stock_minimo, $nombre_imagen, $id_producto);
        } else {
            $sql = "UPDATE productos
                    SET nombre=?, codigo_barra=?, descripcion=?, id_categoria=?, precio_venta=?, precio_compra=?, stock=?, stock_minimo=?
                    WHERE id_producto=?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sssiddiii", $nombre, $codigo_barra, $descripcion, $id_categoria, $precio_venta, $precio_compra, $stock, $stock_minimo, $id_producto);
        }

        $stmt->execute();
        $stmt->close();
        $producto_id_guardado = $id_producto;
    } else {
        $imagen_final = $nombre_imagen ?? 'default_producto.png';
        $sql = "INSERT INTO productos (nombre, codigo_barra, descripcion, id_categoria, precio_venta, precio_compra, stock, stock_minimo, imagen)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssiddiis", $nombre, $codigo_barra, $descripcion, $id_categoria, $precio_venta, $precio_compra, $stock, $stock_minimo, $imagen_final);
        $stmt->execute();
        $producto_id_guardado = (int)$conexion->insert_id;
        $stmt->close();
    }

    if (tablaProductoPreciosDisponible($conexion)) {
        $stmtDeletePresentaciones = $conexion->prepare("DELETE FROM producto_precios WHERE id_producto = ?");
        $stmtDeletePresentaciones->bind_param("i", $producto_id_guardado);
        $stmtDeletePresentaciones->execute();
        $stmtDeletePresentaciones->close();

        if (!empty($presentaciones)) {
            $stmtInsertPresentacion = $conexion->prepare("INSERT INTO producto_precios (id_producto, tipo, cantidad, precio) VALUES (?, ?, ?, ?)");

            foreach ($presentaciones as $presentacion) {
                $tipo = $presentacion['tipo'];
                $cantidad = $presentacion['cantidad'];
                $precio = $presentacion['precio'];
                $stmtInsertPresentacion->bind_param("isid", $producto_id_guardado, $tipo, $cantidad, $precio);
                $stmtInsertPresentacion->execute();
            }

            $stmtInsertPresentacion->close();
        }
    }

    $conexion->commit();
    $_SESSION['mensaje'] = 'Producto guardado correctamente.';
    $_SESSION['mensaje_tipo'] = 'success';
} catch (Throwable $e) {
    $conexion->rollback();
    $_SESSION['mensaje'] = 'Error al guardar el producto: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'danger';
}

$conexion->close();
header("Location: index.php");
exit;
