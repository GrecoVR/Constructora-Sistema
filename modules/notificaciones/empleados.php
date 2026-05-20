<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('ver_dashboard');
registrarAccion('Vio notificaciones de empleados');

$pdo   = conectar();
$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_empleado = intval($_POST['id_empleado'] ?? 0);
    $titulo      = trim($_POST['titulo'] ?? '');
    $contenido   = trim($_POST['contenido'] ?? '');
    $todos        = isset($_POST['todos']);

    if ($titulo && $contenido) {
        if ($todos) {
            // Notifica a todos los empleados activos
            $empleados_activos = $pdo->query("
                SELECT id_empleado FROM empleados WHERE estado = 'activo'
            ")->fetchAll();

            $stmt = $pdo->prepare("
                INSERT INTO notificaciones_empleados (id_empleado, titulo, contenido)
                VALUES (?, ?, ?)
            ");
            foreach ($empleados_activos as $emp) {
                $stmt->execute([$emp['id_empleado'], $titulo, $contenido]);
            }
            registrarAccion("Envió notificación masiva a todos los empleados");
            $exito = 'Notificación enviada a todos los empleados';

        } elseif ($id_empleado) {
            $stmt = $pdo->prepare("
                INSERT INTO notificaciones_empleados (id_empleado, titulo, contenido)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$id_empleado, $titulo, $contenido]);
            registrarAccion("Envió notificación al empleado ID: $id_empleado");
            $exito = 'Notificación enviada correctamente';
        } else {
            $error = 'Selecciona un empleado o marca enviar a todos';
        }
    } else {
        $error = 'Completa título y mensaje';
    }
}

$empleados = $pdo->query("
    SELECT id_empleado, nombre FROM empleados 
    WHERE estado = 'activo' ORDER BY nombre ASC
")->fetchAll();

// Mis notificaciones — las del empleado logueado
$stmt_mias = $pdo->prepare("
    SELECT n.id_notificacion_empleado, n.titulo, n.contenido
    FROM notificaciones_empleados n
    JOIN usuarios_sistema us ON us.id_empleado = n.id_empleado
    WHERE us.id_usuario_sistema = ?
    ORDER BY n.id_notificacion_empleado DESC
    LIMIT 10
");
$stmt_mias->execute([$_SESSION['id_usuario']]);
$mis_notificaciones = $stmt_mias->fetchAll();

// Historial general (solo gerentes)
$historial = [];
if (in_array('gestionar_empleados', $_SESSION['permisos'])) {
    $historial = $pdo->query("
        SELECT n.titulo, n.contenido, e.nombre as empleado
        FROM notificaciones_empleados n
        JOIN empleados e ON e.id_empleado = n.id_empleado
        ORDER BY n.id_notificacion_empleado DESC
        LIMIT 20
    ")->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Notificaciones Empleados — Vértice</title>
</head>
<body>

<h2>🔔 Notificaciones a Empleados</h2>
<a href="../../dashboard.php">← Volver al dashboard</a>
&nbsp;&nbsp;
<a href="clientes.php">Ver notificaciones clientes</a>

<br><br>

<!-- MIS NOTIFICACIONES -->
<h3>📬 Mis notificaciones</h3>
<?php if ($mis_notificaciones): ?>
    <table border="1" cellpadding="8">
        <tr>
            <th>#</th>
            <th>Título</th>
            <th>Mensaje</th>
        </tr>
        <?php foreach ($mis_notificaciones as $mn): ?>
            <tr>
                <td><?= $mn['id_notificacion_empleado'] ?></td>
                <td><?= htmlspecialchars($mn['titulo']) ?></td>
                <td><?= htmlspecialchars($mn['contenido']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>No tienes notificaciones.</p>
<?php endif; ?>

<hr>

<?php if (in_array('gestionar_empleados', $_SESSION['permisos'])): ?>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
<?php if ($exito): ?>
    <p style="color:green"><?= $exito ?></p>
<?php endif; ?>

<h3>Enviar notificación</h3>
<form method="POST">
    <label>
        <input type="checkbox" name="todos" id="todos" 
               onchange="document.getElementById('select_empleado').disabled=this.checked">
        Enviar a todos los empleados
    </label><br><br>

    <label>Empleado específico:</label><br>
    <select name="id_empleado" id="select_empleado">
        <option value="">-- Selecciona --</option>
        <?php foreach ($empleados as $emp): ?>
            <option value="<?= $emp['id_empleado'] ?>"><?= htmlspecialchars($emp['nombre']) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Título: *</label><br>
    <input type="text" name="titulo" required style="width:400px"><br><br>

    <label>Mensaje: *</label><br>
    <textarea name="contenido" rows="5" cols="50" required></textarea><br><br>

    <button type="submit">Enviar notificación</button>
</form>

<hr>

<h3>Historial general</h3>
<?php if ($historial): ?>
    <table border="1" cellpadding="8">
        <tr>
            <th>Empleado</th>
            <th>Título</th>
            <th>Mensaje</th>
        </tr>
        <?php foreach ($historial as $h): ?>
            <tr>
                <td><?= htmlspecialchars($h['empleado']) ?></td>
                <td><?= htmlspecialchars($h['titulo']) ?></td>
                <td><?= htmlspecialchars($h['contenido']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>No hay notificaciones enviadas.</p>
<?php endif; ?>

<?php endif; ?>

</body>
</html>