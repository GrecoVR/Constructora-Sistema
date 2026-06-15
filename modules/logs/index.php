<?php
date_default_timezone_set('America/La_Paz');

require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
require_once '../../triggers/TriggerManager.php';

requierePermiso('registrar_movimientos');

$pdo   = conectar();

$permisos = $_SESSION['permisos'];
?>

<?php require_once '../../modules/layouts/header.php'; ?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a class="text-decoration-none" href="../../modules/dashboard/dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Registros Sistema</li>
  </ol>
</nav>

<h4 class="mb-4 fw-semibold">🔍 Últimas 20 acciones en el sistema</h4>

<div class="card shadow mb-4">
      <div class="card-body table-responsive">
      <?php
      $stmt = $pdo->query("
          SELECT rs.accion, rs.descripcion,rs.fecha_hora, us.nombre_usuario
          FROM registros_sistema rs
          JOIN usuarios_sistema us ON us.id_usuario_sistema = rs.id_usuario_sistema
          ORDER BY rs.fecha_hora DESC
          LIMIT 20
      ");
      $logs = $stmt->fetchAll();
      ?>
      <table id="tabla-datos" class="table table-striped table-bordered">
        <thead>
          <tr>
              <th>Usuario</th>
              <th>Acción</th>
              <th>Descripcion</th>
              <th>Fecha y hora</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $log): ?>
              <tr>
                  <td><?= htmlspecialchars($log['nombre_usuario']) ?></td>
                  <td><?= htmlspecialchars($log['accion']) ?></td>
                  <td><?= htmlspecialchars($log['descripcion']) ?></td>
                  <td><?= $log['fecha_hora'] ?></td>
              </tr>
          <?php endforeach; ?>
         </tbody>
      </table>
    </div><!-- end card-body -->
</div><!-- end card -->

<script>
$(document).ready(function() {
   var table = $('#tabla-datos').DataTable({
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
        }
    });
});    
</script>
<?php require_once '../../modules/layouts/footer.php'; ?>