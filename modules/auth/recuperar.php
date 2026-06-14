<?php
require_once '../../config/session.php';
require_once '../../config/database.php';


$paso    = 1;
$error   = '';
$success = '';
$pdo     = conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Paso 1: Buscar usuario
    if (isset($_POST['buscar'])) {

        $usuario = trim($_POST['usuario'] ?? '');

        if ($usuario) {

            $stmt = $pdo->prepare("
                SELECT us.id_usuario_sistema,
                       us.nombre_usuario,
                       us.password_hash,
                       us.estado,
                       e.nombre
                FROM usuarios_sistema us
                JOIN empleados e
                    ON e.id_empleado = us.id_empleado
                WHERE us.nombre_usuario = ?
            ");

            $stmt->execute([$usuario]);

            $user = $stmt->fetch();

            if ($user) {

                $_SESSION['reset_userid'] = $user['id_usuario_sistema'];
                $_SESSION['reset_name']   = $user['nombre'];

                $paso = 2;

            } else {

                $error = 'No se encontró ese usuario';
                $paso  = 1;

            }

        } else {

            $error = 'Ingresa tu nombre de usuario';
            $paso  = 1;

        }
    }

    // Paso 2: nueva contraseña
    elseif (isset($_POST['guardar'])) {

        $nueva    = $_POST['nueva_pass'] ?? '';
        $confirma = $_POST['confirma_pass'] ?? '';

        if (strlen($nueva) < 6) {

            $error = 'La contraseña debe tener al menos 6 caracteres.';
            $paso  = 2;

        } elseif ($nueva !== $confirma) {

            $error = 'Las contraseñas no coinciden.';
            $paso  = 2;

        } elseif (!isset($_SESSION['reset_userid'])) {

            $error = 'Sesión expirada. Inicia el proceso nuevamente.';
            $paso  = 1;

        } else {

            $hash = password_hash($nueva, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                UPDATE usuarios_sistema
                SET password_hash = ?
                WHERE id_usuario_sistema = ?
            ");

            $stmt->execute([
                $hash,
                $_SESSION['reset_userid']
            ]);

            unset(
                $_SESSION['reset_token'],
                $_SESSION['reset_userid'],
                $_SESSION['reset_name']
            );

            $success = 'Contraseña actualizada correctamente.';
            $paso    = 3;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña — Vértice</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
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

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                radial-gradient(circle, rgba(52,152,219,0.10) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        .auth-card {
            background: rgba(255,255,255,0.97);
            border-radius: 20px;
            padding: 44px 40px 36px;
            width: 100%;
            max-width: 420px;
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
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: linear-gradient(135deg, #3498DB, #E67E22);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 900;
            color: #fff;
            margin: 0 auto 14px;
            box-shadow: 0 8px 24px rgba(52,152,219,0.3);
        }

        .brand-name {
            text-align: center;
            font-size: 18px;
            font-weight: 800;
            color: #1C1C1E;
        }

        .brand-sub {
            text-align: center;
            font-size: 11px;
            color: #95A5A6;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 24px;
            margin-top: 3px;
        }

        /* Pasos visuales */
        .steps-row {
            display: flex;
            align-items: center;
            gap: 0;
            margin-bottom: 28px;
        }
        .step-dot {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #E8ECF0;
            color: #95A5A6;
            font-size: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: background 0.3s, color 0.3s;
        }
        .step-dot.done {
            background: #27AE60;
            color: #fff;
        }
        .step-dot.active {
            background: #3498DB;
            color: #fff;
            box-shadow: 0 0 0 4px rgba(52,152,219,0.18);
        }
        .step-line {
            flex: 1;
            height: 2px;
            background: #E8ECF0;
            margin: 0 4px;
            transition: background 0.3s;
        }
        .step-line.done { background: #27AE60; }

        .step-label {
            font-size: 10px;
            color: #95A5A6;
            text-align: center;
            margin-top: 5px;
            font-weight: 600;
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
            border-color: #3498DB;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.12);
            background: #fff;
            outline: none;
        }

        .btn-primary-custom {
            width: 100%;
            padding: 12px;
            border-radius: 11px;
            border: none;
            background: linear-gradient(135deg, #3498DB, #2980B9);
            color: #fff;
            font-size: 14.5px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 6px;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 4px 16px rgba(52,152,219,0.3);
        }
        .btn-primary-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(52,152,219,0.4);
        }

        .alert-box {
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-error {
            background: #FDECEA;
            border: 1px solid #F5C6CB;
            color: #721C24;
        }
        .alert-success {
            background: #D4EDDA;
            border: 1px solid #C3E6CB;
            color: #155724;
        }

        .link-small {
            font-size: 12.5px;
            color: #95A5A6;
            text-decoration: none;
            display: block;
            text-align: center;
            margin-top: 14px;
            transition: color 0.15s;
        }
        .link-small:hover { color: #3498DB; }

        /* Password strength */
        .strength-bar {
            height: 4px;
            border-radius: 99px;
            margin-top: 6px;
            transition: width 0.3s, background 0.3s;
            width: 0;
        }

        /* Success state */
        .success-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #27AE60, #2ECC71);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #fff;
            margin: 0 auto 16px;
            animation: popIn 0.5s cubic-bezier(.22,1,.36,1) both;
        }
        @keyframes popIn {
            from { transform: scale(0.5); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }

        .divider {
            height: 1px;
            background: #F0F2F4;
            margin: 20px 0;
        }
    </style>
</head>
<body>

<div class="auth-card">

    <div class="brand-logo">EC</div>
    <div class="brand-name">Empresa Constructora</div>
    <div class="brand-sub">Sistema de Gestión</div>

    <!-- Indicador de pasos -->
    <?php if ($paso < 3): ?>
    <div>
        <div class="steps-row">
            <div class="step-dot <?= $paso >= 1 ? ($paso > 1 ? 'done' : 'active') : '' ?>">
                <?= $paso > 1 ? '✓' : '1' ?>
            </div>
            <div class="step-line <?= $paso > 1 ? 'done' : '' ?>"></div>
            <div class="step-dot <?= $paso >= 2 ? ($paso > 2 ? 'done' : 'active') : '' ?>">
                <?= $paso > 2 ? '✓' : '2' ?>
            </div>
            <div class="step-line"></div>
            <div class="step-dot <?= $paso >= 3 ? 'active' : '' ?>">3</div>
        </div>
        <div style="display:flex;justify-content:space-between;
                    font-size:10px;color:#95A5A6;font-weight:600;margin-bottom:22px;">
            <span>Verificar nombre de usuario</span>
            <span>Nueva contraseña</span>
            <span>Listo</span>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert-box alert-error">
        <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if ($success && $paso !== 3): ?>
    <div class="alert-box alert-success">
        <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <!-- ── PASO 1: Usuario ── -->
    <?php if ($paso === 1): ?>
        <p style="font-size:13.5px;color:#7F8C8D;margin-bottom:20px;text-align:center;">
            Ingresa tu nombre de usuario y te ayudaremos a restablecer tu contraseña.
        </p>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Nombre de Usuario</label>
                <input class="form-control"
                       type="text"
                       name="usuario"
                       placeholder="Nombre de usuario"
                       required>
            </div>
            <button class="btn-primary-custom" type="submit" name="buscar">
                Verificar nombre de usuario
            </button>
        </form>

    <!-- ── PASO 2: Nueva contraseña ── -->
    <?php elseif ($paso === 2): ?>
        <p style="font-size:13.5px;color:#7F8C8D;margin-bottom:20px;text-align:center;">
            Hola, <strong><?= htmlspecialchars($_SESSION['reset_name'] ?? '') ?></strong>.
            Ingresa tu nueva contraseña.
        </p>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Nueva contraseña</label>
                <div style="position:relative;">
                    <input class="form-control"
                           type="password"
                           name="nueva_pass"
                           id="nueva_pass"
                           placeholder="Mínimo 6 caracteres"
                           oninput="checkStrength(this.value)"
                           required>
                    <button type="button"
                            onclick="togglePass('nueva_pass', this)"
                            style="position:absolute;right:10px;top:50%;
                                   transform:translateY(-50%);
                                   background:none;border:none;
                                   color:#95A5A6;cursor:pointer;font-size:16px;">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <!-- Barra de fortaleza -->
                <div style="background:#E8ECF0;border-radius:99px;height:4px;
                            margin-top:8px;overflow:hidden;">
                    <div id="strength-bar"
                         style="height:100%;border-radius:99px;width:0;
                                transition:width 0.3s,background 0.3s;"></div>
                </div>
                <div id="strength-label"
                     style="font-size:11px;color:#95A5A6;margin-top:4px;"></div>
            </div>

            <div class="mb-3">
                <label class="form-label">Confirmar contraseña</label>
                <div style="position:relative;">
                    <input class="form-control"
                           type="password"
                           name="confirma_pass"
                           id="confirma_pass"
                           placeholder="Repite la contraseña"
                           required>
                    <button type="button"
                            onclick="togglePass('confirma_pass', this)"
                            style="position:absolute;right:10px;top:50%;
                                   transform:translateY(-50%);
                                   background:none;border:none;
                                   color:#95A5A6;cursor:pointer;font-size:16px;">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <button class="btn-primary-custom" type="submit" name="guardar">
                <i class="bi bi-shield-lock me-2"></i>Guardar contraseña
            </button>
        </form>

    <!-- ── PASO 3: Éxito ── -->
    <?php elseif ($paso === 3): ?>
        <div style="text-align:center;padding:10px 0 6px;">
            <div class="success-icon">
                <i class="bi bi-check-lg"></i>
            </div>
            <h3 style="font-size:17px;font-weight:800;color:#1C1C1E;margin-bottom:8px;">
                Contraseña actualizada
            </h3>
            <p style="font-size:13.5px;color:#7F8C8D;line-height:1.6;">
                Tu contraseña fue cambiada exitosamente.<br>
                Ya puedes iniciar sesión.
            </p>
            <a href="login.php"
               class="btn-primary-custom"
               style="display:block;margin-top:22px;text-decoration:none;text-align:center;">
                <i class="bi bi-box-arrow-in-right me-2"></i>Ir al inicio de sesión
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
function togglePass(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function checkStrength(val) {
    const bar   = document.getElementById('strength-bar');
    const label = document.getElementById('strength-label');
    let score   = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { w: '0%',   bg: 'transparent', text: '' },
        { w: '25%',  bg: '#E74C3C',     text: 'Muy débil' },
        { w: '50%',  bg: '#E67E22',     text: 'Débil' },
        { w: '70%',  bg: '#F39C12',     text: 'Aceptable' },
        { w: '85%',  bg: '#27AE60',     text: 'Fuerte' },
        { w: '100%', bg: '#1E8449',     text: 'Muy fuerte' },
    ];

    bar.style.width      = levels[score].w;
    bar.style.background = levels[score].bg;
    label.textContent    = levels[score].text;
    label.style.color    = levels[score].bg;
}
</script>
</body>
</html>