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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" 
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body>
<div class="container-fluid bg-light">
 <div class="wrapper d-flex flex-column align-items-center vh-100">
  <div class="p-4">

  <h2 class="mb-4 fw-semibold">🔔 Mis Notificaciones</h2>
  <a href="index.php">← Volver al portal</a>

  <?php if ($notificaciones): ?>
      <?php foreach ($notificaciones as $n): ?>
          <div class="my-5 p-5 border rounded">
              <strong>📌 <?= htmlspecialchars($n['titulo']) ?></strong>
              <hr>
              <p><?= htmlspecialchars($n['contenido']) ?></p>
          </div>
      <?php endforeach; ?>
  <?php else: ?>
      <p>No tienes notificaciones por el momento.</p>
  <?php endif; ?>
  </div>
 </div>
</div>
</body>
</html>