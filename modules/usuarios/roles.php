<?php
session_start();
require_once '../../config/database.php';

// Validar ID
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['mensaje']      = 'ID de usuario no válido.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: index.php');
    exit;
}

// Cargar usuario
$stmt = $pdo->prepare("SELECT id_usuario_sistema, nombre_usuario, email_usuario, estado_usuario FROM usuarios_sistema WHERE id_usuario_sistema = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    $_SESSION['mensaje']      = 'Usuario no encontrado.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: index.php');
    exit;
}

$mensaje      = null;
$tipo_mensaje = 'info';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Los checkboxes enviados son los roles deseados; los ausentes deben eliminarse
    $roles_seleccionados = isset($_POST['roles']) && is_array($_POST['roles'])
        ? array_map('intval', $_POST['roles'])
        : [];

    try {
        $pdo->beginTransaction();

        // Eliminar todos los roles actuales del usuario
        $stmt_del = $pdo->prepare("DELETE FROM usuarios_roles WHERE id_usuario_sistema = ?");
        $stmt_del->execute([$id]);

        // Insertar los nuevos roles seleccionados
        if (!empty($roles_seleccionados)) {
            $stmt_ins = $pdo->prepare("INSERT INTO usuarios_roles (id_usuario_sistema, id_rol) VALUES (?, ?)");
            foreach ($roles_seleccionados as $id_rol) {
                $stmt_ins->execute([$id, $id_rol]);
            }
        }

        $pdo->commit();
        $mensaje      = 'Roles actualizados correctamente.';
        $tipo_mensaje = 'exito';

    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensaje      = 'Error al actualizar los roles. Intente nuevamente.';
        $tipo_mensaje = 'error';
    }
}

// Cargar todos los roles disponibles
$stmt_roles = $pdo->query("SELECT id_rol, nombre_rol, descripcion FROM roles ORDER BY nombre_rol ASC");
$todos_roles = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

