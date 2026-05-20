<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('gestionar_materiales');

$pdo   = conectar();
$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre          = trim($_POST['nombre'] ?? '');
    $descripcion     = trim($_POST['descripcion'] ?? '');
    $precio          = floatval($_POST['precio'] ?? 0);
    $id_tipo         = intval($_POST['id_tipo_material'] ?? 0);
    $id_unidad       = intval($_POST['id_unidad_medida'] ?? 0);

    if ($nombre && $precio && $id_tipo && $id_unidad) {
        $stmt = $pdo->prepare("
            INSERT INTO materiales (id_tipo_material, id_unidad_medida, nombre, descripcion, precio_unitario_base)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$id_tipo, $id_unidad, $nombre, $descripcion, $precio]);
        registrarAccion("Creó material: $nombre");
        $exito = 'Material creado correctamente';
    } else {
        $error = 'Completa todos los campos obligatorios';
    }
}

$tipos   = $pdo->query("SELECT * FROM tipos_materiales ORDER BY nombre ASC")->fetchAll();
$unidades = $pdo->query("SELECT * FROM unidades_medida ORDER BY descripcion ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Material — Vértice</title>
</head>
<body>

<h2>➕ Nuevo Material</h2>
<a href="index.php">← Volver a materiales</a>

<br><br>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
<?php if ($exito): ?>
    <p style="color:green"><?= $exito ?></p>
<?php endif; ?>

<form method="POST">
    <label>Nombre: *</label><br>
    <input type="text" name="nombre" required><br><br>

    <label>Descripción:</label><br>
    <textarea name="descripcion" rows="3" cols="40"></textarea><br><br>

    <label>Tipo de material: *</label><br>
    <select name="id_tipo_material" required>
        <option value="">-- Selecciona --</option>
        <?php foreach ($tipos as $t): ?>
            <option value="<?= $t['id_tipo_material'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Unidad de medida: *</label><br>
    <select name="id_unidad_medida" required>
        <option value="">-- Selecciona --</option>
        <?php foreach ($unidades as $u): ?>
            <option value="<?= $u['id_unidad_medida'] ?>">
                <?= htmlspecialchars($u['descripcion']) ?> (<?= $u['abreviatura'] ?>)
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Precio unitario base (Bs): *</label><br>
    <input type="number" name="precio" step="0.01" min="0" required><br><br>

    <button type="submit">Crear material</button>
</form>

</body>
</html>