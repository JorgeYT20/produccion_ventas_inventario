<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario']) && function_exists('usuarioActual')) {
    $usuarioSesion = usuarioActual();
    if ($usuarioSesion) {
        $_SESSION['usuario'] = $usuarioSesion;
    }
}

$usuarioSidebar = $_SESSION['usuario'] ?? null;
$idRolSidebar = isset($usuarioSidebar['id_rol']) ? (int)$usuarioSidebar['id_rol'] : null;
$nombreUsuarioSidebar = $usuarioSidebar['nombre_completo'] ?? ($_SESSION['nombre_usuario'] ?? 'Usuario');
$rolUsuarioSidebar = match ($idRolSidebar) {
    1 => 'Administrador',
    2 => 'Cajero',
    default => 'Usuario'
};
$perfilSidebarHref = '/mi_sistema/modules/perfil/index.php';

$rutaActualSidebar = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

$menuSidebar = [
    [
        'label' => 'Dashboard',
        'icon' => 'fa-chart-pie',
        'href' => '/mi_sistema/index.php',
        'match' => ['/mi_sistema/index.php'],
        'visible' => tienePermiso('dashboard_ver')
    ],
    [
        'label' => 'Ventas',
        'icon' => 'fa-cash-register',
        'href' => '/mi_sistema/modules/ventas/index.php',
        'match' => [
            '/mi_sistema/modules/ventas/index.php',
            '/mi_sistema/modules/ventas/ticket.php'
        ],
        'visible' => usuarioTieneRol([1, 2]) || tienePermiso('ventas_crear')
    ],
    [
        'label' => 'Historial',
        'icon' => 'fa-receipt',
        'href' => '/mi_sistema/modules/ventas/historial.php',
        'match' => [
            '/mi_sistema/modules/ventas/historial.php',
            '/mi_sistema/modules/ventas/listado_ventas.php'
        ],
        'visible' => $idRolSidebar !== null && in_array($idRolSidebar, [1, 2], true)
    ],
    [
        'label' => 'Arqueo Historial',
        'icon' => 'fa-cash-register',
        'href' => '/mi_sistema/modules/arqueo/historial.php',
        'match' => [
            '/mi_sistema/modules/arqueo/historial.php'
        ],
        'visible' => $idRolSidebar !== null && in_array($idRolSidebar, [1, 2], true)
    ],
    [
        'label' => 'Inventario',
        'icon' => 'fa-box-open',
        'href' => '/mi_sistema/modules/productos/index.php',
        'match' => ['/mi_sistema/modules/productos/index.php'],
        'visible' => usuarioTieneRol([1, 2]) || tienePermiso('productos_ver_lista')
    ],
    [
        'label' => 'Categorias',
        'icon' => 'fa-tags',
        'href' => '/mi_sistema/modules/categorias/index.php',
        'match' => ['/mi_sistema/modules/categorias/index.php'],
        'visible' => tienePermiso('categorias_ver_lista') || usuarioTieneRol([1])
    ],
    [
        'label' => 'Combos',
        'icon' => 'fa-layer-group',
        'href' => '/mi_sistema/modules/combos/index.php',
        'match' => [
            '/mi_sistema/modules/combos/index.php',
            '/mi_sistema/modules/combos/crear.php',
            '/mi_sistema/modules/combos/editar.php'
        ],
        'visible' => usuarioTieneRol([1, 2])
    ],
    [
        'label' => 'Compras',
        'icon' => 'fa-truck',
        'href' => '/mi_sistema/modules/compras/index.php',
        'match' => ['/mi_sistema/modules/compras/index.php'],
        'visible' => tienePermiso('compras_ver_lista') || usuarioTieneRol([1])
    ],
    [
        'label' => 'Proveedores',
        'icon' => 'fa-truck-loading',
        'href' => '/mi_sistema/modules/proveedores/index.php',
        'match' => ['/mi_sistema/modules/proveedores/index.php'],
        'visible' => tienePermiso('proveedores_ver_lista') || usuarioTieneRol([1])
    ],
    [
        'label' => 'Clientes',
        'icon' => 'fa-users',
        'href' => '/mi_sistema/modules/clientes/index.php',
        'match' => ['/mi_sistema/modules/clientes/index.php'],
        'visible' => tienePermiso('clientes_ver_lista') || usuarioTieneRol([1, 2])
    ],
    [
        'label' => 'Caja',
        'icon' => 'fa-calculator',
        'href' => '/mi_sistema/modules/caja/index.php',
        'match' => ['/mi_sistema/modules/caja/index.php'],
        'visible' => tienePermiso('caja_gestionar') || usuarioTieneRol([1, 2])
    ],
    [
        'label' => 'Reportes',
        'icon' => 'fa-chart-line',
        'href' => '/mi_sistema/modules/reportes/index.php',
        'match' => ['/mi_sistema/modules/reportes/index.php'],
        'visible' => $idRolSidebar === 1 && (tienePermiso('reportes_ver_ventas') || tienePermiso('reportes_ver_inventario') || usuarioTieneRol([1]))
    ],
    [
        'label' => 'Usuarios',
        'icon' => 'fa-users-cog',
        'href' => '/mi_sistema/modules/usuarios/index.php',
        'match' => ['/mi_sistema/modules/usuarios/index.php'],
        'visible' => $idRolSidebar === 1 && (tienePermiso('usuarios_ver_lista') || usuarioTieneRol([1]))
    ],
];