// Cargar los roles asignados al usuario
$stmt_asignados = $pdo->prepare("SELECT id_rol FROM usuarios_roles WHERE id_usuario_sistema = ?");
$stmt_asignados->execute([$id]);
$roles_asignados_raw = $stmt_asignados->fetchAll(PDO::FETCH_COLUMN);
$roles_asignados = array_map('intval', $roles_asignados_raw);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Roles</title>
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
            align-items: flex-start;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header h1 { font-size: 1.5rem; font-weight: 700; }
        .header h1 span { color: var(--accent); }
        .header-meta { font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem; }

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
        .btn-sm { padding: 0.35rem 0.75rem; font-size: 0.8rem; }

        .alert {
            padding: 0.85rem 1.2rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .alert-exito { background: rgba(0,196,140,0.12); border: 1px solid rgba(0,196,140,0.3); color: var(--success); }
        .alert-error  { background: rgba(255,77,109,0.12); border: 1px solid rgba(255,77,109,0.3); color: var(--danger); }

        .layout {
            display: grid;
            grid-template-columns: 1fr 280px;
            gap: 1.5rem;
            align-items: start;
        }

        @media (max-width: 680px) {
            .layout { grid-template-columns: 1fr; }
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.75rem;
        }

        .section-title {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-muted);
            margin-bottom: 1.25rem;
            padding-bottom: 0.6rem;
            border-bottom: 1px solid var(--border);
        }

        .roles-grid {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }

        .role-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            user-select: none;
        }
        .role-item:hover { border-color: var(--accent); background: rgba(108,99,255,0.05); }
        .role-item.checked { border-color: var(--accent); background: rgba(108,99,255,0.08); }

        /* Ocultar el checkbox nativo pero mantenerlo accesible */
        .role-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            margin-top: 1px;
            accent-color: var(--accent);
            cursor: pointer;
        }

        .role-info { flex: 1; }
        .role-name { font-size: 0.9rem; font-weight: 600; }
        .role-desc { font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem; }

        .empty-roles {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Panel lateral: resumen */
        .summary-card { position: sticky; top: 1.5rem; }

        .user-info-block {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .user-info-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
        }
        .user-info-row .label { color: var(--text-muted); }
        .user-info-row .value { font-weight: 600; text-align: right; max-width: 60%; word-break: break-word; }

        .badge {
            display: inline-block;
            padding: 0.2rem 0.65rem;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-activo   { background: rgba(0,196,140,0.15); color: var(--success); }
        .badge-inactivo { background: rgba(255,77,109,0.15); color: var(--danger); }

        .counter {
            text-align: center;
            padding: 1rem;
            border: 1px dashed var(--border);
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .counter-num {
            font-size: 2rem;
            font-weight: 800;
            color: var(--accent);
            line-height: 1;
        }
        .counter-label {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .form-actions { display: flex; flex-direction: column; gap: 0.5rem; }

        .select-controls {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

<div class="header">
    <div>
        <h1>Gestionar <span>Roles</span></h1>
        <p class="header-meta">Usuario #<?= $id ?> · <?= htmlspecialchars($usuario['nombre_usuario']) ?></p>
    </div>
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
        <a href="editar.php?id=<?= $id ?>" class="btn btn-outline btn-sm">Editar usuario</a>
        <a href="index.php" class="btn btn-outline btn-sm">← Volver</a>
    </div>
</div>

<?php if ($mensaje): ?>
    <div class="alert alert-<?= htmlspecialchars($tipo_mensaje) ?>">
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<form method="POST" action="roles.php?id=<?= $id ?>">
<div class="layout">

    <!-- Panel principal: lista de roles -->
    <div class="card">
        <p class="section-title">Roles disponibles</p>

        <div class="select-controls">
            <button type="button" class="btn btn-outline btn-sm" onclick="toggleTodos(true)">Seleccionar todos</button>
            <button type="button" class="btn btn-outline btn-sm" onclick="toggleTodos(false)">Deseleccionar todos</button>
        </div>

        <?php if (empty($todos_roles)): ?>
            <div class="empty-roles">No hay roles disponibles en el sistema.</div>
        <?php else: ?>
            <div class="roles-grid" id="rolesGrid">
                <?php foreach ($todos_roles as $rol): ?>
                    <?php $asignado = in_array((int)$rol['id_rol'], $roles_asignados, true); ?>
                    <label class="role-item <?= $asignado ? 'checked' : '' ?>">
                        <input
                            type="checkbox"
                            name="roles[]"
                            value="<?= (int)$rol['id_rol'] ?>"
                            <?= $asignado ? 'checked' : '' ?>
                            onchange="actualizarEstado(this)"
                        >
                        <div class="role-info">
                            <div class="role-name"><?= htmlspecialchars($rol['nombre_rol']) ?></div>
                            <?php if (!empty($rol['descripcion'])): ?>
                                <div class="role-desc"><?= htmlspecialchars($rol['descripcion']) ?></div>
                            <?php endif; ?>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Panel lateral: resumen -->
    <div class="summary-card">
        <div class="card">
            <p class="section-title">Resumen del usuario</p>

            <div class="user-info-block">
                <div class="user-info-row">
                    <span class="label">Nombre</span>
                    <span class="value"><?= htmlspecialchars($usuario['nombre_usuario']) ?></span>
                </div>
                <div class="user-info-row">
                    <span class="label">Email</span>
                    <span class="value"><?= htmlspecialchars($usuario['email_usuario']) ?></span>
                </div>
                <div class="user-info-row">
                    <span class="label">Estado</span>
                    <span class="value">
                        <?php $estado = strtolower($usuario['estado_usuario']); ?>
                        <span class="badge badge-<?= $estado === 'activo' ? 'activo' : 'inactivo' ?>">
                            <?= ucfirst(htmlspecialchars($usuario['estado_usuario'])) ?>
                        </span>
                    </span>
                </div>
            </div>

            <div class="counter">
                <div class="counter-num" id="contadorRoles"><?= count($roles_asignados) ?></div>
                <div class="counter-label">rol<?= count($roles_asignados) !== 1 ? 'es' : '' ?> asignado<?= count($roles_asignados) !== 1 ? 's' : '' ?></div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar roles</button>
                <a href="index.php" class="btn btn-outline">Cancelar</a>
            </div>
        </div>
    </div>

</div>
</form>

<script>
    function actualizarEstado(checkbox) {
        const label = checkbox.closest('.role-item');
        label.classList.toggle('checked', checkbox.checked);
        actualizarContador();
    }

    function actualizarContador() {
        const total = document.querySelectorAll('#rolesGrid input[type="checkbox"]:checked').length;
        const el = document.getElementById('contadorRoles');
        if (el) el.textContent = total;
    }

    function toggleTodos(estado) {
        document.querySelectorAll('#rolesGrid input[type="checkbox"]').forEach(cb => {
            cb.checked = estado;
            cb.closest('.role-item').classList.toggle('checked', estado);
        });
        actualizarContador();
    }
</script>

</body>
</html>
