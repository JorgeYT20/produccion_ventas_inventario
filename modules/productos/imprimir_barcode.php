<?php
// Recibimos el código y el nombre por la URL
$codigo = $_GET['codigo'] ?? '';
$nombre = $_GET['nombre'] ?? 'Producto';

if (empty($codigo)) {
    die("No se proporcionó un código válido.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Imprimir Etiqueta - <?php echo $nombre; ?></title>
    <style>
        @page {
            size: 30mm 20mm; /* Define el tamaño físico de la hoja para el navegador */
            margin: 0; /* Elimina márgenes de la página para que no salga la fecha/hora arriba */
        }
        body {
            margin: 0;
            padding: 0;
            width: 26mm;
            height: 17mm;
            overflow: hidden; /* Evita que se cree una segunda página por error */
            position: relative;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding-top: 2mm;
            padding-left: 2mm;
            box-sizing: border-box;
        }
        .etiqueta {
            width: 28mm;
            height: 17mm; /* Le damos 0.5mm de "aire" para que no desborde */
            text-align: center;
            overflow: hidden;
        }
        .nombre-producto {
            font-size: 8px; /* Reducido para que quepa en 20mm de alto */
            font-family: Arial, sans-serif;
            font-weight: bold;
            margin-bottom: 0.5px;
            white-space: nowrap;
            overflow: hidden;
        }
        svg {
            width: 90%; /* Ajusta el ancho de las barras al papel */
            height: auto;
        }

        #barcode {
            max-width: 28mm; /* Deja 1mm de "Quiet Zone" a cada lado */
            height: 12mm;
            }
            @media print {
                .no-print { display: none; } /* Oculta botones al imprimir */
        }
    </style>
</head>
<body>

    <div class="etiqueta">
        <div class="nombre-producto"><?php echo htmlspecialchars($nombre); ?></div>
        <svg id="barcode"></svg>
    </div>

    <div class="no-print" style="margin-top: 20px;">
        <button onclick="window.print()">Imprimir</button>
        <button onclick="window.close()">Cerrar</button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script>
        JsBarcode("#barcode", "<?php echo $codigo; ?>", {
            format: "CODE128",
            lineColor: "#000",
            width: 1,      // Ancho de barra mínimo para etiquetas pequeñas
            height: 30,     // Altura ajustada para dejar espacio al texto
            displayValue: true,
            fontSize: 9,   // Tamaño del número bajo las barras
            margin: 0,
            marginTop: 0
        });
    </script>
</body>
</html>