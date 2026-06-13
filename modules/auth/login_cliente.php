<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

if (isset($_SESSION['id_cliente'])) {
    header('Location: ../../client/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario    = trim($_POST['usuario'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');

    if ($usuario && $contrasena) {
        $pdo  = conectar();
        $stmt = $pdo->prepare("
            SELECT uc.id_usuario_cliente, uc.password_hash,
                   uc.estado, c.id_cliente, c.nombre, c.email
            FROM usuarios_clientes uc
            JOIN clientes c ON c.id_cliente = uc.id_cliente
            WHERE uc.nombre_usuario = ?
        ");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();

        if ($user && $user['estado'] === 'activo'
            && password_verify($contrasena, $user['password_hash'])) {

            $_SESSION['id_cliente']         = $user['id_cliente'];
            $_SESSION['id_usuario_cliente'] = $user['id_usuario_cliente'];
            $_SESSION['nombre_cliente']     = $user['nombre'];
            $_SESSION['email_cliente']      = $user['email'];
            $_SESSION['tipo_sesion']        = 'cliente';

            header('Location: ../../client/index.php');
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
    <title>Portal Clientes — Vértice Constructora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #1a2535 0%, #2C3E50 50%, #1a3a2a 100%);
            overflow: hidden;
        }

        /* Fondo con patrón */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                radial-gradient(circle, rgba(39,174,96,0.10) 1px, transparent 1px);
            background-size: 38px 38px;
            pointer-events: none;
        }

        .login-card {
            background: rgba(255,255,255,0.98);
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

        /* Badge "Portal Cliente" */
        .portal-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #D5F5E3;
            color: #1E8449;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 99px;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }
        .portal-badge::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #27AE60;
            display: inline-block;
        }

        .brand-logo {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            background: linear-gradient(135deg, #27AE60, #2ECC71);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: 900;
            color: #fff;
            margin: 0 auto 14px;
            box-shadow: 0 8px 24px rgba(39,174,96,0.35);
        }

        .brand-name {
            text-align: center;
            font-size: 20px;
            font-weight: 800;
            color: #1C1C1E;
        }

        .brand-sub {
            text-align: center;
            font-size: 11px;
            color: #95A5A6;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 28px;
            margin-top: 3px;
        }

        .form-label {
            font-size: 11.5px;
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
            background: #F8F9FA;
            transition: border-color 0.18s, box-shadow 0.18s;
        }

        .form-control:focus {
            border-color: #27AE60;
            box-shadow: 0 0 0 3px rgba(39,174,96,0.12);
            background: #fff;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, #27AE60, #1E8449);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 8px;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 4px 16px rgba(39,174,96,0.35);
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(39,174,96,0.45);
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

        .divider {
            height: 1px;
            background: #F0F2F4;
            margin: 20px 0;
        }

        .link-small {
            font-size: 12.5px;
            color: #95A5A6;
            text-decoration: none;
            display: block;
            text-align: center;
            margin-top: 10px;
            transition: color 0.15s;
        }
        .link-small:hover { color: #27AE60; }

        .switch-link {
            text-align: center;
            font-size: 12.5px;
            color: #95A5A6;
            margin-top: 20px;
        }
        .switch-link a {
            color: #3498DB;
            font-weight: 700;
            text-decoration: none;
        }
        .switch-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="login-card">

    <div style="text-align:center;">
        <div class="portal-badge"> Cliente</div>
    </div>

    <div class="brand-logo">EC</div>
    <div class="brand-name">Empresa Constructora</div>
    <div class="brand-sub">Sistema de Usuarios</div>

    <?php if ($error): ?>
    <div class="error-box">
        <span>⚠</span> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" autocomplete="on">
        <div class="mb-3">
            <label class="form-label" for="usuario">Usuario</label>
            <input class="form-control"
                   type="text"
                   id="usuario"
                   name="usuario"
                   placeholder="Tu usuario de acceso"
                   autofocus
                   required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="contrasena">Contraseña</label>
            <input class="form-control"
                   type="password"
                   id="contrasena"
                   name="contrasena"
                   placeholder="Contraseña"
                   required>
        </div>
        <button class="btn-login" type="submit">
            Ingresar al portal
        </button>
    </form>

    <div class="divider"></div>

    <a href="recuperar_cliente.php" class="link-small">
        ¿Olvidaste tu contraseña?
    </a>
    <a href="register_cliente.php" class="link-small">
        ¿Primera vez? Crear cuenta
    </a>
    
    <a href="inicio.php" class="link-small" style="margin-top:6px">← Volver al inicio</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>