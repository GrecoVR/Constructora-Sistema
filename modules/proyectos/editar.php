<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('editar_proyectos');

$pdo = conectar();
$id  = intval($_GET['id'] ?? 0);

if (!$id) { header('Location: index.php'); exit; }

$error = '';
$exito = '';

$stmt = $pdo->prepare("SELECT * FROM proyectos WHERE id_proyecto = ?");
$stmt->execute([$id]);
$proyecto = $stmt->fetch();

if (!$proyecto) { header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre       = trim($_POST['nombre'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $ubicacion    = trim($_POST['ubicacion'] ?? '');
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin    = $_POST['fecha_fin_estimada'] ?? '';
    $estado       = $_POST['estado'] ?? 'planificacion';
    $id_tipo      = intval($_POST['id_tipo_proyecto'] ?? 0);

    if ($nombre && $descripcion && $ubicacion && $fecha_inicio && $fecha_fin && $id_tipo) {
        $stmt2 = $pdo->prepare("
            UPDATE proyectos SET nombre=?, descripcion=?, ubicacion=?,
            fecha_inicio=?, fecha_fin_estimada=?, estado=?, id_tipo_proyecto=?
            WHERE id_proyecto=?
        ");
        $stmt2->execute([$nombre, $descripcion, $ubicacion, $fecha_inicio, $fecha_fin, $estado, $id_tipo, $id]);
        registrarAccion("Editó proyecto ID: $id");
        $exito = 'Proyecto actualizado correctamente';
        $stmt->execute([$id]);
        $proyecto = $stmt->fetch();
    } else {
        $error = 'Completa todos los campos';
    }
}

$tipos = $pdo->query("SELECT * FROM tipos_proyecto ORDER BY nombre ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Proyecto — Vértice</title>
</head>
<body>

<h2>✏️ Editar Proyecto</h2>
<a href="index.php">← Volver a proyectos</a>
&nbsp;&nbsp;
<a href="detalle.php?id=<?= $id ?>">Ver detalle</a>

<br><br>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
<?php if ($exito): ?>
    <p style="color:green"><?= $exito ?></p>
<?php endif; ?>

<form method="POST">
    <label>Nombre: *</label><br>
    <input type="text" name="nombre" value="<?= htmlspecialchars($proyecto['nombre']) ?>" required style="width:400px"><br><br>

    <label>Descripción: *</label><br>
    <textarea name="descripcion" rows="3" cols="50" required><?= htmlspecialchars($proyecto['descripcion']) ?></textarea><br><br>

    <label>Ubicación: *</label><br>
    <input type="text" name="ubicacion" value="<?= htmlspecialchars($proyecto['ubicacion']) ?>" required style="width:400px"><br><br>

    <label>Tipo de proyecto: *</label><br>
    <select name="id_tipo_proyecto" required>
        <?php foreach ($tipos as $t): ?>
            <option value="<?= $t['id_tipo_proyecto'] ?>"
                <?= $t['id_tipo_proyecto'] == $proyecto['id_tipo_proyecto'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($t['nombre']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Fecha de inicio: *</label><br>
    <input type="date" name="fecha_inicio" value="<?= $proyecto['fecha_inicio'] ?>" required><br><br>

    <label>Fecha fin estimada: *</label><br>
    <input type="date" name="fecha_fin_estimada" value="<?= $proyecto['fecha_fin_estimada'] ?>" required><br><br>

    <label>Estado:</label><br>
    <select name="estado">
        <?php foreach (['planificacion','ejecucion','pausado','finalizado','cancelado'] as $est): ?>
            <option value="<?= $est ?>" <?= $proyecto['estado'] === $est ? 'selected' : '' ?>>
                <?= ucfirst($est) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <button type="submit">Guardar cambios</button>
</form>

</body>
</html>