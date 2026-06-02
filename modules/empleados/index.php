<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('ver_empleados');
registrarAccion('Vio lista de empleados');

$pdo      = conectar();
$busqueda = trim($_GET['busqueda'] ?? '');

if ($busqueda) {
    $stmt = $pdo->prepare("
        SELECT e.id_empleado, e.nombre, e.ci, e.telefono, e.email, e.estado,
               MAX(c.nombre) as cargo_actual
        FROM empleados e
        LEFT JOIN asignaciones a ON a.id_empleado = e.id_empleado AND a.fecha_fin IS NULL
        LEFT JOIN cargos c ON c.id_cargo = a.id_cargo
        WHERE e.nombre LIKE ? OR e.ci LIKE ?
        GROUP BY e.id_empleado, e.nombre, e.ci, e.telefono, e.email, e.estado
        ORDER BY e.nombre ASC
    ");
    $stmt->execute(["%$busqueda%", "%$busqueda%"]);
} else {
    $stmt = $pdo->query("
        SELECT e.id_empleado, e.nombre, e.ci, e.telefono, e.email, e.estado,
               MAX(c.nombre) as cargo_actual
        FROM empleados e
        LEFT JOIN asignaciones a ON a.id_empleado = e.id_empleado AND a.fecha_fin IS NULL
        LEFT JOIN cargos c ON c.id_cargo = a.id_cargo
        GROUP BY e.id_empleado, e.nombre, e.ci, e.telefono, e.email, e.estado
        ORDER BY e.nombre ASC
    ");
}
$empleados = $stmt->fetchAll();
?>

<?php require_once '../../modules/layouts/header.php'; ?>

<nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="../../modules/dashboard/dashboard.php"> Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page"> Empleados</li>
  </ol>
</nav>

<h2 class="mb-4 fw-semibold">👷 Empleados</h2>

<div class="card shadow mt-2">
  <div class="card-header d-flex justify-content-between align-items-center">
      <h4 class="mb-0">Lista de Empleados</h4>
      <?php if (in_array('gestionar_empleados', $_SESSION['permisos'])): ?>
        <a class="btn btn-primary" href="crear.php"><i class="bi bi-plus-lg"></i> Nuevo empleado</a>
      <?php endif; ?>
  </div>   
  <div class="card-body table-responsive">
    <table id="tabla-datos" class="table table-striped table-bordered">
    <thead>
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>CI</th>
        <th>Teléfono</th>
        <th>Email</th>
        <th>Cargo actual</th>
        <th>Estado</th>
        <th>Acciones</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($empleados as $e): ?>
        <tr>
            <td><?= $e['id_empleado'] ?></td>
            <td><?= htmlspecialchars($e['nombre']) ?></td>
            <td><?= $e['ci'] ?></td>
            <td><?= $e['telefono'] ?></td>
            <td><?= $e['email'] ?></td>
            <td><?= $e['cargo_actual'] ?? '—' ?></td>
            <td style="color:<?= $e['estado'] === 'activo' ? 'green' : 'red' ?>">
                <?= ucfirst($e['estado']) ?>
            </td>
            <td>
                <a class="btn btn-outline-dark btn-sm border-0 fw-semibold" href="asignaciones.php?id=<?= $e['id_empleado'] ?>">
                      <i class="bi bi-clipboard-data"></i> Asignaciones</a>
                <?php if (in_array('gestionar_empleados', $_SESSION['permisos'])): ?>
                    
                    <a  class="btn btn-outline-secondary btn-sm border-0 fw-semibold" href="editar.php?id=<?= $e['id_empleado'] ?>">
                        <i class="bi bi-pencil-square"></i> Editar</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php if (empty($empleados)): ?>
    <p>No se encontraron empleados.</p>
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