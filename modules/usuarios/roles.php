<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('configurar_sistema');

$pdo = conectar();
$id  = intval($_GET['id'] ?? 0);

if (!$id) {
    header('Location: index.php');
    exit;
}

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

// Guarda roles
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roles_seleccionados = $_POST['roles'] ?? [];

    // Borra roles actuales
    $stmt2 = $pdo->prepare("DELETE FROM usuarios_roles WHERE id_usuario_sistema = ?");
    $stmt2->execute([$id]);

    // Inserta los nuevos
    if ($roles_seleccionados) {
        $stmt3 = $pdo->prepare("INSERT INTO usuarios_roles (id_usuario_sistema, id_rol) VALUES (?, ?)");
        foreach ($roles_seleccionados as $id_rol) {
            $stmt3->execute([$id, intval($id_rol)]);
        }
    }

    registrarAccion("Actualizó roles del usuario ID: $id");
    $exito = 'Roles actualizados correctamente';
}

// Todos los roles disponibles
$todos_roles = $pdo->query("SELECT * FROM roles ORDER BY nombre_rol ASC")->fetchAll();

// Roles actuales del usuario
$stmt4 = $pdo->prepare("SELECT id_rol FROM usuarios_roles WHERE id_usuario_sistema = ?");
$stmt4->execute([$id]);
$roles_actuales = $stmt4->fetchAll(PDO::FETCH_COLUMN);

$i=0;
?>

<?php require_once '../../modules/layouts/header.php'; ?>

<nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index.php">Usuarios</a></li>
    <li class="breadcrumb-item active" aria-current="page">Editar Roles</li>
  </ol>
</nav>

<h2 class="mb-4 fw-semibold">🔐 Roles de : </h2>

<h4 class="mb-4 fw-semibold"><?= htmlspecialchars($usuario['empleado']) ?></h4>

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

<div class="card shadow">
<div class="card-body">
<form method="POST">
    <p class="fw-semibold">Selecciona los roles para este usuario:</p>
    <div class="d-grid gap-2 mb-4" style="grid-template-columns: auto 1fr;">
    <?php foreach ($todos_roles as $rol): ?>
      <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="rol_<?= $i ?>" name="roles[]" value="<?= $rol['id_rol'] ?>"
              <?= in_array($rol['id_rol'], $roles_actuales) ? 'checked' : '' ?> >
          <label class="form-check-label fw-semibold" for="rol_<?= $i ?>" ><?= htmlspecialchars($rol['nombre_rol']) ?> </label>           
      </div>
      <div> <?= htmlspecialchars($rol['descripcion']) ?> </div>
      <?php $i++; ?>
    <?php endforeach; ?>
    </div>
    <button class="btn btn-primary" type="submit">Guardar roles</button>
</form>
</div>
</div>

<?php require_once '../../modules/layouts/footer.php'; ?>