$itemsSidebarVisibles = array_values(array_filter($menuSidebar, fn($item) => !empty($item['visible'])));

function sidebarLinkActivo(string $rutaActualSidebar, array $matches): bool
{
    foreach ($matches as $match) {
        if ($rutaActualSidebar === $match) {
            return true;
        }
    }

    return false;
}
?>

<div class="d-lg-none app-mobile-topbar">
    <button class="btn btn-dark app-mobile-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebarOffcanvas" aria-controls="appSidebarOffcanvas">
        <i class="fas fa-bars"></i>
    </button>
    <a class="app-mobile-brand" href="/mi_sistema/index.php">
        <i class="fa-brands fa-battle-net"></i>
        <span>Licoreria</span>
    </a>
</div>

<aside class="app-sidebar d-none d-lg-flex flex-column">
    <div class="app-sidebar-brand">
        <a href="/mi_sistema/index.php" class="text-decoration-none text-white d-flex align-items-center gap-2">
            <i class="fa-brands fa-battle-net"></i>
            <div>
            
                <small class="app-sidebar-subtitle">POS Comercial</small>
            </div>
        </a>
    </div>

    <nav class="app-sidebar-nav app-sidebar-scroll">
        <?php foreach ($itemsSidebarVisibles as $item): ?>
            <?php $activo = sidebarLinkActivo($rutaActualSidebar, $item['match']); ?>
            <a href="<?php echo htmlspecialchars($item['href']); ?>" class="app-sidebar-link <?php echo $activo ? 'active' : ''; ?>">
                <i class="fas <?php echo htmlspecialchars($item['icon']); ?>"></i>
                <span><?php echo htmlspecialchars($item['label']); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="app-sidebar-user-card mt-auto">
        <div class="app-sidebar-user-info">
            <div>
                <div class="app-sidebar-user-name"><?php echo htmlspecialchars($nombreUsuarioSidebar); ?></div>
                <div class="app-sidebar-user-role"><?php echo htmlspecialchars($rolUsuarioSidebar); ?></div>
            </div>
            <a href="<?php echo htmlspecialchars($perfilSidebarHref); ?>" class="app-sidebar-settings-link" title="Configuracion de perfil" aria-label="Ir al perfil">
                <i class="fas fa-cog"></i>
            </a>
        </div>
        <a href="/mi_sistema/modules/auth/logout.php" class="btn btn-outline-light btn-sm w-100 mt-3">
            <i class="fas fa-sign-out-alt me-2"></i>Cerrar sesion
        </a>
    </div>
</aside>

<div class="offcanvas offcanvas-start app-sidebar-offcanvas text-bg-dark" tabindex="-1" id="appSidebarOffcanvas" aria-labelledby="appSidebarOffcanvasLabel">
    <div class="offcanvas-header border-bottom border-secondary-subtle">
        <div>
            <h5 class="offcanvas-title mb-0" id="appSidebarOffcanvasLabel">Licoreria</h5>
            <small class="text-white-50">POS Comercial</small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-3">
        <nav class="app-sidebar-nav app-sidebar-scroll">
            <?php foreach ($itemsSidebarVisibles as $item): ?>
                <?php $activo = sidebarLinkActivo($rutaActualSidebar, $item['match']); ?>
                <a href="<?php echo htmlspecialchars($item['href']); ?>" class="app-sidebar-link <?php echo $activo ? 'active' : ''; ?>">
                    <i class="fas <?php echo htmlspecialchars($item['icon']); ?>"></i>
                    <span><?php echo htmlspecialchars($item['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="app-sidebar-user-card mt-auto">
            <div class="app-sidebar-user-info">
                <div>
                    <div class="app-sidebar-user-name"><?php echo htmlspecialchars($nombreUsuarioSidebar); ?></div>
                    <div class="app-sidebar-user-role"><?php echo htmlspecialchars($rolUsuarioSidebar); ?></div>
                </div>
                <a href="<?php echo htmlspecialchars($perfilSidebarHref); ?>" class="app-sidebar-settings-link" title="Configuracion de perfil" aria-label="Ir al perfil">
                    <i class="fas fa-cog"></i>
                </a>
            </div>
            <a href="/mi_sistema/modules/auth/logout.php" class="btn btn-outline-light btn-sm w-100 mt-3">
                <i class="fas fa-sign-out-alt me-2"></i>Cerrar sesion
            </a>
        </div>
    </div>
</div>
