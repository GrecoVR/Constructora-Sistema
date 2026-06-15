<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('ver_empleados');
registrarAccion('Vio lista de empleados');

$pdo      = conectar();

$permisos = $_SESSION['permisos'];

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action'])) {
    $id        = trim($_POST['id_empleado'] ?? 0);
    $nombre    = trim($_POST['nombre'] ?? '');
    $apellidos    = trim($_POST['apellidos'] ?? '');
    $ci        = trim($_POST['ci'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $estado    = $_POST['estado'] ?? 'activo';
  //crear
  if ($_POST['action'] == 'create') {
    if ($nombre && $ci && $direccion) {
        // Verifica CI único
        $stmt = $pdo->prepare("SELECT id_empleado FROM empleados WHERE ci = ?");
        $stmt->execute([$ci]);

        if ($stmt->fetch()) {
            $error = 'Ya existe un empleado con ese CI';
        } else {
            $stmt2 = $pdo->prepare("
                INSERT INTO empleados (nombre, apellidos, ci, direccion, telefono, email, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt2->execute([$nombre, $apellidos, $ci, $direccion, $telefono, $email, $estado]);
            $id_nuevo = $pdo->lastInsertId();
            registrarAccion("Creó empleado: $nombre (ID: $id_nuevo)");
            $exito = 'Empleado creado correctamente';
        }
    } else {
        $error = 'Completa los campos obligatorios';
    }
   }
   // actualizar
   if ($_POST['action'] == 'update') {
     
   if ($nombre && $ci && $direccion) {
        // Verifica CI único excluyendo el actual
        $stmt2 = $pdo->prepare("
            SELECT id_empleado FROM empleados WHERE ci = ? AND id_empleado != ?
        ");
        $stmt2->execute([$ci, $id]);

        if ($stmt2->fetch()) {
            $error = 'Ese CI ya pertenece a otro empleado';
        } else {
            $stmt3 = $pdo->prepare("
                UPDATE empleados SET nombre=?, apellidos=?, ci=?, direccion=?, telefono=?, email=?, estado=?
                WHERE id_empleado=?
            ");
            $stmt3->execute([$nombre, $apellidos,$ci, $direccion, $telefono, $email, $estado, $id]);
            registrarAccion("Editó empleado ID: $id");
            $exito = 'Empleado actualizado correctamente';
        }
    } else {
        $error = 'Completa los campos obligatorios';
    }   
   }
  }
}

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
        SELECT e.id_empleado, e.nombre, e.apellidos, e.ci, e.direccion, e.telefono, e.email, e.estado,
               MAX(c.nombre) as cargo_actual
        FROM empleados e
        LEFT JOIN asignaciones a ON a.id_empleado = e.id_empleado AND a.fecha_fin IS NULL AND a.id_asignacion = (SELECT MAX(id_asignacion) FROM Asignaciones WHERE id_empleado = e.id_empleado)
        LEFT JOIN cargos c ON c.id_cargo = a.id_cargo
        GROUP BY e.id_empleado, e.nombre, e.ci, e.telefono, e.email, e.estado
        ORDER BY e.nombre ASC
    ");
}
$empleados = $stmt->fetchAll();
?>

<?php require_once '../../modules/layouts/header.php'; ?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a class="text-decoration-none" href="../../modules/dashboard/dashboard.php"> Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page"> Empleados</li>
  </ol>
</nav>

<h2 class="mb-4 fw-semibold">👷 Empleados</h2>

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

<div class="card shadow mt-2">
  <div class="card-header d-flex justify-content-between align-items-center">
      <h4 class="mb-0">Lista de Empleados</h4>
      <?php if (in_array('gestionar_empleados', $_SESSION['permisos'])): ?>
        <button type="button" id="addRowBtn" class="btn btn-success"><i class="bi bi-plus-lg"></i> Nuevo empleado</button>
      <?php endif; ?>
  </div>   
  <div class="card-body table-responsive">
    <table id="tabla-datos" class="table table-striped table-bordered">
    <thead>
    <tr>
        <th>ID</th>
        <th>Nombres</th>
        <th>Apellidos</th>
        <th>CI</th>
        <th>Direccion</th>
        <th>Teléfono</th>
        <th>Email</th>
        <th>Ultimo Cargo Asignado</th>
        <th>Estado</th>
        <th>Acciones</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($empleados as $e): ?>
        <tr>
            <td><?= $e['id_empleado'] ?></td>
            <td><?= htmlspecialchars($e['nombre']) ?></td>
            <td><?= htmlspecialchars($e['apellidos']) ?></td>
            <td><?= $e['ci'] ?></td>
            <td><?= $e['direccion'] ?></td>
            <td><?= $e['telefono'] ?></td>
            <td><?= $e['email'] ?></td>
            <td><?= $e['cargo_actual'] ?? '—' ?></td>
            <td class="text-<?= $e['estado'] === 'activo' ? 'success' : 'danger' ?> fw-semibold">
                <?= ucfirst($e['estado']) ?>
            </td>
            <td>
                <a class="btn btn-outline-secondary btn-sm border-0 fw-semibold" href="asignaciones.php?id=<?= $e['id_empleado'] ?>">
                      <i class="bi bi-clipboard-data"></i> Cargos</a>
                <?php if (in_array('gestionar_empleados', $_SESSION['permisos'])): ?>
                    
                  <button type="button" class="btn btn-outline-primary btn-sm border-0 fw-semibold editBtn" data-id="<?= $e['id_empleado'] ?>">
                      <i class="bi bi-pencil-square"></i> Editar</button>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<!--  Modal (Handles both Create and Update) -->
<form method="POST" id="dataForm">
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">Registrar Empleado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="id_empleado" id="id_empleado">
              <input type="hidden" name="action" id="action" value="create">
              <div class="mb-3">
              <label class="form-label" for="nombre">Nombres : *</label>
              <input class="form-control" type="text" id="nombre" name="nombre" required>
              </div>
              <div class="mb-3">
              <label class="form-label" for="apellidos">Apellidos : *</label>
              <input class="form-control" type="text" id="apellidos" name="apellidos" required>
              </div>
              <div class="mb-3">
              <label class="form-label" for="ci">CI: *</label>
              <input class="form-control" type="text" id="ci" name="ci" required>
              </div>
              <div class="mb-3">
              <label class="form-label" for="direccion">Dirección: *</label>
              <input class="form-control" type="text" id="direccion" name="direccion" required>
              </div>
              <div class="mb-3">
              <label class="form-label" for="telefono">Teléfono:</label>
              <input class="form-control" type="text" id="telefono" name="telefono">
              </div>
              <div class="mb-3">
              <label class="form-label" for="email">Email:</label>
              <input class="form-control" type="email" id="email" name="email">
              </div>
              <div class="mb-3">
              <label class="form-label" for="estado">Estado:</label>
              <select class="form-select" id="estado" name="estado">
                  <option value="activo">Activo</option>
                  <option value="inactivo">Inactivo</option>
              </select>
              </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type="submit">Registrar</button>
            </div>
        </div>
   </div>
</div><!-- end modal -->
</form>

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
   // Open Modal for Adding row
    $('#addRowBtn').click(function() {
        $('#dataForm')[0].reset();
        $('.modal-title').text('Registrar Empleado');
        $('#action').val('create');
        $('#userModal').modal('show');
    });
    // Handle Edit Button Click
    $(document).on('click', '.editBtn', function() {
        const id = $(this).data('id');
        $('#id_empleado').val(id);

        //Get the row data
        const data = table.row($(this).parents('tr')).data();

        // Map data to Modal fields (using IDs of input elements)
        $('#nombre').val(data[1]);
        $('#apellidos').val(data[2]);
        $('#ci').val(data[3]);
        $('#direccion').val(data[4]);
        $('#telefono').val(data[5]);
        $('#email').val(data[6]);
        const estadoVal = data[8].toLowerCase();
        $('#estado').val(estadoVal);
        
        $('.modal-title').text('Editar Empleado');
        $('#action').val('update');
        $('#userModal').modal('show');

    });
});    
</script>

<?php require_once '../../modules/layouts/footer.php'; ?>