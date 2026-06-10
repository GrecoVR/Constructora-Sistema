<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('configurar_sistema');

$pdo = conectar();

$permisos = $_SESSION['permisos'];

$id  = intval($_GET['id'] ?? 0);

if (!$id) {
    header('Location: index.php');
    exit;
}

$error = '';
$exito = '';

// Carga el usuario
$stmt = $pdo->prepare("
    SELECT us.*, e.nombre as empleado
    FROM usuarios_sistema us
    JOIN empleados e ON e.id_empleado = us.id_empleado
    WHERE us.id_usuario_sistema = ?
");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            registrarAccion(LOG_EDITAR_USUARIO . ' — ID:' . $id . ' usuario: ' . $nuevo_usuario);
            $exito = 'Usuario actualizado correctamente';
            // Recarga datos
            $stmt->execute([$id]);
            $usuario = $stmt->fetch();
        }
    } else {
        $error = 'El nombre de usuario no puede estar vacío';
    }
}
?>

<?php require_once '../../modules/layouts/header.php'; ?>

<nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index.php">Usuarios</a></li>
    <li class="breadcrumb-item active" aria-current="page">Editar Usuario</li>
  </ol>
</nav>

<h2 class="mb-4 fw-semibold">✏️ Editar Usuario </h2> 

<h4 class="mb-4 fw-semibold"><?= htmlspecialchars($usuario['empleado']) ?></h4>


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
    <label class="form-label" for="usuario" >Nombre de usuario:</label>
    <input class="form-control" type="text" id="usuario" name="usuario" value="<?= htmlspecialchars($usuario['nombre_usuario']) ?>" required>
    </div>
    <div class="mb-3">
    <label class="form-label" for="contrasena">Nueva Contraseña: (dejar vacío para no cambiar):</label>
    <input class="form-control" type="password" id="contrasena" name="contrasena" required>
    </div>
    <div class="mb-3">
    <label class="form-label" for="estado">Estado:</label>
    <select class="form-select" id="estado" name="estado" value="<?= $usuario['estado'] ?>">
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