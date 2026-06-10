<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('configurar_sistema');
registrarAccion(LOG_VER_USUARIOS);

$pdo  = conectar();

$permisos = $_SESSION['permisos'];

$stmt = $pdo->query("
    SELECT us.id_usuario_sistema, us.nombre_usuario, us.estado,
           e.nombre as empleado,
           GROUP_CONCAT(r.nombre_rol SEPARATOR ', ') as roles
    FROM usuarios_sistema us
    JOIN empleados e ON e.id_empleado = us.id_empleado
    LEFT JOIN usuarios_roles ur ON ur.id_usuario_sistema = us.id_usuario_sistema
    LEFT JOIN roles r ON r.id_rol = ur.id_rol
    GROUP BY us.id_usuario_sistema, us.nombre_usuario, us.estado, e.nombre
    ORDER BY e.nombre ASC
");
$usuarios = $stmt->fetchAll();
?>

<?php require_once '../../modules/layouts/header.php'; ?>

<nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="../../modules/dashboard/dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Gestion de Usuarios</li>
  </ol>
</nav>

<h2 class="mb-4 fw-semibold">👥 Gestión de Usuarios</h2>



<div class="card shadow mt-2">
      <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="mb-0">Lista de Usuarios</h4>
          <a class="btn btn-primary" href="crear.php"><i class="bi bi-plus-lg"></i> Nuevo usuario</a>
      </div>
      <div class="card-body table-responsive">
      <table id="tabla-datos" class="table table-striped table-bordered">
      <thead>
      <tr>
          <th>ID</th>
          <th>Empleado</th>
          <th>Usuario</th>
          <th>Roles</th>
          <th>Estado</th>
          <th>Acciones</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($usuarios as $u): ?>
          <tr>
              <td><?= $u['id_usuario_sistema'] ?></td>
              <td><?= htmlspecialchars($u['empleado']) ?></td>
              <td><?= htmlspecialchars($u['nombre_usuario']) ?></td>
              <td><?= $u['roles'] ?? 'Sin roles' ?></td>
              <td><?= $u['estado'] ?></td>
              <td>
                  <a class="btn btn-outline-secondary btn-sm border-0 fw-semibold" href="editar.php?id=<?= $u['id_usuario_sistema'] ?>">
                     <i class="bi bi-pencil-square"></i> Editar</a>
                  
                  <a class="btn btn-outline-success btn-sm border-0 fw-semibold" href="roles.php?id=<?= $u['id_usuario_sistema'] ?>">
                     <i class="bi bi-person-gear"></i> Roles</a>
              </td>
          </tr>
      <?php endforeach; ?>
      </tbody>
      </table>
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
