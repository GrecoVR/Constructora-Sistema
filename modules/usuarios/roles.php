<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('configurar_sistema');

$pdo = conectar();
$id  = intval($_GET['id'] ?? 0);

if (!$id) {
    header('Location: index.php');
    exit;
}

$exito = '';

// Carga el usuario
$stmt = $pdo->prepare("
    SELECT us.*, e.nombre as empleado
    FROM usuarios_sistema us
    JOIN empleados e ON e.id_empleado = us.id_empleado
    WHERE us.id_usuario_sistema = ?
");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: index.php');
    exit;
}

// Guarda roles
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roles_seleccionados = $_POST['roles'] ?? [];

    // Borra roles actuales
    $stmt2 = $pdo->prepare("DELETE FROM usuarios_roles WHERE id_usuario_sistema = ?");
    $stmt2->execute([$id]);

    // Inserta los nuevos
    if ($roles_seleccionados) {
        $stmt3 = $pdo->prepare("INSERT INTO usuarios_roles (id_usuario_sistema, id_rol) VALUES (?, ?)");
        foreach ($roles_seleccionados as $id_rol) {
            $stmt3->execute([$id, intval($id_rol)]);
        }
    }

    registrarAccion("Actualizó roles del usuario ID: $id");
    $exito = 'Roles actualizados correctamente';
}

// Todos los roles disponibles
$todos_roles = $pdo->query("SELECT * FROM roles ORDER BY nombre_rol ASC")->fetchAll();

// Roles actuales del usuario
$stmt4 = $pdo->prepare("SELECT id_rol FROM usuarios_roles WHERE id_usuario_sistema = ?");
$stmt4->execute([$id]);
$roles_actuales = $stmt4->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Roles — Vértice</title>
</head>
<body>

<h2>🔐 Roles de <?= htmlspecialchars($usuario['empleado']) ?></h2>
<a href="index.php">← Volver a usuarios</a>

<br><br>

<?php if ($exito): ?>
    <p style="color:green"><?= $exito ?></p>
<?php endif; ?>

<form method="POST">
    <p>Selecciona los roles para este usuario:</p>

    <?php foreach ($todos_roles as $rol): ?>
        <label>
            <input type="checkbox" name="roles[]" value="<?= $rol['id_rol'] ?>"
                <?= in_array($rol['id_rol'], $roles_actuales) ? 'checked' : '' ?>>
            <strong><?= htmlspecialchars($rol['nombre_rol']) ?></strong>
            — <?= htmlspecialchars($rol['descripcion']) ?>
        </label><br><br>
    <?php endforeach; ?>

    <button type="submit">Guardar roles</button>
</form>

</body>
</html>