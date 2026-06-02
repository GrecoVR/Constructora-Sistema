<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('crear_proyectos');

$pdo   = conectar();
$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre           = trim($_POST['nombre'] ?? '');
    $descripcion      = trim($_POST['descripcion'] ?? '');
    $ubicacion        = trim($_POST['ubicacion'] ?? '');
    $fecha_inicio     = $_POST['fecha_inicio'] ?? '';
    $fecha_fin        = $_POST['fecha_fin_estimada'] ?? '';
    $id_tipo_proyecto = intval($_POST['id_tipo_proyecto'] ?? 0);
    $id_contrato      = intval($_POST['id_contrato'] ?? 0);
    $estado           = $_POST['estado'] ?? 'planificacion';

    if ($nombre && $descripcion && $ubicacion && $fecha_inicio && $fecha_fin && $id_tipo_proyecto && $id_contrato) {
        $stmt = $pdo->prepare("
            INSERT INTO proyectos 
            (id_tipo_proyecto, id_contrato, nombre, descripcion, ubicacion, fecha_inicio, fecha_fin_estimada, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$id_tipo_proyecto, $id_contrato, $nombre, $descripcion, $ubicacion, $fecha_inicio, $fecha_fin, $estado]);
        $id_nuevo = $pdo->lastInsertId();
        registrarAccion("Creó proyecto: $nombre (ID: $id_nuevo)");
        $exito = 'Proyecto creado correctamente';
    } else {
        $error = 'Completa todos los campos obligatorios';
    }
}

$tipos     = $pdo->query("SELECT * FROM tipos_proyecto ORDER BY nombre ASC")->fetchAll();
$contratos = $pdo->query("
    SELECT c.id_contrato, cl.nombre as cliente, c.estado
    FROM contratos c
    JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
    JOIN clientes cl ON cl.id_cliente = co.id_cliente
    WHERE c.estado = 'activo'
    ORDER BY cl.nombre ASC
")->fetchAll();
?>

<?php require_once '../../modules/layouts/header.php'; ?>

<nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index.php"> Proyectos</a></li>
    <li class="breadcrumb-item active" aria-current="page">Crear proyecto</li>
  </ol>
</nav>

<h4 class="mb-4 fw-semibold">➕ Nuevo Proyecto</h4>

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
      <label class="form-label" for="nombre">Nombre: *</label>
      <input class="form-control" type="text" id="nombre" name="nombre" required>
    </div>
    <div class="mb-3">
      <label class="form-label" for="descripcion">Descripción: *</label>
      <textarea class="form-control" id="descripcion" name="descripcion" rows="3" cols="50" required></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label" for="ubicacion">Ubicación: *</label><br>
      <input class="form-control" type="text" id="ubicacion" name="ubicacion" required>
    </div>
    <div class="mb-3">
      <label class="form-label" for="id_tipo_proyecto">Tipo de proyecto: *</label>
      <select class="form-select" id="id_tipo_proyecto" name="id_tipo_proyecto" required>
          <option value="">-- Selecciona --</option>
          <?php foreach ($tipos as $t): ?>
              <option value="<?= $t['id_tipo_proyecto'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
          <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label" for="id_contrato">Contrato activo: *</label><br>
      <select class="form-select" id="id_contrato" name="id_contrato" required>
          <option value="">-- Selecciona --</option>
          <?php foreach ($contratos as $c): ?>
              <option value="<?= $c['id_contrato'] ?>">
                  #<?= $c['id_contrato'] ?> — <?= htmlspecialchars($c['cliente']) ?>
              </option>
          <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label" for="fecha_inicio">Fecha de inicio: *</label>
      <input class="form-control" type="date" id="fecha_inicio" name="fecha_inicio" required>
    </div>
    <div class="mb-3">
      <label class="form-label" for="fecha_fin_estimada">Fecha fin estimada: *</label><br>
      <input class="form-control" type="date" id="fecha_fin_estimada" name="fecha_fin_estimada" required>
    </div>
    <div class="mb-3">
      <label class="form-label" for="estado" >Estado inicial:</label>
      <select class="form-select" id="estado" name="estado">
          <option value="planificacion">Planificación</option>
          <option value="ejecucion">Ejecución</option>
      </select>
    </div>
      <button class="btn btn-primary" type="submit">Crear proyecto</button>
    </form>
    </div>
  </div>
</div><!-- end col -->
</div><!-- end row -->

<?php require_once '../../modules/layouts/footer.php'; ?>