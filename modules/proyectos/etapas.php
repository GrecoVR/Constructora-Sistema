<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
require_once '../../triggers/TriggerManager.php';

requierePermiso('editar_proyectos');

$pdo   = conectar();
$id    = intval($_GET['id'] ?? 0);
$error = '';
$exito = '';

if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM proyectos WHERE id_proyecto = ?");
$stmt->execute([$id]);
$proyecto = $stmt->fetch();

if (!$proyecto) { header('Location: index.php'); exit; }

// Actualizar avance de etapa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_etapa'])) {
    $id_etapa   = intval($_POST['id_etapa'] ?? 0);
    $porcentaje = intval($_POST['porcentaje_avance'] ?? 0);
    $estado     = $_POST['estado_etapa'] ?? 'ejecucion';

    if ($id_etapa && $porcentaje >= 0 && $porcentaje <= 100) {
        $stmt2 = $pdo->prepare("
            UPDATE etapas_proyecto
            SET porcentaje_avance = ?, estado = ?
            WHERE id_etapa_proyecto = ? AND id_proyecto = ?
        ");
        $stmt2->execute([$porcentaje, $estado, $id_etapa, $id]);

        // Dispara trigger de etapa completada
        $manager = new TriggerManager($pdo);
        $manager->ejecutar('proyectos.etapa_completada', [
            'id_etapa'          => $id_etapa,
            'porcentaje_avance' => $porcentaje
        ]);

        registrarAccion("Actualizó etapa ID: $id_etapa al $porcentaje%");
        $exito = 'Etapa actualizada correctamente';
    } else {
        $error = 'Datos incorrectos';
    }
}

// Crear nueva etapa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_etapa'])) {
    $nombre      = trim($_POST['nombre_etapa'] ?? '');
    $descripcion = trim($_POST['descripcion_etapa'] ?? '');
    $fecha_ini   = $_POST['fecha_inicio_etapa'] ?? '';
    $fecha_fin   = $_POST['fecha_fin_etapa'] ?? '';

    if ($nombre && $descripcion && $fecha_ini && $fecha_fin) {
        $stmt3 = $pdo->prepare("
            INSERT INTO etapas_proyecto 
            (id_proyecto, nombre, descripcion, porcentaje_avance, fecha_inicio, fecha_fin, estado)
            VALUES (?, ?, ?, 0, ?, ?, 'planificacion')
        ");
        $stmt3->execute([$id, $nombre, $descripcion, $fecha_ini, $fecha_fin]);
        registrarAccion("Creó nueva etapa en proyecto ID: $id");
        $exito = 'Etapa creada correctamente';
    } else {
        $error = 'Completa todos los campos de la nueva etapa';
    }
}

// Recarga etapas
$etapas = $pdo->prepare("SELECT * FROM etapas_proyecto WHERE id_proyecto = ? ORDER BY fecha_inicio ASC");
$etapas->execute([$id]);
$etapas = $etapas->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etapas — <?= htmlspecialchars($proyecto['nombre']) ?></title>
</head>
<body>

<h2>📋 Etapas — <?= htmlspecialchars($proyecto['nombre']) ?></h2>
<a href="detalle.php?id=<?= $id ?>">← Volver al detalle</a>

<br><br>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
<?php if ($exito): ?>
    <p style="color:green"><?= $exito ?></p>
<?php endif; ?>

<!-- ETAPAS ACTUALES -->
<h3>Etapas actuales</h3>
<?php foreach ($etapas as $e): ?>
    <fieldset style="margin-bottom:15px">
        <legend><strong><?= htmlspecialchars($e['nombre']) ?></strong> — <?= $e['porcentaje_avance'] ?>%</legend>
        <p><?= htmlspecialchars($e['descripcion']) ?></p>
        <progress value="<?= $e['porcentaje_avance'] ?>" max="100"></progress>

        <form method="POST" style="display:inline">
            <input type="hidden" name="id_etapa" value="<?= $e['id_etapa_proyecto'] ?>">

            <label>Avance %:</label>
            <input type="number" name="porcentaje_avance" min="0" max="100"
                   value="<?= $e['porcentaje_avance'] ?>" style="width:60px">

            <label>Estado:</label>
            <select name="estado_etapa">
                <?php foreach (['planificacion','ejecucion','pausado','finalizado','cancelado'] as $est): ?>
                    <option value="<?= $est ?>" <?= $e['estado'] === $est ? 'selected' : '' ?>>
                        <?= ucfirst($est) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" name="actualizar_etapa">Actualizar</button>
        </form>
    </fieldset>
<?php endforeach; ?>

<hr>

<!-- NUEVA ETAPA -->
<h3>➕ Agregar nueva etapa</h3>
<form method="POST">
    <label>Nombre: *</label><br>
    <input type="text" name="nombre_etapa" required style="width:400px"><br><br>

    <label>Descripción: *</label><br>
    <textarea name="descripcion_etapa" rows="3" cols="50" required></textarea><br><br>

    <label>Fecha inicio: *</label><br>
    <input type="date" name="fecha_inicio_etapa" required><br><br>

    <label>Fecha fin: *</label><br>
    <input type="date" name="fecha_fin_etapa" required><br><br>

    <button type="submit" name="nueva_etapa">Crear etapa</button>
</form>

</body>
</html>