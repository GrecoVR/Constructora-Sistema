<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('editar_proyectos');

$pdo = conectar();
$id  = intval($_GET['id'] ?? 0);

if (!$id) { header('Location: index.php'); exit; }

$error = '';
$exito = '';

$stmt = $pdo->prepare("SELECT * FROM proyectos WHERE id_proyecto = ?");
$stmt->execute([$id]);
$proyecto = $stmt->fetch();

if (!$proyecto) { header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre       = trim($_POST['nombre'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $ubicacion    = trim($_POST['ubicacion'] ?? '');
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin    = $_POST['fecha_fin_estimada'] ?? '';
    $estado       = $_POST['estado'] ?? 'planificacion';
    $id_tipo      = intval($_POST['id_tipo_proyecto'] ?? 0);

    if ($nombre && $descripcion && $ubicacion && $fecha_inicio && $fecha_fin && $id_tipo) {
        $stmt2 = $pdo->prepare("
            UPDATE proyectos SET nombre=?, descripcion=?, ubicacion=?,
            fecha_inicio=?, fecha_fin_estimada=?, estado=?, id_tipo_proyecto=?
            WHERE id_proyecto=?
        ");
        $stmt2->execute([$nombre, $descripcion, $ubicacion, $fecha_inicio, $fecha_fin, $estado, $id_tipo, $id]);
        registrarAccion("Editó proyecto ID: $id");
        $exito = 'Proyecto actualizado correctamente';
        $stmt->execute([$id]);
        $proyecto = $stmt->fetch();
    } else {
        $error = 'Completa todos los campos';
    }
}

$tipos = $pdo->query("SELECT * FROM tipos_proyecto ORDER BY nombre ASC")->fetchAll();
?>

<?php require_once '../../modules/layouts/header.php'; ?>

<nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index.php"> Proyectos</a></li>
    <li class="breadcrumb-item active" aria-current="page"> Editar proyecto</li>
  </ol>
</nav>

<a class="btn btn-secondary" href="detalle.php?id=<?= $id ?>">Ver detalle</a>

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
 <div class="card shadow mt-4">
  <div class="card-header">
      <h4 class="mb-0">✏️ Editar Proyecto</h4>
  </div>   
  <div class="card-body">
    <form method="POST">
        <div class="mb-3">
          <label class="form-label" for="nombre">Nombre: *</label>
          <input class="form-control" type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($proyecto['nombre']) ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label" for="descripcion">Descripción: *</label>
          <textarea class="form-control" id="descripcion" name="descripcion" rows="3" cols="50" required><?= htmlspecialchars($proyecto['descripcion']) ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label" for="ubicacion">Ubicación: *</label><br>
          <input class="form-control" type="text" id="ubicacion" name="ubicacion" value="<?= htmlspecialchars($proyecto['ubicacion']) ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label" for="id_tipo_proyecto">Tipo de proyecto: *</label>
          <select class="form-select" id="id_tipo_proyecto" name="id_tipo_proyecto" required>
              <?php foreach ($tipos as $t): ?>
                <option value="<?= $t['id_tipo_proyecto'] ?>"
                    <?= $t['id_tipo_proyecto'] == $proyecto['id_tipo_proyecto'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['nombre']) ?>
                </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label" for="fecha_inicio">Fecha de inicio: *</label>
          <input class="form-control" type="date" id="fecha_inicio" name="fecha_inicio" value="<?= $proyecto['fecha_inicio'] ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label" for="fecha_fin_estimada">Fecha fin estimada: *</label><br>
          <input class="form-control" type="date" id="fecha_fin_estimada" name="fecha_fin_estimada" value="<?= $proyecto['fecha_fin_estimada'] ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label" for="estado" >Estado:</label>
          <select class="form-select" id="estado" name="estado">
              <?php foreach (['planificacion','ejecucion','pausado','finalizado','cancelado'] as $est): ?>
                <option value="<?= $est ?>" <?= $proyecto['estado'] === $est ? 'selected' : '' ?>>
                    <?= ucfirst($est) ?>
                </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-primary" type="submit">Guardar cambios</button>
    </form>
  </div>
 </div>
</div><!-- end col -->
</div><!-- end row -->

<?php require_once '../../modules/layouts/footer.php'; ?>