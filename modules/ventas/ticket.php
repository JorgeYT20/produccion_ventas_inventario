<?php
require_once __DIR__ . '/../../config/database.php';
if (!isset($_SESSION['id_usuario'])) { redirigir('login.php'); }

if (!isset($_GET['id_venta'])) {
    echo "Venta no encontrada.";
    exit;
}
$id_venta = (int)$_GET['id_venta'];

// Obtener datos de la venta
$sql_venta = "SELECT v.*, c.nombre_cliente, u.nombre_completo as cajero
              FROM ventas v
              LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
              JOIN usuarios u ON v.id_usuario = u.id_usuario
              WHERE v.id_venta = ?";
$stmt_venta = $conexion->prepare($sql_venta);
$stmt_venta->bind_param("i", $id_venta);
$stmt_venta->execute();
$venta = $stmt_venta->get_result()->fetch_assoc();

// Obtener detalles de la venta
$sql_detalle = "SELECT d.*, d.descuento, p.nombre as producto_nombre
                FROM detalle_ventas d
                JOIN productos p ON d.id_producto = p.id_producto
                WHERE d.id_venta = ?";
$stmt_detalle = $conexion->prepare($sql_detalle);
$stmt_detalle->bind_param("i", $id_venta);
$stmt_detalle->execute();
$detalles = $stmt_detalle->get_result();
$total_descuento = 0;
$subtotal_ticket = $venta ? ((float)$venta['total_venta']) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket de Venta #<?php echo $venta['id_venta']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  
    <style>
        /* Estilo para pantalla */
        body { font-family: 'Courier New', Courier, monospace; background-color: #f0f0f0; }
        .ticket { 
            max-width: 80mm; 
            margin: 20px auto; 
            padding: 15px; 
            background: white;
        }

        /* ESTILO CRÍTICO PARA IMPRESIÓN */
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }
            
            * {
                color: #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                -webkit-font-smoothing: none !important; /* Quita lo borroso */
            }

            body { background: none; padding: 0; margin: 0; }

            .ticket { 
                page-break-inside: avoid;
                height: auto;
                overflow: visible;
                width: 72mm;
                margin: 0; 
                padding-left: 6mm; /* Despega del borde izquierdo */
                padding-right: 4mm;
                box-sizing: border-box; 
            }

            html, body {
                height: auto;
            }

            /* --- ESTILO DEL TÍTULO (Para que vuelva a ser hermoso) --- */
            .header-ticket h2 {
                font-size: 24px !important; /* Tamaño grande para el nombre */
                font-weight: 900 !important;
                margin-bottom: 0;
                text-align: center;
            }

            .header-ticket p {
                font-size: 12px !important;
                text-align: center;
                margin: 2px 0;
            }

            /* --- ESTILO DE LA TABLA (Para nitidez extrema) --- */
            .table { 
                width: 100%;
                table-layout: fixed; 
                border-collapse: collapse;
                margin-top: 10px;
                font-family: 'Courier New', Courier, monospace;
            }

            .table th {
                border-bottom: 1px solid #000;
                font-size: 13px;
            }

            .table td {
                font-size: 13px;
                font-weight: 700; /* Hace que la descripción se vea más oscura */
                padding: 3px 0;
                word-wrap: break-word;
            }

            .no-print { display: none; }
        }
    </style>

</head>
<body>
    <?php if ($venta): ?>
        <div class="ticket">
            <br><br>
            
            <div class="header-ticket" style="text-align: center; font-family: 'Arial Narrow', sans-serif; margin-bottom: 5mm;">
    
                
                <div style="font-size: 22px; font-weight: 1000; text-transform: uppercase; color: #000; margin: 0; padding: 0; letter-spacing: 1px;">
                    Punto de Encuentro
                </div>
                
                <div style="border-top: 2px solid #000; width: 60%; margin: 1mm auto 1mm;"></div>
                
                <div style="font-size: 14px; font-weight: bold; text-transform: uppercase; color: #000;">
                    Licoreria | Minimarket
                </div>
                
                <p style="font-size: 11px; margin-top: 2mm; margin-bottom: 0;">RUC: 10434556738 | Av.Uno Virgen de Chaute</p>
                <p style="font-size: 11px; margin: 0;">Atención 24 Horas | Delivery: 959 833 609</p>
            </div>


            <p class="text-center" style="font-size: 12px;">Ticket #<?php echo $venta['id_venta']; ?></p>
            <div style="font-size: 12px; border-top: 1px dashed black; padding-top: 5px;">
                <p>Fecha: <?php echo date('d/m/y H:i', strtotime($venta['fecha_venta'])); ?></p>
                <p>Cajero: <?php echo substr(htmlspecialchars($venta['cajero']), 0, 20); ?></p>
            </div>
            
            <table style="width: 100%; font-size: 12px; border-top: 1px dashed black;">
                <thead>
                    <tr>
                        <th align="left">Cant</th>
                        <th align="left">Prod</th>
                        <th align="right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($item = $detalles->fetch_assoc()): ?>
                    <?php
                        $descuento_item = (float)$item['descuento'];
                        $total_descuento += $descuento_item;
                    ?>
                    <tr>
                        <td><?php echo $item['cantidad']; ?></td>
                        <td><?php echo substr(htmlspecialchars($item['producto_nombre']), 0, 15); ?></td>
                        <td align="right"><?php echo number_format($item['cantidad'] * $item['precio_unitario'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <?php
                $total_descuento = (float)$total_descuento;
                $subtotal_ticket = (float)$venta['total_venta'] + $total_descuento;
            ?>
            
            <div style="border-top: 1px dashed black; padding-top: 3px; text-align: right;">
                <?php if ($total_descuento > 0): ?>
                    <div style="font-size: 12px; margin-bottom: 2px;">
                        <strong>SUBTOTAL:</strong>
                        <span><?php echo getMoneda() . number_format($subtotal_ticket, 2); ?></span>
                    </div>
                    <div style="font-size: 12px; margin-bottom: 2px;">
                        <strong>DESCUENTO:</strong>
                        <span>-<?php echo getMoneda() . number_format($total_descuento, 2); ?></span>
                    </div>
                <?php endif; ?>
                <h5 style="font-weight: bold;">TOTAL: <?php echo getMoneda() . number_format($venta['total_venta'], 2); ?></h5>
            </div>

            <div style="margin-top: 5mm; border-top: 1px dashed #000; padding-top: 5mm; text-align: center;">
                

                <p style="font-size: 12px; font-style: italic; margin-bottom: 4mm;">
                    <strong>¡Por compras mayor a s/100, reclama tu bolsa de hielo!</strong>
                </p>

                <p style="font-size: 14px; font-weight: bold; margin-top: 5mm;">¡GRACIAS POR TU PREFERENCIA!</p>
                
                <br>
            </div>

        </div>
    <?php else: ?>
        <p class="text-center">No se encontró la venta solicitada.</p>
    <?php endif; ?>

    <div class="text-center no-print mt-3">
        <button onclick="window.print();" class="btn btn-primary">Imprimir Ticket</button>
        <a href="index.php" class="btn btn-secondary">Volver al POS</a>
        <a href="listado_ventas.php" class="btn btn-info text-white">Volver al Listado de Ventas</a>
    </div>


    <script>
        window.onload = function() {
            // Esto ayuda a que el navegador entienda el final del documento
            window.print();
            setTimeout(function(){ window.close(); }, 500);
        };
    </script>

</body>
</html>
