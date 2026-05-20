<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
require_once '../../utils/fecha.php';
require_once '../../triggers/TriggerManager.php';

requierePermiso('ver_empleados');

$pdo = conectar();
$id  = intval($_GET['id'] ?? 0);

if (!$id) { header('Location: index.php'); exit; }

$error = '';
$exito = '';

$stmt = $pdo->prepare("SELECT * FROM empleados WHERE id_empleado = ?");
$stmt->execute([$id]);
$empleado = $stmt->fetch();

if (!$empleado) { header('Location: index.php'); exit; }

registrarAccion("Vio asignaciones empleado ID: $id");

// Nueva asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_asignacion'])) {
    $id_proyecto  = intval($_POST['id_proyecto'] ?? 0);
    $id_cargo     = intval($_POST['id_cargo'] ?? 0);
    $fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-d');

    if ($id_proyecto && $id_cargo) {
        // Verifica que no esté ya asignado activamente
        $stmt2 = $pdo->prepare("
            SELECT id_asignacion FROM asignaciones
            WHERE id_empleado = ? AND id_proyecto = ? AND fecha_fin IS NULL
        ");
        $stmt2->execute([$id, $id_proyecto]);

        if ($stmt2->fetch()) {
            $error = 'El empleado ya está asignado activamente a ese proyecto';
        } else {
            $stmt3 = $pdo->prepare("
                INSERT INTO asignaciones (id_proyecto, id_empleado, id_cargo, fecha_inicio)
                VALUES (?, ?, ?, ?)
            ");
            $stmt3->execute([$id_proyecto, $id, $id_cargo, $fecha_inicio]);

            // Trigger — notifica al empleado
            $manager = new TriggerManager($pdo);
            $manager->ejecutar('empleados.asignacion_nueva', [
                'id_empleado' => $id,
                'id_proyecto' => $id_proyecto,
                'id_cargo'    => $id_cargo
            ]);

            registrarAccion("Asignó empleado ID: $id al proyecto ID: $id_proyecto");
            $exito = 'Asignación registrada correctamente';
        }
    } else {
        $error = 'Selecciona proyecto y cargo';
    }
}

// Finalizar asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_asignacion'])) {
    $id_asignacion = intval($_POST['id_asignacion'] ?? 0);

    if ($id_asignacion) {
        $stmt4 = $pdo->prepare("
            UPDATE asignaciones SET fecha_fin = CURDATE() WHERE id_asignacion = ?
        ");
        $stmt4->execute([$id_asignacion]);
        registrarAccion("Finalizó asignación ID: $id_asignacion");
        $exito = 'Asignación finalizada';
    }
}

// Asignaciones actuales
$asignaciones = $pdo->prepare("
    SELECT a.id_asignacion, a.fecha_inicio, a.fecha_fin,
           p.nombre as proyecto, p.estado as estado_proyecto,
           c.nombre as cargo
    FROM asignaciones a
    JOIN proyectos p ON p.id_proyecto = a.id_proyecto
    JOIN cargos c ON c.id_cargo = a.id_cargo
    WHERE a.id_empleado = ?
    ORDER BY a.fecha_inicio DESC
");
$asignaciones->execute([$id]);
$asignaciones = $asignaciones->fetchAll();

$proyectos = $pdo->query("
    SELECT id_proyecto, nombre FROM proyectos
    WHERE estado IN ('planificacion','ejecucion')
    ORDER BY nombre ASC
")->fetchAll();

$cargos = $pdo->query("SELECT id_cargo, nombre FROM cargos ORDER BY nombre ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignaciones — <?= htmlspecialchars($empleado['nombre']) ?></title>
</head>
<body>

<h2>📋 Asignaciones — <?= htmlspecialchars($empleado['nombre']) ?></h2>
<a href="index.php">← Volver a empleados</a>

<br><br>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
<?php if ($exito): ?>
    <p style="color:green"><?= $exito ?></p>
<?php endif; ?>

<!-- ASIGNACIONES ACTUALES -->
<h3>Historial de asignaciones</h3>
<table border="1" cellpadding="8">
    <tr>
        <th>Proyecto</th>
        <th>Cargo</th>
        <th>Desde</th>
        <th>Hasta</th>
        <th>Acciones</th>
    </tr>
    <?php foreach ($asignaciones as $a): ?>
        <tr>
            <td><?= htmlspecialchars($a['proyecto']) ?></td>
            <td><?= htmlspecialchars($a['cargo']) ?></td>
            <td><?= formatoFechaCorta($a['fecha_inicio']) ?></td>
            <td><?= $a['fecha_fin'] ? formatoFechaCorta($a['fecha_fin']) : '<span style="color:green">Activo</span>' ?></td>
            <td>
                <?php if (!$a['fecha_fin'] && in_array('gestionar_empleados', $_SESSION['permisos'])): ?>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="id_asignacion" value="<?= $a['id_asignacion'] ?>">
                        <button type="submit" name="finalizar_asignacion"
                                onclick="return confirm('¿Finalizar esta asignación?')">
                            Finalizar
                        </button>
                    </form>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php if (empty($asignaciones)): ?>
    <p>Este empleado no tiene asignaciones registradas.</p>
<?php endif; ?>

<hr>

<?php if (in_array('gestionar_empleados', $_SESSION['permisos'])): ?>
<h3>➕ Nueva asignación</h3>
<form method="POST">
    <label>Proyecto: *</label><br>
    <select name="id_proyecto" required>
        <option value="">-- Selecciona --</option>
        <?php foreach ($proyectos as $p): ?>
            <option value="<?= $p['id_proyecto'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Cargo: *</label><br>
    <select name="id_cargo" required>
        <option value="">-- Selecciona --</option>
        <?php foreach ($cargos as $c): ?>
            <option value="<?= $c['id_cargo'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Fecha de inicio:</label><br>
    <input type="date" name="fecha_inicio" value="<?= date('Y-m-d') ?>"><br><br>

    <button type="submit" name="nueva_asignacion">Asignar</button>
</form>
<?php endif; ?>

</body>
</html>