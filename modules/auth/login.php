<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

if (isset($_SESSION['id_usuario'])) {
    header('Location: ../../modules/dashboard/dashboard.php');
    registrarAccion(LOG_LOGIN);  // antes del header Location
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario    = trim($_POST['usuario'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');

    if ($usuario && $contrasena) {
        $pdo  = conectar();
        $stmt = $pdo->prepare("
            SELECT us.id_usuario_sistema, us.password_hash, us.estado, e.nombre
            FROM usuarios_sistema us
            JOIN empleados e ON e.id_empleado = us.id_empleado
            WHERE us.nombre_usuario = ?
        ");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();

        if ($user && $user['estado'] === 'activo' && password_verify($contrasena, $user['password_hash'])) {

            $stmt2 = $pdo->prepare("
                SELECT DISTINCT p.nombre_permiso
                FROM usuarios_roles ur
                JOIN roles_permisos rp ON rp.id_rol = ur.id_rol
                JOIN permisos p ON p.id_permiso = rp.id_permiso
                WHERE ur.id_usuario_sistema = ?
            ");
            $stmt2->execute([$user['id_usuario_sistema']]);
            $permisos = $stmt2->fetchAll(PDO::FETCH_COLUMN);

            $stmt3 = $pdo->prepare("
                SELECT r.nombre_rol
                FROM usuarios_roles ur
                JOIN roles r ON r.id_rol = ur.id_rol
                WHERE ur.id_usuario_sistema = ?
            ");
            $stmt3->execute([$user['id_usuario_sistema']]);
            $roles = $stmt3->fetchAll(PDO::FETCH_COLUMN);

            $_SESSION['id_usuario']  = $user['id_usuario_sistema'];
            $_SESSION['nombre']      = $user['nombre'];
            $_SESSION['permisos']    = $permisos;
            $_SESSION['roles']       = $roles;
            $_SESSION['tipo_sesion'] = 'empleado';
            $_SESSION['show_splash'] = true; // ← flag para splash

            header('Location: ../../modules/dashboard/dashboard.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos';
        }
    } else {
        $error = 'Completa todos los campos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vértice Constructora — Iniciar sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2C3E50 0%, #1a2535 60%, #0d1520 100%);
            font-family: 'Segoe UI', system-ui, sans-serif;
            overflow: hidden;
        }

        /* Partículas de fondo */
        .bg-dots {
            position: fixed;
            inset: 0;
            background-image:
                radial-gradient(circle, rgba(52,152,219,0.12) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        .login-card {
            background: rgba(255,255,255,0.97);
            border-radius: 20px;
            padding: 44px 40px 36px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 32px 80px rgba(0,0,0,0.35);
            animation: cardIn 0.6s cubic-bezier(.22,1,.36,1) both;
            position: relative;
            z-index: 2;
        }

        @keyframes cardIn {
            from { transform: translateY(40px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        .brand-logo {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            background: linear-gradient(135deg, #3498DB, #E67E22);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: 900;
            color: #fff;
            margin: 0 auto 16px;
            box-shadow: 0 8px 24px rgba(52,152,219,0.35);
        }

        .brand-name {
            text-align: center;
            font-size: 20px;
            font-weight: 800;
            color: #1C1C1E;
            letter-spacing: 0.5px;
        }

        .brand-sub {
            text-align: center;
            font-size: 11px;
            color: #95A5A6;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 32px;
            margin-top: 3px;
        }

        .form-label {
            font-size: 12px;
            font-weight: 700;
            color: #7F8C8D;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .form-control {
            border: 1.5px solid #E8ECF0;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 14px;
            transition: border-color 0.18s, box-shadow 0.18s;
            background: #F8F9FA;
        }

        .form-control:focus {
            border-color: #3498DB;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.12);
            background: #fff;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, #3498DB, #2980B9);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 8px;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 4px 16px rgba(52,152,219,0.35);
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(52,152,219,0.45);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-box {
            background: #FDECEA;
            border: 1px solid #F5C6CB;
            color: #721C24;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .link-small {
            font-size: 12.5px;
            color: #95A5A6;
            text-decoration: none;
            display: block;
            text-align: center;
            margin-top: 18px;
            transition: color 0.15s;
        }

        .link-small:hover { color: #3498DB; }

        .divider {
            height: 1px;
            background: #F0F2F4;
            margin: 20px 0;
        }
    </style>
</head>
<body>

<div class="bg-dots"></div>

<div class="login-card">
    <div class="brand-logo">EC</div>
    <div class="brand-name">Empresa Constructora</div>
    <div class="brand-sub">Sistema de Gestión</div>

    <?php if ($error): ?>
        <div class="error-box">
            <span>⚠</span> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="on">
        <div class="mb-3">
            <label class="form-label" for="usuario">Usuario</label>
            <input
                class="form-control"
                type="text"
                id="usuario"
                name="usuario"
                placeholder="Tu nombre de usuario"
                autofocus
                required
            >
        </div>

        <div class="mb-3">
            <label class="form-label" for="contrasena">Contraseña</label>
            <input
                class="form-control"
                type="password"
                id="contrasena"
                name="contrasena"
                placeholder="Contraseña"
                required
            >
        </div>

        <button class="btn-login" type="submit">Iniciar sesión</button>
    </form>

    <div class="divider"></div>

    <a href="recuperar.php" class="link-small">¿Olvidaste tu contraseña?</a>
    <a href="inicio.php" class="link-small" style="margin-top:6px">← Volver al inicio</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>