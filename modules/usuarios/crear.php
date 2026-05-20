<?php
session_start();
require_once '../../config/database.php';

$errores = [];
$datos = ['nombre_usuario' => '', 'email_usuario' => '', 'estado_usuario' => 'activo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y sanear entradas
    $datos['nombre_usuario'] = trim($_POST['nombre_usuario'] ?? '');
    $datos['email_usuario']  = trim($_POST['email_usuario']  ?? '');
    $datos['estado_usuario'] = trim($_POST['estado_usuario'] ?? 'activo');
    $password                = $_POST['password_usuario']    ?? '';
    $password_confirm        = $_POST['password_confirmar']  ?? '';

    // Validaciones
    if ($datos['nombre_usuario'] === '') {
        $errores['nombre_usuario'] = 'El nombre de usuario es obligatorio.';
    } elseif (strlen($datos['nombre_usuario']) < 3) {
        $errores['nombre_usuario'] = 'Debe tener al menos 3 caracteres.';
    }

    if ($datos['email_usuario'] === '') {
        $errores['email_usuario'] = 'El email es obligatorio.';
    } elseif (!filter_var($datos['email_usuario'], FILTER_VALIDATE_EMAIL)) {
        $errores['email_usuario'] = 'El formato del email no es válido.';
    } else {
        // Verificar unicidad del email
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios_sistema WHERE email_usuario = ?");
        $stmt->execute([$datos['email_usuario']]);
        if ((int)$stmt->fetchColumn() > 0) {
            $errores['email_usuario'] = 'Este email ya está registrado.';
        }
    }

    if ($password === '') {
        $errores['password_usuario'] = 'La contraseña es obligatoria.';
    } elseif (strlen($password) < 8) {
        $errores['password_usuario'] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($password !== $password_confirm) {
        $errores['password_confirmar'] = 'Las contraseñas no coinciden.';
    }

    if (!in_array($datos['estado_usuario'], ['activo', 'inactivo'], true)) {
        $datos['estado_usuario'] = 'activo';
    }

    // Si no hay errores, insertar
    if (empty($errores)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        try {
            $stmt = $pdo->prepare("
                INSERT INTO usuarios_sistema (nombre_usuario, email_usuario, password_usuario, estado_usuario)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $datos['nombre_usuario'],
                $datos['email_usuario'],
                $hash,
                $datos['estado_usuario']
            ]);
            $_SESSION['mensaje']      = 'Usuario creado correctamente.';
            $_SESSION['tipo_mensaje'] = 'exito';
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $errores['general'] = 'Error al guardar el usuario. Intente nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #0f1117;
            --surface: #1a1d27;
            --border: #2a2d3a;
            --accent: #6c63ff;
            --accent-light: #8b85ff;
            --text: #e2e4ed;
            --text-muted: #7a7d8e;
            --danger: #ff4d6d;
            --success: #00c48c;
            --radius: 10px;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .header h1 { font-size: 1.5rem; font-weight: 700; }
        .header h1 span { color: var(--accent); }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.55rem 1.2rem;
            border: none;
            border-radius: var(--radius);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: opacity 0.2s, transform 0.1s;
        }
        .btn:active { transform: scale(0.97); }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { opacity: 0.88; }
        .btn-outline { background: transparent; color: var(--text-muted); border: 1px solid var(--border); }
        .btn-outline:hover { border-color: var(--accent); color: var(--accent-light); }

        .alert-error-general {
            padding: 0.85rem 1.2rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            background: rgba(255,77,109,0.12);
            border: 1px solid rgba(255,77,109,0.3);
            color: var(--danger);
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 2rem;
            max-width: 600px;
        }

        .form-section-title {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-muted);
            margin-bottom: 1.25rem;
            padding-bottom: 0.6rem;
            border-bottom: 1px solid var(--border);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.45rem;
            color: var(--text);
        }

        .required { color: var(--accent); margin-left: 2px; }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            padding: 0.6rem 0.9rem;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s;
        }
        input:focus, select:focus { border-color: var(--accent); }
        input.input-error, select.input-error { border-color: var(--danger); }

        .field-error {
            font-size: 0.8rem;
            color: var(--danger);
            margin-top: 0.35rem;
        }

        .form-hint {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 0.35rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.75rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border);
        }

        @media (max-width: 520px) {
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Crear <span>Usuario</span></h1>
    <a href="index.php" class="btn btn-outline">← Volver al listado</a>
</div>

<?php if (!empty($errores['general'])): ?>
    <div class="alert-error-general"><?= htmlspecialchars($errores['general']) ?></div>
<?php endif; ?>

<div class="card">
    <p class="form-section-title">Información de la cuenta</p>

    <form method="POST" action="crear.php" novalidate>

        <div class="form-group">
            <label for="nombre_usuario">Nombre de usuario <span class="required">*</span></label>
            <input
                type="text"
                id="nombre_usuario"
                name="nombre_usuario"
                value="<?= htmlspecialchars($datos['nombre_usuario']) ?>"
                class="<?= isset($errores['nombre_usuario']) ? 'input-error' : '' ?>"
                autocomplete="username"
                maxlength="80"
            >
            <?php if (!empty($errores['nombre_usuario'])): ?>
                <span class="field-error"><?= htmlspecialchars($errores['nombre_usuario']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="email_usuario">Email <span class="required">*</span></label>
            <input
                type="email"
                id="email_usuario"
                name="email_usuario"
                value="<?= htmlspecialchars($datos['email_usuario']) ?>"
                class="<?= isset($errores['email_usuario']) ? 'input-error' : '' ?>"
                autocomplete="email"
                maxlength="150"
            >
            <?php if (!empty($errores['email_usuario'])): ?>
                <span class="field-error"><?= htmlspecialchars($errores['email_usuario']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="password_usuario">Contraseña <span class="required">*</span></label>
                <input
                    type="password"
                    id="password_usuario"
                    name="password_usuario"
                    class="<?= isset($errores['password_usuario']) ? 'input-error' : '' ?>"
                    autocomplete="new-password"
                >
                <span class="form-hint">Mínimo 8 caracteres.</span>
                <?php if (!empty($errores['password_usuario'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errores['password_usuario']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password_confirmar">Confirmar contraseña <span class="required">*</span></label>
                <input
                    type="password"
                    id="password_confirmar"
                    name="password_confirmar"
                    class="<?= isset($errores['password_confirmar']) ? 'input-error' : '' ?>"
                    autocomplete="new-password"
                >
                <?php if (!empty($errores['password_confirmar'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errores['password_confirmar']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="estado_usuario">Estado <span class="required">*</span></label>
            <select id="estado_usuario" name="estado_usuario">
                <option value="activo"   <?= $datos['estado_usuario'] === 'activo'   ? 'selected' : '' ?>>Activo</option>
                <option value="inactivo" <?= $datos['estado_usuario'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Crear usuario</button>
            <a href="index.php" class="btn btn-outline">Cancelar</a>
        </div>

    </form>
</div>

</body>
</html>
