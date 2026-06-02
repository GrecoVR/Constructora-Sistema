<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
require_once '../../utils/fecha.php';

requierePermiso('ver_proyectos');
registrarAccion('Vio lista de proyectos');

$pdo      = conectar();

$permisos = $_SESSION['permisos'];

// Gerentes ven todos, operativos solo los suyos
if (in_array('gestionar_contratos', $permisos)) {
    $stmt = $pdo->query("
        SELECT p.id_proyecto, p.nombre, p.estado, p.fecha_inicio,
               p.fecha_fin_estimada, tp.nombre as tipo,
               cl.nombre as cliente
        FROM proyectos p
        JOIN tipos_proyecto tp ON tp.id_tipo_proyecto = p.id_tipo_proyecto
        JOIN contratos c ON c.id_contrato = p.id_contrato
        JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
        JOIN clientes cl ON cl.id_cliente = co.id_cliente
        ORDER BY p.fecha_fin_estimada ASC
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT p.id_proyecto, p.nombre, p.estado, p.fecha_inicio,
               p.fecha_fin_estimada, tp.nombre as tipo,
               cl.nombre as cliente
        FROM proyectos p
        JOIN tipos_proyecto tp ON tp.id_tipo_proyecto = p.id_tipo_proyecto
        JOIN contratos c ON c.id_contrato = p.id_contrato
        JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
        JOIN clientes cl ON cl.id_cliente = co.id_cliente
        JOIN asignaciones a ON a.id_proyecto = p.id_proyecto
        JOIN usuarios_sistema us ON us.id_empleado = a.id_empleado
        WHERE us.id_usuario_sistema = ?
        GROUP BY p.id_proyecto
        ORDER BY p.fecha_fin_estimada ASC
    ");
    $stmt->execute([$_SESSION['id_usuario']]);
}
$proyectos = $stmt->fetchAll();
?>

<?php require_once '../../modules/layouts/header.php'; ?>

<nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="../../modules/dashboard/dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Proyectos</li>
  </ol>
</nav>

<h2 class="mb-4 fw-semibold">📁 Proyectos</h2>

<div class="card shadow mt-2">
  <div class="card-header d-flex justify-content-between align-items-center">
      <h4 class="mb-0">Lista de Proyectos</h4>
      <?php if (in_array('crear_proyectos', $permisos)): ?>
    <a class="btn btn-primary" href="crear.php"><i class="bi bi-plus-lg"></i> Nuevo proyecto</a>
<?php endif; ?>
  </div>   
  <div class="card-body table-responsive">
    <table id="tabla-datos" class="table table-striped table-bordered">
      <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Cliente</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Fecha inicio</th>
            <th>Fecha fin</th>
            <th>Acciones</th>
        </tr>
       </thead>
       <tbody>
        <?php foreach ($proyectos as $p): ?>
            <tr>
                <td><?= $p['id_proyecto'] ?></td>
                <td><?= htmlspecialchars($p['nombre']) ?></td>
                <td><?= htmlspecialchars($p['cliente']) ?></td>
                <td><?= htmlspecialchars($p['tipo']) ?></td>
                <td><?= ucfirst($p['estado']) ?></td>
                <td><?= formatoFechaCorta($p['fecha_inicio']) ?></td>
                <td><?= estadoFecha($p['fecha_fin_estimada']) ?></td>
                <td>
                    <a class="btn btn-outline-success btn-sm border-0 fw-semibold" href="detalle.php?id=<?= $p['id_proyecto'] ?>">
                      <i class="bi bi-eye-fill"></i> Ver</a>
                    <?php if (in_array('editar_proyectos', $permisos)): ?>
                        
                    <a class="btn btn-outline-secondary btn-sm border-0 fw-semibold" href="editar.php?id=<?= $p['id_proyecto'] ?>">
                      <i class="bi bi-pencil-square"></i> Editar</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
  </div>
</div>

<?php if (empty($proyectos)): ?>
    <p>No tienes proyectos asignados.</p>
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