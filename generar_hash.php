<?php
// La contraseña que queremos usar
$passwordPlano = '12345';

// Generar el hash usando el algoritmo por defecto y más seguro
$hash = password_hash($passwordPlano, PASSWORD_DEFAULT);

// Mostrar el hash en pantalla
echo "<h1>Hash Generado</h1>";
echo "<p>Copia la siguiente línea completa y pégala en la base de datos:</p>";
echo "<hr>";
echo "<code>" . $hash . "</code>";
echo "<hr>";
echo "<p><strong>Importante:</strong> Después de copiar y usar este hash, elimina el archivo 'generar_hash.php' de tu servidor.</p>";
?>