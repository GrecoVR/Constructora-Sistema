<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
require_once '../../utils/fecha.php';
require_once '../../triggers/TriggerManager.php';

requierePermiso('ver_cotizaciones');
registrarAccion('Vio cotizaciones');

$pdo   = conectar();
$error = '';
$exito = '';

// Cambiar estado de cotización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $id_cotizacion = intval($_POST['id_cotizacion'] ?? 0);
    $nuevo_estado  = $_POST['nuevo_estado'] ?? '';

    $estados_validos = ['pendiente', 'aprobada', 'rechazada'];

    if ($id_cotizacion && in_array($nuevo_estado, $estados_validos)) {
        $stmt = $pdo->prepare("
            UPDATE cotizaciones SET estado = ? WHERE id_cotizacion = ?
        ");
        $stmt->execute([$nuevo_estado, $id_cotizacion]);

        // Si se aprobó dispara el trigger
        if ($nuevo_estado === 'aprobada') {
            $manager = new TriggerManager($pdo);
            $manager->ejecutar('contratos.cotizacion_aprobada', [
                'id_cotizacion' => $id_cotizacion
            ]);
        }

        registrarAccion("Cambi estado cotización ID: $id_cotizacion a $nuevo_estado");
        $exito = 'Estado actualizado correctamente';
    } else {
        $error = 'Datos incorrectos';
    }
}

$cotizaciones = $pdo->query("
    SELECT co.id_cotizacion, co.fecha_creacion, co.estado, co.monto_total,
           cl.nombre as cliente
    FROM cotizaciones co
    JOIN clientes cl ON cl.id_cliente = co.id_cliente
    ORDER BY co.fecha_creacion DESC
")->fetchAll();
?>

<?php require_once '../../modules/layouts/header.php'; ?>

<div class="p-4">

<?php if ($error): ?>
    <div class="toast fade show align-items-center text-bg-danger border-0 w-100" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <?= $error ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
<?php endif; ?>
<?php if ($exito): ?>
    <div class="toast fade show align-items-center text-bg-success border-0 w-100" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <?= $exito ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
<?php endif; ?>

<nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index.php"> Contratos</a></li>
    <li class="breadcrumb-item active" aria-current="page"> Cotizaciones</li>
  </ol>
</nav>

<h2>📋 Cotizaciones</h2>

<div class="card shadow mt-2">
  <div class="card-header">
      <h4 class="mb-0"> Información general</h4>
  </div>   
  <div class="card-body table-responsive">
  <table id="tabla-datos" class="table table-striped table-bordered">
      <thead>
      <tr>
          <th>ID</th>
          <th>Cliente</th>
          <th>Fecha</th>
          <th>Monto (Bs)</th>
          <th>Estado</th>
          <th>Acciones</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($cotizaciones as $co): ?>
          <tr>
              <td><?= $co['id_cotizacion'] ?></td>
              <td><?= htmlspecialchars($co['cliente']) ?></td>
              <td><?= formatoFechaCorta($co['fecha_creacion']) ?></td>
              <td><?= number_format($co['monto_total'], 2) ?></td>
              <td>
                  <?php
                  $color = match($co['estado']) {
                      'aprobada'  => 'success',
                      'rechazada' => 'danger',
                      default     => 'warning'
                  };
                  ?>
                  <span class="badge text-bg-<?= $color ?>"><?= ucfirst($co['estado']) ?></span>
              </td>
              <td>
                  <?php if ($co['estado'] === 'pendiente' && in_array('gestionar_cotizaciones', $_SESSION['permisos'])): ?>
                      <form method="POST" style="display:inline">
                          <input type="hidden" name="id_cotizacion" value="<?= $co['id_cotizacion'] ?>">
                          <input type="hidden" name="nuevo_estado" value="aprobada">
                          <button class="btn btn-outline-success btn-sm" type="submit" name="cambiar_estado"
                                  onclick="return confirm('¿Aprobar esta cotización?')">
                              <i class="bi bi-check-square-fill"></i> Aprobar
                          </button>
                      </form>
                      <form method="POST" style="display:inline">
                          <input type="hidden" name="id_cotizacion" value="<?= $co['id_cotizacion'] ?>">
                          <input type="hidden" name="nuevo_estado" value="rechazada">
                          <button class="btn btn-outline-danger btn-sm ms-2" type="submit" name="cambiar_estado"
                                  onclick="return confirm('¿Rechazar esta cotización?')">
                              <i class="bi bi-x-square-fill"></i> Rechazar
                          </button>
                      </form>
                  <?php endif; ?>
              </td>
          </tr>
      <?php endforeach; ?>
      </tbody>
  </table>
  </div>
</div>

</div>
<script>
$(document).ready(function() {
   var table = $('#tabla-datos').DataTable({
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
        },
        order: [],
        columnDefs: [
        {
          targets: -1,
          orderable: false
        }
        ]
    });
});    
</script>
<?php require_once '../../modules/layouts/footer.php'; ?>