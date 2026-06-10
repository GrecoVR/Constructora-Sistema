<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
require_once '../../utils/fecha.php';

requierePermiso('ver_contratos');
registrarAccion('Vio lista de contratos');

$pdo = conectar();

$permisos = $_SESSION['permisos'];

$contratos = $pdo->query("
    SELECT c.id_contrato, c.fecha_firma, c.estado,
           cl.nombre as cliente,
           co.monto_total,
           p.nombre as proyecto,
           p.fecha_fin_estimada
    FROM contratos c
    JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
    JOIN clientes cl ON cl.id_cliente = co.id_cliente
    LEFT JOIN proyectos p ON p.id_contrato = c.id_contrato
    ORDER BY c.fecha_firma DESC
")->fetchAll();
?>

<?php require_once '../../modules/layouts/header.php'; ?>


<nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="../../modules/dashboard/dashboard.php"> Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Contratos</li>
  </ol>
</nav>

<h2 class="mb-4 fw-semibold">📄 Contratos</h2>

<?php if (in_array('gestionar_contratos', $_SESSION['permisos'])): ?>    
    <a class="btn btn-primary" href="crear.php"><i class="bi bi-plus-lg"></i> Nuevo contrato</a>
    
    <a class="btn btn-secondary" href="cotizaciones.php"><i class="bi bi-eye-fill"></i> Ver cotizaciones</a>
<?php endif; ?>

<div class="card shadow mt-4">
  <div class="card-header">
      <h4 class="mb-0">📌 Información general</h4>
  </div>   
  <div class="card-body table-responsive">
    <table id="tabla-datos" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Cliente</th>
            <th>Proyecto</th>
            <th>Monto (Bs)</th>
            <th>Fecha firma</th>
            <th>Fecha fin</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
      </thead>
      </tbody>
        <?php foreach ($contratos as $c): ?>
            <tr>
                <td><?= $c['id_contrato'] ?></td>
                <td><?= htmlspecialchars($c['cliente']) ?></td>
                <td><?= $c['proyecto'] ? htmlspecialchars($c['proyecto']) : '—' ?></td>
                <td><?= number_format($c['monto_total'], 2) ?></td>
                <td><?= formatoFechaCorta($c['fecha_firma']) ?></td>
                <td><?= $c['fecha_fin_estimada'] ? estadoFecha($c['fecha_fin_estimada']) : '—' ?></td>
                <td><?= ucfirst($c['estado']) ?></td>
                <td>
                    <a class="btn btn-outline-secondary btn-sm border-0 fw-semibold" href="pagos_cliente.php?id=<?= $c['id_contrato'] ?>">
                        <i class="bi bi-eye-fill"></i> Ver Pagos</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
  </div>
</div>

<?php if (empty($contratos)): ?>
    <p>No hay contratos registrados.</p>
<?php endif; ?>

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