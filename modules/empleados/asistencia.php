<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
require_once '../../utils/fecha.php';

requierePermiso('registrar_asistencia');
registrarAccion('Vio módulo de asistencia');

$pdo   = conectar();
$error = '';
$exito = '';

// Filtro por fecha
$fecha_filtro = $_GET['fecha'] ?? date('Y-m-d');

// Registrar asistencia
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_asignacion = intval($_POST['id_asignacion'] ?? 0);
    $fecha         = $_POST['fecha'] ?? date('Y-m-d');
    $hora_entrada  = $_POST['hora_entrada'] ?? '';
    $hora_salida   = $_POST['hora_salida'] ?? '';

    if ($id_asignacion && $fecha && $hora_entrada) {
        // Verifica que no exista ya para esa fecha
        $stmt = $pdo->prepare("
            SELECT id_asistencia FROM asistencia
            WHERE id_asignacion = ? AND fecha = ?
        ");
        $stmt->execute([$id_asignacion, $fecha]);

        if ($stmt->fetch()) {
            $error = 'Ya existe un registro de asistencia para ese empleado en esa fecha';
        } else {
            $stmt2 = $pdo->prepare("
                INSERT INTO asistencia (id_asignacion, fecha, hora_entrada, hora_salida)
                VALUES (?, ?, ?, ?)
            ");
            $stmt2->execute([
                $id_asignacion, $fecha, $hora_entrada,
                $hora_salida ?: null
            ]);
            registrarAccion("Registró asistencia asignación ID: $id_asignacion fecha: $fecha");
            $exito = 'Asistencia registrada correctamente';
        }
    } else {
        $error = 'Completa los campos obligatorios';
    }
}

// Asignaciones activas para el dropdown
$asignaciones = $pdo->query("
    SELECT a.id_asignacion, e.nombre as empleado,
           p.nombre as proyecto, c.nombre as cargo
    FROM asignaciones a
    JOIN empleados e ON e.id_empleado = a.id_empleado
    JOIN proyectos p ON p.id_proyecto = a.id_proyecto
    JOIN cargos c ON c.id_cargo = a.id_cargo
    WHERE a.fecha_fin IS NULL
    AND e.estado = 'activo'
    ORDER BY e.nombre ASC
")->fetchAll();

// Registros del día filtrado
$registros = $pdo->prepare("
    SELECT ast.id_asistencia, ast.hora_entrada, ast.hora_salida,
           e.nombre as empleado, p.nombre as proyecto, c.nombre as cargo,
           TIMEDIFF(ast.hora_salida, ast.hora_entrada) as horas_trabajadas
    FROM asistencia ast
    JOIN asignaciones a ON a.id_asignacion = ast.id_asignacion
    JOIN empleados e ON e.id_empleado = a.id_empleado
    JOIN proyectos p ON p.id_proyecto = a.id_proyecto
    JOIN cargos c ON c.id_cargo = a.id_cargo
    WHERE ast.fecha = ?
    ORDER BY e.nombre ASC
");
$registros->execute([$fecha_filtro]);
$registros = $registros->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asistencia — Vértice</title>
</head>
<body>

<h2>📋 Registro de Asistencia</h2>
<a href="../../dashboard.php">← Volver al dashboard</a>

<br><br>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
<?php if ($exito): ?>
    <p style="color:green"><?= $exito ?></p>
<?php endif; ?>

<!-- REGISTRAR -->
<h3>Registrar asistencia</h3>
<form method="POST">
    <label>Empleado / Asignación: *</label><br>
    <select name="id_asignacion" required>
        <option value="">-- Selecciona --</option>
        <?php foreach ($asignaciones as $a): ?>
            <option value="<?= $a['id_asignacion'] ?>">
                <?= htmlspecialchars($a['empleado']) ?>
                — <?= htmlspecialchars($a['proyecto']) ?>
                (<?= htmlspecialchars($a['cargo']) ?>)
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Fecha: *</label><br>
    <input type="date" name="fecha" value="<?= $fecha_filtro ?>"><br><br>

    <label>Hora de entrada: *</label><br>
    <input type="time" name="hora_entrada" value="07:00"><br><br>

    <label>Hora de salida:</label><br>
    <input type="time" name="hora_salida" value="17:00"><br><br>

    <button type="submit">Registrar</button>
</form>

<hr>

<!-- VER POR FECHA -->
<h3>Asistencia del día</h3>
<form method="GET">
    <input type="date" name="fecha" value="<?= $fecha_filtro ?>">
    <button type="submit">Ver</button>
</form>

<br>

<?php if ($registros): ?>
    <table border="1" cellpadding="8">
        <tr>
            <th>Empleado</th>
            <th>Proyecto</th>
            <th>Cargo</th>
            <th>Entrada</th>
            <th>Salida</th>
            <th>Horas</th>
        </tr>
        <?php foreach ($registros as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['empleado']) ?></td>
                <td><?= htmlspecialchars($r['proyecto']) ?></td>
                <td><?= htmlspecialchars($r['cargo']) ?></td>
                <td><?= $r['hora_entrada'] ?></td>
                <td><?= $r['hora_salida'] ?? '—' ?></td>
                <td><?= $r['hora_salida'] ? $r['horas_trabajadas'] : '—' ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>No hay registros de asistencia para el <?= formatoFechaCorta($fecha_filtro) ?>.</p>
<?php endif; ?>

</body>
</html>