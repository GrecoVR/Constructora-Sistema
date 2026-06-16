<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

$error   = '';
$exito   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario    = trim($_POST['usuario'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');
    $id_empleado = intval($_POST['id_empleado'] ?? 0);

    if ($usuario && $contrasena && $id_empleado) {
        $pdo = conectar();

        $stmt = $pdo->prepare("SELECT id_usuario_sistema FROM usuarios_sistema WHERE nombre_usuario = ?");
        $stmt->execute([$usuario]);

        if ($stmt->fetch()) {
            $error = 'Ese nombre de usuario ya existe';
        } else {
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            $stmt2 = $pdo->prepare("
                INSERT INTO usuarios_sistema (id_empleado, nombre_usuario, password_hash, estado)
                VALUES (?, ?, ?, 'activo')
            ");
            $stmt2->execute([$id_empleado, $usuario, $hash]);
            $exito = 'Cuenta creada correctamente. Ya puedes iniciar sesión.';
        }
    } else {
        $error = 'Completa todos los campos';
    }
}

$pdo       = conectar();
$empleados = $pdo->query("
    SELECT e.id_empleado, e.nombre
    FROM empleados e
    WHERE e.id_empleado NOT IN (SELECT id_empleado FROM usuarios_sistema)
    ORDER BY e.nombre ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta — Vértice</title>
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
        .form-control,
        .form-select {
            border: 1.5px solid #E8ECF0;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 14px;
            background: #F8F9FA;
            transition: border-color 0.18s, box-shadow 0.18s;
        }
        .form-control:focus,
        .form-select:focus {
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
        .alert-error   { background:#FDECEA; border:1px solid #F5C6CB; color:#721C24; }
        .alert-success { background:#D4EDDA; border:1px solid #C3E6CB; color:#155724; }

        /* Input password con ojo */
        .pass-wrap { position: relative; }
        .pass-wrap .btn-eye {
            position: absolute;
            right: 10px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: #95A5A6; cursor: pointer;
            font-size: 16px; padding: 0;
        }

        /* Strength bar */
        .strength-track {
            background: #E8ECF0;
            border-radius: 99px;
            height: 4px;
            margin-top: 8px;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            border-radius: 99px;
            width: 0;
            transition: width 0.3s, background 0.3s;
        }
        .strength-label {
            font-size: 11px;
            color: #95A5A6;
            margin-top: 4px;
        }

        .divider { height: 1px; background: #F0F2F4; margin: 20px 0; }

        .link-small {
            font-size: 12.5px;
            color: #95A5A6;
            text-decoration: none;
            display: block;
            text-align: center;
            margin-top: 10px;
            transition: color 0.15s;
        }
        .link-small:hover { color: #3498DB; }

        /* Vacío */
        .empty-state {
            text-align: center;
            padding: 10px 0 4px;
            color: #7F8C8D;
        }
        .empty-state i {
            font-size: 32px;
            display: block;
            margin-bottom: 10px;
            opacity: 0.35;
        }
    </style>
</head>
<body>

<div class="auth-card">

    <div class="brand-logo">EC</div>
    <div class="brand-name">Empresa Constructora</div>
    <div class="brand-sub">Sistema de Gestión</div>

    <?php if ($error): ?>
    <div class="alert-box alert-error">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if ($exito): ?>
    <div class="alert-box alert-success">
        <i class="bi bi-check-circle-fill"></i>
        <?= htmlspecialchars($exito) ?>
    </div>
    <?php endif; ?>

    <?php if (empty($empleados) && !$exito): ?>
    <div class="empty-state">
        <i class="bi bi-people"></i>
        <p style="font-size:13.5px;font-weight:600;margin-bottom:6px;">
            Sin empleados disponibles
        </p>
        <p style="font-size:13px;">
            Todos los empleados ya tienen cuenta registrada o no hay
            empleados en el sistema.
        </p>
    </div>

    <?php elseif (!$exito): ?>
    <form method="POST" autocomplete="off">

        <div class="mb-3">
            <label class="form-label">Empleado</label>
            <select class="form-select" name="id_empleado" required>
                <option value="">— Selecciona un empleado —</option>
                <?php foreach ($empleados as $emp): ?>
                <option value="<?= $emp['id_empleado'] ?>">
                    <?= htmlspecialchars($emp['nombre']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Nombre de usuario</label>
            <input class="form-control"
                   type="text"
                   name="usuario"
                   placeholder="Introduce un nombre de usuario"
                   autocomplete="off"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <div class="pass-wrap">
                <input class="form-control"
                       type="password"
                       name="contrasena"
                       id="pass-field"
                       placeholder="Mínimo 6 caracteres"
                       oninput="checkStr(this.value)"
                       required>
                <button type="button"
                        class="btn-eye"
                        onclick="togglePass('pass-field', this)">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <div class="strength-track">
                <div class="strength-fill" id="str-bar"></div>
            </div>
            <div class="strength-label" id="str-label"></div>
        </div>

        <button class="btn-primary-custom" type="submit">
            <i class="bi bi-person-plus me-2"></i>Crear cuenta
        </button>
    </form>
    <?php endif; ?>

    <?php if ($exito): ?>
    <div style="text-align:center;padding:8px 0;">
        <div style="width:60px;height:60px;border-radius:50%;
                    background:linear-gradient(135deg,#3498DB,#2980B9);
                    display:flex;align-items:center;justify-content:center;
                    font-size:26px;color:#fff;margin:0 auto 14px;
                    animation:popIn .5s cubic-bezier(.22,1,.36,1) both;">
            <i class="bi bi-check-lg"></i>
        </div>
        <style>@keyframes popIn{from{transform:scale(.5);opacity:0}to{transform:scale(1);opacity:1}}</style>
        <a href="login.php" class="btn-primary-custom"
           style="display:block;text-decoration:none;text-align:center;margin-top:4px;">
            <i class="bi bi-box-arrow-in-right me-2"></i>Ir al inicio de sesión
        </a>
    </div>
    <?php endif; ?>

    <div class="divider"></div>
    <a href="login.php" class="link-small">
        <i class="bi bi-arrow-left me-1"></i>Volver al inicio de sesión
    </a>

</div>

<script>
function togglePass(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

function checkStr(val) {
    const bar = document.getElementById('str-bar');
    const lbl = document.getElementById('str-label');
    let s = 0;
    if (val.length >= 6)          s++;
    if (val.length >= 10)         s++;
    if (/[A-Z]/.test(val))        s++;
    if (/[0-9]/.test(val))        s++;
    if (/[^A-Za-z0-9]/.test(val)) s++;
    const L = [
        { w:'0%',   bg:'transparent', t:'' },
        { w:'25%',  bg:'#E74C3C',     t:'Muy débil' },
        { w:'50%',  bg:'#E67E22',     t:'Débil' },
        { w:'70%',  bg:'#F39C12',     t:'Aceptable' },
        { w:'85%',  bg:'#27AE60',     t:'Fuerte' },
        { w:'100%', bg:'#1E8449',     t:'Muy fuerte' },
    ];
    bar.style.width      = L[s].w;
    bar.style.background = L[s].bg;
    lbl.textContent      = L[s].t;
    lbl.style.color      = L[s].bg;
}
</script>
</body>
</html>