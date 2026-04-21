<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario']) && function_exists('usuarioActual')) {
    $usuarioSesion = usuarioActual();
    if ($usuarioSesion) {
        $_SESSION['usuario'] = $usuarioSesion;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/mi_sistema/assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --app-sidebar-width: 280px;
            --app-sidebar-bg: #111827;
            --app-sidebar-border: rgba(255, 255, 255, 0.08);
            --app-sidebar-text: rgba(255, 255, 255, 0.78);
            --app-sidebar-text-strong: #ffffff;
            --app-sidebar-hover: rgba(255, 255, 255, 0.08);
            --app-sidebar-active: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            --app-body-bg: #f4f6fb;
            --app-card-bg: rgba(255, 255, 255, 0.08);
        }

        html, body {
            min-height: 100%;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--app-body-bg);
            color: #111827;
        }

        .app-shell {
            min-height: 100vh;
        }

        .app-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--app-sidebar-width);
            height: 100vh;
            max-height: 100vh;
            overflow-y: auto;
            background: var(--app-sidebar-bg);
            color: var(--app-sidebar-text);
            border-right: 1px solid var(--app-sidebar-border);
            padding: 1.5rem 1rem;
            z-index: 1040;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.22) transparent;
        }

        .app-sidebar::-webkit-scrollbar,
        .app-sidebar-scroll::-webkit-scrollbar,
        .offcanvas-body::-webkit-scrollbar {
            width: 6px;
        }

        .app-sidebar::-webkit-scrollbar-track,
        .app-sidebar-scroll::-webkit-scrollbar-track,
        .offcanvas-body::-webkit-scrollbar-track {
            background: transparent;
        }

        .app-sidebar::-webkit-scrollbar-thumb,
        .app-sidebar-scroll::-webkit-scrollbar-thumb,
        .offcanvas-body::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.22);
            border-radius: 999px;
        }

        .app-sidebar-brand {
            padding: 0.5rem 0.75rem 1.5rem;
            border-bottom: 1px solid var(--app-sidebar-border);
            margin-bottom: 1.25rem;
        }

        .app-sidebar-title {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .app-sidebar-subtitle {
            color: rgba(255, 255, 255, 0.55);
        }

        .app-sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .app-sidebar-scroll {
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.22) transparent;
        }

        .app-sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.85rem 1rem;
            border-radius: 1rem;
            color: var(--app-sidebar-text);
            text-decoration: none;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .app-sidebar-link i {
            width: 1.2rem;
            text-align: center;
            font-size: 0.95rem;
        }

        .app-sidebar-link:hover {
            background: var(--app-sidebar-hover);
            color: var(--app-sidebar-text-strong);
        }

        .app-sidebar-link.active {
            background: var(--app-sidebar-active);
            color: #fff;
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.28);
        }

        .app-sidebar-user-card {
            background: var(--app-card-bg);
            border: 1px solid var(--app-sidebar-border);
            border-radius: 1.2rem;
            padding: 1rem;
            backdrop-filter: blur(12px);
        }

        .app-sidebar-user-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .app-sidebar-user-name {
            color: #fff;
            font-weight: 600;
        }

        .app-sidebar-user-role {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            margin-top: 0.2rem;
        }

        .app-sidebar-settings-link {
            width: 2.35rem;
            height: 2.35rem;
            flex-shrink: 0;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            transition: all 0.2s ease;
        }

        .app-sidebar-settings-link:hover {
            background: rgba(255, 255, 255, 0.16);
            color: #fff;
            transform: rotate(20deg);
        }

        .app-content {
            margin-left: var(--app-sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .app-main {
            flex: 1;
            padding: 2rem;
        }

        .app-page {
            max-width: 100%;
        }

        .app-mobile-topbar {
            position: sticky;
            top: 0;
            z-index: 1035;
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(17, 24, 39, 0.08);
        }

        .app-mobile-toggle {
            width: 44px;
            height: 44px;
            border-radius: 0.9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .app-mobile-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            font-weight: 700;
            color: #111827;
            text-decoration: none;
        }

        .app-sidebar-offcanvas {
            background: var(--app-sidebar-bg);
        }

        .offcanvas-body {
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.22) transparent;
        }

        @media (max-width: 991.98px) {
            .app-content {
                margin-left: 0;
            }

            .app-main {
                padding: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <?php if (isset($_SESSION['id_usuario'])): ?>
            <?php require __DIR__ . '/sidebar.php'; ?>
        <?php endif; ?>

        <div class="app-content">
            <main class="app-main">
                <div class="app-page">
<script>
    function confirmarReinicio() {
        if (confirm("ESTAS SEGURO? Se borraran todas las ventas, productos y stock. Esta accion no se puede deshacer.")) {
            let clave = prompt("Para confirmar, escribe la palabra: BORRAR");
            if (clave === "BORRAR") {
                window.location.href = "/mi_sistema/modules/config/reset_db.php";
            } else {
                alert("Confirmacion incorrecta. No se realizaron cambios.");
            }
        }
    }
</script>
