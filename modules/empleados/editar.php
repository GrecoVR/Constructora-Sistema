<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('gestionar_empleados');

$pdo = conectar();

$permisos = $_SESSION['permisos'];

$id  = intval($_GET['id'] ?? 0);

if (!$id) { header('Location: index.php'); exit; }

$error = '';
$exito = '';

$stmt = $pdo->prepare("SELECT * FROM empleados WHERE id_empleado = ?");
$stmt->execute([$id]);
$empleado = $stmt->fetch();

if (!$empleado) { header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $ci        = trim($_POST['ci'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $estado    = $_POST['estado'] ?? 'activo';

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
                UPDATE empleados SET nombre=?, ci=?, direccion=?, telefono=?, email=?, estado=?
                WHERE id_empleado=?
            ");
            $stmt3->execute([$nombre, $ci, $direccion, $telefono, $email, $estado, $id]);
            registrarAccion("Editó empleado ID: $id");
            $exito = 'Empleado actualizado correctamente';
            $stmt->execute([$id]);
            $empleado = $stmt->fetch();
        }
    } else {
        $error = 'Completa los campos obligatorios';
    }
}
?>

<?php require_once '../../modules/layouts/header.php'; ?>

<nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index.php"> Empleados</a></li>
    <li class="breadcrumb-item active" aria-current="page"> Editar empleado</li>
  </ol>
</nav>

<h2 class="mb-4 fw-semibold">✏️ Editar Empleado</h2>


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
<div class="col-lg-4 col-md-6 col-sm-8 col-xs-12">
<div class="card shadow mt-2">
  <div class="card-body">
  <form method="POST">
    <div class="mb-3">
    <label class="form-label" for="nombre">Nombre completo: *</label>
    <input class="form-control" type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($empleado['nombre']) ?>" required>
    </div>
    <div class="mb-3">
    <label class="form-label" for="ci">CI: *</label>
    <input class="form-control" type="text" id="ci" name="ci" value="<?= htmlspecialchars($empleado['ci']) ?>" required>
    </div>
    <div class="mb-3">
    <label class="form-label" for="direccion">Dirección: *</label>
    <input class="form-control" type="text" id="direccion" name="direccion" value="<?= htmlspecialchars($empleado['direccion']) ?>" required>
    </div>
    <div class="mb-3">
    <label class="form-label" for="telefono">Teléfono:</label>
    <input class="form-control" type="text" id="telefono" name="telefono" value="<?= htmlspecialchars($empleado['telefono']) ?>">
    </div>
    <div class="mb-3">
    <label class="form-label" for="email">Email:</label>
    <input class="form-control" type="email" id="email" name="email" value="<?= htmlspecialchars($empleado['email']) ?>">
    </div>
    <div class="mb-3">
    <label class="form-label" for="estado">Estado:</label>
    <select class="form-select" id="estado" name="estado" value="<?= $empleado['estado'] ?>">
        <option value="activo">Activo</option>
        <option value="inactivo">Inactivo</option>
    </select>
    </div>
    <button class="btn btn-primary" type="submit">Guardar Cambios</button>
  </form>
  </div>
</div>
</div><!-- end col -->
</div><!-- end row -->

<?php require_once '../../modules/layouts/footer.php'; ?>