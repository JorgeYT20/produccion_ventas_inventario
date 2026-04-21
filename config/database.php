<?php
// Iniciar la sesión aquí, como único punto de entrada.
session_start();

// --- CONFIGURACIÓN GLOBAL DE LA APLICACIÓN ---
$config = [
    'moneda' => '$',
    'nombre_tienda' => 'GestiónPRO'
];
function getConfig($key) {
    global $config;
    return $config[$key] ?? null;
}
function getMoneda() {
    if (isset($_SESSION['moneda_usuario']) && !empty($_SESSION['moneda_usuario'])) {
        return $_SESSION['moneda_usuario'];
    }
    return getConfig('moneda');
}
// --- FIN DE LA CONFIGURACIÓN GLOBAL ---

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tienda_sistema');

$conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conexion->connect_error) {
    die("Error de Conexión: " . $conexion->connect_error);
}
$conexion->set_charset("utf8");

function redirigir($url) {
    header("Location: " . $url);
    exit();
}
function usuarioActual(): ?array {
    if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario'])) {
        return $_SESSION['usuario'];
    }

    if (isset($_SESSION['id_usuario'])) {
        return [
            'id_usuario' => (int)$_SESSION['id_usuario'],
            'nombre_completo' => $_SESSION['nombre_usuario'] ?? '',
            'id_rol' => isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : null
        ];
    }

    return null;
}
function usuarioIdRolActual(): ?int {
    $usuario = usuarioActual();
    return isset($usuario['id_rol']) ? (int)$usuario['id_rol'] : null;
}
function usuarioTieneRol(array $roles): bool {
    $idRol = usuarioIdRolActual();
    return $idRol !== null && in_array($idRol, $roles, true);
}
function esAdministrador(): bool {
    return usuarioTieneRol([1]);
}
function tienePermiso($permiso) {
    if (isset($_SESSION['permisos']) && in_array($permiso, $_SESSION['permisos'])) {
        return true;
    }
    return false;
}
