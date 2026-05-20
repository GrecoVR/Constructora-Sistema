<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('ver_dashboard');
registrarAccion('Vio notificaciones de clientes');

$pdo   = conectar();
$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = intval($_POST['id_cliente'] ?? 0);
    $titulo     = trim($_POST['titulo'] ?? '');
    $contenido  = trim($_POST['contenido'] ?? '');

    if ($id_cliente && $titulo && $contenido) {
        $stmt = $pdo->prepare("
            INSERT INTO notificaciones_clientes (id_cliente, titulo, contenido)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$id_cliente, $titulo, $contenido]);
        registrarAccion("Envió notificación al cliente ID: $id_cliente");
        $exito = 'Notificación enviada correctamente';
    } else {
        $error = 'Completa todos los campos';
    }
}

$clientes = $pdo->query("
    SELECT id_cliente, nombre FROM clientes ORDER BY nombre ASC
")->fetchAll();

// Historial de notificaciones enviadas
$historial = $pdo->query("
    SELECT n.id_notificacion, n.titulo, n.contenido,
           c.nombre as cliente
    FROM notificaciones_clientes n
    JOIN clientes c ON c.id_cliente = n.id_cliente
    ORDER BY n.id_notificacion DESC
    LIMIT 20
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Notificaciones Clientes — Vértice</title>
</head>
<body>

<h2>🔔 Notificaciones a Clientes</h2>
<a href="../../dashboard.php">← Volver al dashboard</a>
&nbsp;&nbsp;
<a href="empleados.php">Ver notificaciones empleados</a>

<br><br>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
<?php if ($exito): ?>
    <p style="color:green"><?= $exito ?></p>
<?php endif; ?>

<h3>Enviar notificación</h3>
<form method="POST">
    <label>Cliente: *</label><br>
    <select name="id_cliente" required>
        <option value="">-- Selecciona cliente --</option>
        <?php foreach ($clientes as $c): ?>
            <option value="<?= $c['id_cliente'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Título: *</label><br>
    <input type="text" name="titulo" required style="width:400px"><br><br>

    <label>Mensaje: *</label><br>
    <textarea name="contenido" rows="5" cols="50" required></textarea><br><br>

    <button type="submit">Enviar notificación</button>
</form>

<hr>

<h3>Historial de notificaciones enviadas</h3>
<?php if ($historial): ?>
    <table border="1" cellpadding="8">
        <tr>
            <th>#</th>
            <th>Cliente</th>
            <th>Título</th>
            <th>Mensaje</th>
        </tr>
        <?php foreach ($historial as $h): ?>
            <tr>
                <td><?= $h['id_notificacion'] ?></td>
                <td><?= htmlspecialchars($h['cliente']) ?></td>
                <td><?= htmlspecialchars($h['titulo']) ?></td>
                <td><?= htmlspecialchars($h['contenido']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>No hay notificaciones enviadas aún.</p>
<?php endif; ?>

</body>
</html>