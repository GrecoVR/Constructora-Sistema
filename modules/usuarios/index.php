<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('configurar_sistema');
registrarAccion('Vio lista de usuarios');

$pdo  = conectar();

$permisos = $_SESSION['permisos'];


$error  = '';
$exito  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  //actualizar
  if (isset($_POST['action'])) {
  if ($_POST['action'] == 'update') {
  $id = $_POST['id_usuario_sistema'];
  $nuevo_usuario = trim($_POST['usuario'] ?? '');
  $contrasena    = trim($_POST['contrasena'] ?? '');
  $estado        = $_POST['estado'] ?? 'activo';
  if ($nuevo_usuario) {
        // Verifica que no exista otro con ese nombre
        $stmt2 = $pdo->prepare("
            SELECT id_usuario_sistema FROM usuarios_sistema 
            WHERE nombre_usuario = ? AND id_usuario_sistema != ?
        ");
        $stmt2->execute([$nuevo_usuario, $id]);

        if ($stmt2->fetch()) {
            $error = 'Ese nombre de usuario ya existe';
        } else {
            if ($contrasena) {
                $hash = password_hash($contrasena, PASSWORD_DEFAULT);
                $stmt3 = $pdo->prepare("
                    UPDATE usuarios_sistema 
                    SET nombre_usuario = ?, password_hash = ?, estado = ?
                    WHERE id_usuario_sistema = ?
                ");
                $stmt3->execute([$nuevo_usuario, $hash, $estado, $id]);
            } else {
                $stmt3 = $pdo->prepare("
                    UPDATE usuarios_sistema 
                    SET nombre_usuario = ?, estado = ?
                    WHERE id_usuario_sistema = ?
                ");
                $stmt3->execute([$nuevo_usuario, $estado, $id]);
            }
            registrarAccion("Editó usuario ID: $id");
            $exito = 'Usuario actualizado correctamente';
        }
    } else {
        $error = 'El nombre de usuario no puede estar vacío';
    }
  }
  
  }else{
    //crear
    $usuario     = trim($_POST['usuario'] ?? '');
    $contrasena  = trim($_POST['contrasena'] ?? '');
    $id_empleado = intval($_POST['id_empleado'] ?? 0);
    $estado      = $_POST['estado'] ?? 'activo';

    if ($usuario && $contrasena && $id_empleado) {
        $stmt = $pdo->prepare("SELECT id_usuario_sistema FROM usuarios_sistema WHERE nombre_usuario = ?");
        $stmt->execute([$usuario]);

        if ($stmt->fetch()) {
            $error = 'Ese nombre de usuario ya existe';
        } else {
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            $stmt2 = $pdo->prepare("
                INSERT INTO usuarios_sistema (id_empleado, nombre_usuario, password_hash, estado)
                VALUES (?, ?, ?, ?)
            ");
            $stmt2->execute([$id_empleado, $usuario, $hash, $estado]);
            registrarAccion("Creó usuario: $usuario");
            $exito = 'Usuario creado correctamente';
        }
    } else {
        $error = 'Completa todos los campos';
    }    
  } 
}

$empleados = $pdo->query("
    SELECT e.id_empleado, CONCAT(e.nombre, ' ', e.apellidos) AS nombre
    FROM empleados e
    WHERE e.id_empleado NOT IN (SELECT id_empleado FROM usuarios_sistema)
    ORDER BY e.nombre ASC
")->fetchAll();


$stmt = $pdo->query("
    SELECT us.id_usuario_sistema, us.nombre_usuario, us.estado,
           CONCAT(e.nombre, ' ', e.apellidos) AS empleado,
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

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a class="text-decoration-none" href="../../modules/dashboard/dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Gestion de Usuarios</li>
  </ol>
</nav>

<h2 class="mb-4 fw-semibold">👥 Gestión de Usuarios</h2>

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
          <h4 class="mb-0">Lista de Usuarios</h4>
          <button type="button" id="addRowBtn" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo usuario</button>
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
                  <button type="button" class="btn btn-outline-secondary btn-sm border-0 fw-semibold editBtn" data-id=<?= $u['id_usuario_sistema'] ?>">
                     <i class="bi bi-pencil-square"></i> Editar</button>
                  
                  <a class="btn btn-outline-success btn-sm border-0 fw-semibold" href="roles.php?id=<?= $u['id_usuario_sistema'] ?>">
                     <i class="bi bi-person-gear"></i> Roles</a>
              </td>
          </tr>
      <?php endforeach; ?>
      </tbody>
      </table>
  </div>
</div>

<!-- create  Modal  -->
<form method="POST" id="createForm">
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Usuario Sistema</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
               <div class="mb-3">
                <label class="form-label" for="id_empleado" >Empleado:</label>
                <select class="form-select" id="id_empleado" name="id_empleado" required>
                    <option value="">-- Selecciona empleado --</option>
                    <?php foreach ($empleados as $emp): ?>
                        <option value="<?= $emp['id_empleado'] ?>"><?= htmlspecialchars($emp['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                </div>
                <div class="mb-3">
                <label class="form-label" for="usuario" >Nombre de usuario:</label>
                <input class="form-control" type="text" id="usuario" name="usuario" required>
                </div>
                <div class="mb-3">
                <label class="form-label" for="contrasena">Contraseña:</label>
                <input class="form-control" type="password" id="contrasena" name="contrasena" required>
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


<!-- edit  Modal  -->
<form method="POST" id="editForm">
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Usuario Sistema</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="id_usuario_sistema" id="id_usuario_sistema">
              <input type="hidden" name="action" id="action" value="update">
              <div class="mb-3">
              <label class="form-label" for="usuario2" >Nombre de usuario:</label>
              <input class="form-control" type="text" id="usuario2" name="usuario" value="<?= htmlspecialchars($usuario['nombre_usuario']) ?>" required>
              </div>
              <div class="mb-3">
              <label class="form-label" for="contrasena">Nueva Contraseña: (dejar vacío para no cambiar):</label>
              <input class="form-control" type="password" id="contrasena2" name="contrasena">
              </div>
              <div class="mb-3">
              <label class="form-label" for="estado2">Estado:</label>
              <select class="form-select" id="estado2" name="estado" value="<?= $usuario['estado'] ?>">
                  <option value="activo">Activo</option>
                  <option value="inactivo">Inactivo</option>
              </select>
              </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type="submit">Guardar Cambios</button>
            </div>
        </div>
   </div>
</div><!-- end modal -->
</form>

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
        $('#createForm')[0].reset();
        $('#action').val('create');
        $('#createModal').modal('show');
    });
    // Handle Edit Button Click
    $(document).on('click', '.editBtn', function() {
        const id = $(this).data('id');
        $('#id_usuario_sistema').val(id);

        //Get the row data
        const data = table.row($(this).parents('tr')).data();

        // Map data to Modal fields (using IDs of input elements)
        $('#usuario2').val(data[2]);
        const estado = $("#estado2").val().toLowerCase();
        $('#contrasena2').val('');
        $('#estado2').val(estado);
        $('#action').val('update');
        $('#editModal').modal('show');

    });
   
 });
</script>

<?php require_once '../../modules/layouts/footer.php'; ?>
