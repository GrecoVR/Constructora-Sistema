<?php
session_start();
try {
    $pdo = new PDO('mysql:host=localhost;dbname=empresa_constructora52;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión a BD: " . $e->getMessage());
}
require_once '../../config/database.php';

// Paginación
$por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Filtro de búsqueda
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// Mensaje flash
$mensaje = $_SESSION['mensaje'] ?? null;
$tipo_mensaje = $_SESSION['tipo_mensaje'] ?? 'info';
unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);

// Eliminar usuario
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    try {
        $stmt = $pdo->prepare("DELETE FROM usuarios_sistema WHERE id_usuario_sistema = ?");
        $stmt->execute([$id]);
        $_SESSION['mensaje'] = 'Usuario eliminado correctamente.';
        $_SESSION['tipo_mensaje'] = 'exito';
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = 'Error al eliminar el usuario.';
        $_SESSION['tipo_mensaje'] = 'error';
    }
    header('Location: index.php');
    exit;
}

// Consulta total para paginación
$sql_total = "SELECT COUNT(*) FROM usuarios_sistema";
$params = [];
if ($buscar !== '') {
    $sql_total .= " WHERE nombre_usuario LIKE ? OR email_usuario LIKE ?";
    $params = ["%$buscar%", "%$buscar%"];
}
$stmt_total = $pdo->prepare($sql_total);
$stmt_total->execute($params);
$total_registros = (int)$stmt_total->fetchColumn();
$total_paginas = (int)ceil($total_registros / $por_pagina);

// Consulta principal
$sql = "SELECT id_usuario_sistema, nombre_usuario, email_usuario, estado_usuario FROM usuarios_sistema";
if ($buscar !== '') {
   $stmt = $pdo->prepare($sql);
$stmt->execute();
}

$stmt = $pdo->prepare($sql);
if ($buscar !== '') {
    $stmt->execute(["%$buscar%", "%$buscar%", $por_pagina, $offset]);
} else {
    $stmt->execute([$por_pagina, $offset]);
}
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
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
            --warning: #ffc947;
            --radius: 10px;
            --font: 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: var(--font);
            min-height: 100vh;
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header h1 {
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

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
        .btn-sm { padding: 0.35rem 0.75rem; font-size: 0.8rem; }
        .btn-outline { background: transparent; color: var(--text-muted); border: 1px solid var(--border); }
        .btn-outline:hover { border-color: var(--accent); color: var(--accent-light); }
        .btn-danger { background: transparent; color: var(--danger); border: 1px solid var(--danger); }
        .btn-danger:hover { background: var(--danger); color: #fff; }

        .alert {
            padding: 0.85rem 1.2rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .alert-exito { background: rgba(0,196,140,0.12); border: 1px solid rgba(0,196,140,0.3); color: var(--success); }
        .alert-error  { background: rgba(255,77,109,0.12); border: 1px solid rgba(255,77,109,0.3); color: var(--danger); }
        .alert-info   { background: rgba(108,99,255,0.12); border: 1px solid rgba(108,99,255,0.3); color: var(--accent-light); }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .search-box {
            display: flex;
            gap: 0.5rem;
        }

        .search-box input {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--text);
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            outline: none;
            width: 240px;
            transition: border-color 0.2s;
        }
        .search-box input:focus { border-color: var(--accent); }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }

        table { width: 100%; border-collapse: collapse; }
        thead { background: rgba(108,99,255,0.08); }
        th {
            text-align: left;
            padding: 0.85rem 1.2rem;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
        }
        td {
            padding: 0.9rem 1.2rem;
            font-size: 0.875rem;
            border-top: 1px solid var(--border);
            vertical-align: middle;
        }
        tr:hover td { background: rgba(255,255,255,0.02); }

        .badge {
            display: inline-block;
            padding: 0.2rem 0.65rem;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-activo   { background: rgba(0,196,140,0.15); color: var(--success); }
        .badge-inactivo { background: rgba(255,77,109,0.15); color: var(--danger); }

        .actions { display: flex; gap: 0.4rem; }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
        }
        .empty-state p { margin-top: 0.5rem; font-size: 0.9rem; }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.2rem;
            border-top: 1px solid var(--border);
            font-size: 0.85rem;
            color: var(--text-muted);
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .pagination-links { display: flex; gap: 0.35rem; }
        .pagination-links a, .pagination-links span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            font-size: 0.85rem;
            text-decoration: none;
            color: var(--text-muted);
            border: 1px solid var(--border);
            background: transparent;
            transition: all 0.2s;
        }
        .pagination-links a:hover { border-color: var(--accent); color: var(--accent); }
        .pagination-links .active { background: var(--accent); color: #fff; border-color: var(--accent); }
    </style>
</head>
<body>

<div class="header">
    <h1>Módulo de <span>Usuarios</span></h1>
    <a href="crear.php" class="btn btn-primary">+ Nuevo Usuario</a>
</div>

<?php if ($mensaje): ?>
    <div class="alert alert-<?= htmlspecialchars($tipo_mensaje) ?>">
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<div class="toolbar">
    <form class="search-box" method="GET" action="index.php">
        <input
            type="text"
            name="buscar"
            placeholder="Buscar por nombre o email..."
            value="<?= htmlspecialchars($buscar) ?>"
        >
        <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
        <?php if ($buscar): ?>
            <a href="index.php" class="btn btn-outline btn-sm">Limpiar</a>
        <?php endif; ?>
    </form>
    <span style="color:var(--text-muted);font-size:0.85rem;">
        <?= $total_registros ?> usuario<?= $total_registros !== 1 ? 's' : '' ?> encontrado<?= $total_registros !== 1 ? 's' : '' ?>
    </span>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Nombre de Usuario</th>
                <th>Email</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($usuarios)): ?>
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <strong>Sin resultados</strong>
                            <p>No se encontraron usuarios con los criterios actuales.</p>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td style="color:var(--text-muted)"><?= (int)$u['id_usuario_sistema'] ?></td>
                        <td><strong><?= htmlspecialchars($u['nombre_usuario']) ?></strong></td>
                        <td style="color:var(--text-muted)"><?= htmlspecialchars($u['email_usuario']) ?></td>
                        <td>
                            <?php $estado = strtolower($u['estado_usuario']); ?>
                            <span class="badge badge-<?= $estado === 'activo' ? 'activo' : 'inactivo' ?>">
                                <?= ucfirst(htmlspecialchars($u['estado_usuario'])) ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="editar.php?id=<?= (int)$u['id_usuario_sistema'] ?>" class="btn btn-outline btn-sm">Editar</a>
                                <a href="roles.php?id=<?= (int)$u['id_usuario_sistema'] ?>" class="btn btn-outline btn-sm">Roles</a>
                                <a
                                    href="index.php?eliminar=<?= (int)$u['id_usuario_sistema'] ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('¿Eliminar usuario <?= htmlspecialchars(addslashes($u['nombre_usuario'])) ?>? Esta acción no se puede deshacer.')"
                                >Eliminar</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_paginas > 1): ?>
        <div class="pagination">
            <span>Página <?= $pagina_actual ?> de <?= $total_paginas ?></span>
            <div class="pagination-links">
                <?php if ($pagina_actual > 1): ?>
                    <a href="?pagina=<?= $pagina_actual - 1 ?>&buscar=<?= urlencode($buscar) ?>">‹</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <?php if ($i === $pagina_actual): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?pagina=<?= $i ?>&buscar=<?= urlencode($buscar) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($pagina_actual < $total_paginas): ?>
                    <a href="?pagina=<?= $pagina_actual + 1 ?>&buscar=<?= urlencode($buscar) ?>">›</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
