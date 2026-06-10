<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
require_once '../../utils/fecha.php';

requierePermiso('gestionar_pagos');

$pdo = conectar();

$permisos = $_SESSION['permisos'];

$id  = intval($_GET['id'] ?? 0);

if (!$id) { header('Location: index.php'); exit; }

$error = '';
$exito = '';

// Info del contrato
$stmt = $pdo->prepare("
    SELECT c.id_contrato, c.estado, c.fecha_firma,
           cl.nombre as cliente, co.monto_total
    FROM contratos c
    JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
    JOIN clientes cl ON cl.id_cliente = co.id_cliente
    WHERE c.id_contrato = ?
");
$stmt->execute([$id]);
$contrato = $stmt->fetch();

if (!$contrato) { header('Location: index.php'); exit; }

registrarAccion(LOG_VER_PAGOS_CLIENTE . ' — contrato ID:' . $id);

// Registrar nuevo pago
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_metodo = intval($_POST['id_metodo_pago'] ?? 0);
    $fecha     = $_POST['fecha_pago'] ?? date('Y-m-d');
    $monto     = floatval($_POST['monto'] ?? 0);
    $estado    = $_POST['estado'] ?? 'pendiente';

    if ($id_metodo && $monto > 0) {
        $stmt2 = $pdo->prepare(
            "INSERT INTO pagos_cliente (id_contrato, id_metodo_pago, fecha_pago, monto, estado) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt2->execute([$id, $id_metodo, $fecha, $monto, $estado]);
        registrarAccion(LOG_REG_PAGO_CLIENTE . ' — contrato ID:' . $id . ' por Bs ' . number_format($monto, 2));
        $exito = 'Pago registrado correctamente';
    } else {
        $error = 'Completa todos los campos';
    }
}

// Pagos existentes
$pagos = $pdo->prepare("
    SELECT pc.id_pago_cliente, pc.fecha_pago, pc.monto,
           pc.estado, mp.nombre as metodo
    FROM pagos_cliente pc
    JOIN metodos_pago mp ON mp.id_metodo_pago = pc.id_metodo_pago
    WHERE pc.id_contrato = ?
    ORDER BY pc.fecha_pago DESC
");
$pagos->execute([$id]);
$pagos = $pagos->fetchAll();

$total_pagado  = array_sum(array_column(
    array_filter($pagos, fn($p) => $p['estado'] === 'completado'), 'monto'
));
$saldo_pendiente = $contrato['monto_total'] - $total_pagado;

$metodos = $pdo->query("SELECT * FROM metodos_pago ORDER BY nombre ASC")->fetchAll();
?>

<?php require_once '../../modules/layouts/header.php'; ?>

<nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index.php"> Contratos</a></li>
    <li class="breadcrumb-item active" aria-current="page"> Pagos Cliente</li>
  </ol>
</nav>

<h2 class="mb-4 fw-semibold">💳 Pagos — Contrato #<?= $id ?></h2>



<!-- RESUMEN -->
<table class="table table-striped table-bordered">
    <tr><td><strong>Cliente</strong></td><td><?= htmlspecialchars($contrato['cliente']) ?></td></tr>
    <tr><td><strong>Monto total contrato</strong></td><td>Bs <?= number_format($contrato['monto_total'], 2) ?></td></tr>
    <tr><td><strong>Total pagado</strong></td><td style="color:green">Bs <?= number_format($total_pagado, 2) ?></td></tr>
    <tr>
        <td><strong>Saldo pendiente</strong></td>
        <td style="color:<?= $saldo_pendiente > 0 ? 'red' : 'green' ?>">
            Bs <?= number_format($saldo_pendiente, 2) ?>
        </td>
    </tr>
    <tr><td><strong>Estado contrato</strong></td><td><?= ucfirst($contrato['estado']) ?></td></tr>
</table>

<hr>

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

<div class="row">
<div class="col-md-4 col-sm-12">
<!-- REGISTRAR PAGO -->
<div class="card shadow mt-2">
  <div class="card-header">
      <h4 class="mb-0"> Registrar nuevo pago</h4>
  </div>   
  <div class="card-body">
    <form method="POST">
        <div class="mb-3">
        <label class="form-label" for="id_metodo_pago">Método de pago: *</label>
        <select class="form-select" id="id_metodo_pago" name="id_metodo_pago" required>
            <option value="">-- Selecciona --</option>
            <?php foreach ($metodos as $m): ?>
                <option value="<?= $m['id_metodo_pago'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        </div>
        <div class="mb-3">
        <label class="form-label" for="monto">Monto (Bs): *</label>
        <input class="form-control" type="number" id="monto" name="monto" step="0.01" min="0.01"
               placeholder="<?= number_format($saldo_pendiente, 2) ?>" required>
        </div>
        <div class="mb-3">
        <label class="form-label" for="fecha_pago">Fecha de pago: *</label>
        <input class="form-control" type="date" id="fecha_pago" name="fecha_pago" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="mb-3">
        <label class="form-label" for="estado">Estado:</label>
        <select class="form-select" id="estado" name="estado">
            <option value="completado">Completado</option>
            <option value="pendiente">Pendiente</option>
        </select>
        </div>
        <button class="btn btn-primary" type="submit">Registrar pago</button>
    </form>
  </div>
</div>

</div>
<div class="col-md-8 col-sm-12">
<!-- HISTORIAL -->
<div class="card shadow mt-2">
  <div class="card-header">
      <h4 class="mb-0"> Historial de pagos</h4>
  </div>   
  <div class="card-body table-responsive">
  <table id="tabla-datos" class="table table-striped table-bordered">
    <thead>
      <tr>
          <th>ID</th>
          <th>Fecha</th>
          <th>Método</th>
          <th>Monto (Bs)</th>
          <th>Estado</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($pagos as $p): ?>
          <tr>
              <td><?= $p['id_pago_cliente'] ?></td>
              <td><?= formatoFechaCorta($p['fecha_pago']) ?></td>
              <td><?= htmlspecialchars($p['metodo']) ?></td>
              <td><?= number_format($p['monto'], 2) ?></td>
              <td style="color:<?= $p['estado'] === 'completado' ? 'green' : ($p['estado'] === 'fallido' ? 'red' : 'orange') ?>">
                  <?= ucfirst($p['estado']) ?>
              </td>
          </tr>
      <?php endforeach; ?>
     </tbody>
  </table>

  <?php if (empty($pagos)): ?>
      <p>No hay pagos registrados para este contrato.</p>
  <?php endif; ?>
  </div>
</div><!-- end card -->
</div>
</div><!-- end row -->

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