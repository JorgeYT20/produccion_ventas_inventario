<?php
require_once __DIR__ . '/../../config/database.php';

// Dentro de login.php, antes de $_SESSION['id_usuario'] = ...
session_unset(); // Borra todas las variables de sesión actuales
session_destroy(); // Destruye la sesión anterior
session_start(); // Inicia una limpia para el nuevo usuario

if (isset($_SESSION['id_usuario'])) {
    redirigir('/mi_sistema/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Por favor, completa ambos campos.';
    } else {
        // MODIFICADO: Se añade 'moneda' a la consulta SQL
        $stmt = $conexion->prepare("SELECT id_usuario, nombre_completo, password, id_rol, moneda FROM usuarios WHERE username = ? AND activo = 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $usuario = $resultado->fetch_assoc();
            
            if (password_verify($password, $usuario['password'])) {
                // Iniciar sesión
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nombre_usuario'] = $usuario['nombre_completo'];
                $_SESSION['id_rol'] = $usuario['id_rol'];
                $_SESSION['usuario'] = [
                    'id_usuario' => (int)$usuario['id_usuario'],
                    'nombre_completo' => $usuario['nombre_completo'],
                    'id_rol' => (int)$usuario['id_rol']
                ];
                
                // NUEVO: Guardar la moneda del usuario en la sesión
                $_SESSION['moneda_usuario'] = $usuario['moneda'];

                // Cargar los permisos del rol en la sesión
                $stmt_permisos = $conexion->prepare("SELECT p.nombre_permiso FROM rol_permiso rp JOIN permisos p ON rp.id_permiso = p.id_permiso WHERE rp.id_rol = ?");
                $stmt_permisos->bind_param("i", $usuario['id_rol']);
                $stmt_permisos->execute();
                $resultado_permisos = $stmt_permisos->get_result();
                $permisos = [];
                while ($fila = $resultado_permisos->fetch_assoc()) {
                    $permisos[] = $fila['nombre_permiso'];
                }
                $_SESSION['permisos'] = $permisos;
                $stmt_permisos->close();
                
                redirigir('/mi_sistema/index.php');

            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Mi Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        body { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            background: #f0f2f5; /* Fondo gris muy claro */
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 15px;
        }

        .login-card { 
            border: none;
            border-radius: 20px;
            overflow: hidden;
            background: #ffffff;
        }

        /* Sección del Logo */
        .logo-section {
            background: var(--primary-gradient);
            padding: 30px 30px 10px;
            
            text-align: center;
            color: white;
        }

        .logo-placeholder {
            width: 200px;
            height: 100px;
            background: rgb(255, 255, 255);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 40px;
            backdrop-filter: blur(5px);
            border: 2px solid rgba(255, 238, 0, 0.62);
        }

        .login-card .card-body {
            padding: 40px 30px;
        }

        .form-label {
            font-weight: 600;
            color: #4a5568;
            font-size: 0.9rem;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(118, 75, 162, 0.4);
            background: var(--primary-gradient);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 10px;
            font-size: 0.9rem;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="card login-card shadow-lg">
            
            <div class="logo-section">
                
                <h3 class="m-0">Bienvenido</h3>
                <p class="small opacity-75">Ingresa tus credenciales</p>
            </div>

            <div class="card-body">
                <?php if (isset($error) && $error): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <div><?php echo $error; ?></div>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="mb-4">
                        <label for="username" class="form-label">Usuario</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 rounded-start-2">
                                <i class="fas fa-user text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" name="username" placeholder="Tu nombre de usuario" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 rounded-start-2">
                                <i class="fas fa-lock text-muted"></i>
                            </span>
                            <input type="password" class="form-control border-start-0" name="password" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="d-grid shadow-sm">
                        <button type="submit" class="btn btn-primary">
                            INGRESAR <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        
        <div class="text-center mt-4">
            <p class="text-muted mb-1" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                &copy; 2026 <strong>Sistema de Ventas e inventario</strong>. Todos los derechos reservados.
            </p>
            <p class="small" style="color: #adb5bd; font-weight: 500;">
                Desarrollado por <span style="color: var(--gold); border-bottom: 1px solid rgba(216, 184, 113, 0.3);">Jorge Yataco</span>
            </p>
        </div>
    </div>

</body>
</html>
