<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('configurar_sistema');

$pdo    = conectar();

$permisos = $_SESSION['permisos'];

$error  = '';
$exito  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
           registrarAccion(LOG_CREAR_USUARIO . ' — usuario: ' . $usuario);
            $exito = 'Usuario creado correctamente';
        }
    } else {
        $error = 'Completa todos los campos';
    }
}

$empleados = $pdo->query("
    SELECT e.id_empleado, e.nombre
    FROM empleados e
    WHERE e.id_empleado NOT IN (SELECT id_empleado FROM usuarios_sistema)
    ORDER BY e.nombre ASC
")->fetchAll();
?>

<?php require_once '../../modules/layouts/header.php'; ?>

<nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index.php">Usuarios</a></li>
    <li class="breadcrumb-item active" aria-current="page">Crear Usuario</li>
  </ol>
</nav>

<h2 class="mb-4 fw-semibold">➕ Crear Usuario</h2>

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
<div class="card shadow">
<div class="card-body">
<form method="POST">
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
    <button class="btn btn-primary" type="submit">Crear usuario</button>
</form>
</div>
</div>
</div><!-- end col -->
</div><!-- end row -->

<?php require_once '../../modules/layouts/footer.php'; ?>