<?php
require_once '../middleware/auth_cliente.php';
require_once '../config/database.php';

$pdo        = conectar();
$id_cliente = $_SESSION['id_cliente'];

$notificaciones = $pdo->prepare("
    SELECT id_notificacion, titulo, contenido
    FROM notificaciones_clientes
    WHERE id_cliente = ?
    ORDER BY id_notificacion DESC
");
$notificaciones->execute([$id_cliente]);
$notificaciones = $notificaciones->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Notificaciones — Portal Cliente</title>
</head>
<body>

<h2>🔔 Mis Notificaciones</h2>
<a href="index.php">← Volver al portal</a>

<br><br>

<?php if ($notificaciones): ?>
    <?php foreach ($notificaciones as $n): ?>
        <div style="border:1px solid #ccc; padding:15px; margin-bottom:15px; border-radius:5px">
            <strong>📌 <?= htmlspecialchars($n['titulo']) ?></strong>
            <hr>
            <p><?= htmlspecialchars($n['contenido']) ?></p>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>No tienes notificaciones por el momento.</p>
<?php endif; ?>

</body>
</html>