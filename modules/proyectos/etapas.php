<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
require_once '../../triggers/TriggerManager.php';

requierePermiso('editar_proyectos');

$pdo   = conectar();
$id    = intval($_GET['id'] ?? 0);
$error = '';
$exito = '';

if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM proyectos WHERE id_proyecto = ?");
$stmt->execute([$id]);
$proyecto = $stmt->fetch();

if (!$proyecto) { header('Location: index.php'); exit; }

// Actualizar avance de etapa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_etapa'])) {
    $id_etapa   = intval($_POST['id_etapa'] ?? 0);
    $porcentaje = intval($_POST['porcentaje_avance'] ?? 0);
    $estado     = $_POST['estado_etapa'] ?? 'ejecucion';

    if ($id_etapa && $porcentaje >= 0 && $porcentaje <= 100) {
        $stmt2 = $pdo->prepare("
            UPDATE etapas_proyecto
            SET porcentaje_avance = ?, estado = ?
            WHERE id_etapa_proyecto = ? AND id_proyecto = ?
        ");
        $stmt2->execute([$porcentaje, $estado, $id_etapa, $id]);

        // Dispara trigger de etapa completada
        $manager = new TriggerManager($pdo);
        $manager->ejecutar('proyectos.etapa_completada', [
            'id_etapa'          => $id_etapa,
            'porcentaje_avance' => $porcentaje
        ]);

        registrarAccion("Actualizó etapa ID: $id_etapa al $porcentaje%");
        $exito = 'Etapa actualizada correctamente';
    } else {
        $error = 'Datos incorrectos';
    }
}

// Crear nueva etapa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_etapa'])) {
    $nombre      = trim($_POST['nombre_etapa'] ?? '');
    $descripcion = trim($_POST['descripcion_etapa'] ?? '');
    $fecha_ini   = $_POST['fecha_inicio_etapa'] ?? '';
    $fecha_fin   = $_POST['fecha_fin_etapa'] ?? '';

    if ($nombre && $descripcion && $fecha_ini && $fecha_fin) {
        $stmt3 = $pdo->prepare("
            INSERT INTO etapas_proyecto 
            (id_proyecto, nombre, descripcion, porcentaje_avance, fecha_inicio, fecha_fin, estado)
            VALUES (?, ?, ?, 0, ?, ?, 'planificacion')
        ");
        $stmt3->execute([$id, $nombre, $descripcion, $fecha_ini, $fecha_fin]);
        registrarAccion("Creó nueva etapa en proyecto ID: $id");
        $exito = 'Etapa creada correctamente';
    } else {
        $error = 'Completa todos los campos de la nueva etapa';
    }
}

// Recarga etapas
$etapas = $pdo->prepare("SELECT * FROM etapas_proyecto WHERE id_proyecto = ? ORDER BY fecha_inicio ASC");
$etapas->execute([$id]);
$etapas = $etapas->fetchAll();
?>

<?php require_once '../../modules/layouts/header.php'; ?>

<div class="p-4">

<nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index.php"> Detalle</a></li>
    <li class="breadcrumb-item active" aria-current="page">Etapas</li>
  </ol>
</nav>

<h3>📋 Etapas — <?= htmlspecialchars($proyecto['nombre']) ?></h3>

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

<!-- ETAPAS ACTUALES -->
<div class="card shadow mt-3" style="width:400px;">
  <div class="card-header">
      <h4 class="mb-0">Etapas actuales</h4>
  </div>   
  <div class="card-body">
    <?php foreach ($etapas as $e): ?>
        <fieldset style="margin-bottom:15px">
            <legend><strong><?= htmlspecialchars($e['nombre']) ?></strong> — <?= $e['porcentaje_avance'] ?>%</legend>
            
            <p><?= htmlspecialchars($e['descripcion']) ?></p>
            
            <div class="progress" role="progressbar" aria-label="porcentaje avance" aria-valuenow="<?= $e['porcentaje_avance'] ?>" aria-valuemin="0" aria-valuemax="100">
              <div class="progress-bar" style="width: <?= $e['porcentaje_avance'] ?>%"><?= $e['porcentaje_avance'] ?></div>
            </div>

            <form method="POST" style="display:inline">
                <input type="hidden" name="id_etapa" value="<?= $e['id_etapa_proyecto'] ?>">
                <div class="mb-3">
                <label class="form-label" for="porcentaje_avance">Avance %:</label>
                <input class="form-control" type="number" id="porcentaje_avance" name="porcentaje_avance" min="0" max="100"
                       value="<?= $e['porcentaje_avance'] ?>" style="width:60px">
                </div>
                <div class="mb-3">
                <label class="form-label" for="estado_etapa">Estado:</label>
                <select class="form-select" id="estado_etapa" name="estado_etapa">
                    <?php foreach (['planificacion','ejecucion','pausado','finalizado','cancelado'] as $est): ?>
                        <option value="<?= $est ?>" <?= $e['estado'] === $est ? 'selected' : '' ?>>
                            <?= ucfirst($est) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                </div>
                <button class="btn btn-success" type="submit" name="actualizar_etapa">Actualizar</button>
            </form>
        </fieldset>
    <?php endforeach; ?>
    </div>
 </div>



<!-- NUEVA ETAPA -->
<div class="card shadow mt-2" style="width:400px;">
  <div class="card-header">
      <h4 class="mb-0">➕ Agregar nueva etapa</h4>
  </div>   
  <div class="card-body">
    <form method="POST">
        <div class="mb-3">
        <label class="form-label" for="nombre_etapa">Nombre: *</label>
        <input class="form-control" type="text" id="nombre_etapa" name="nombre_etapa" required>
        </div>
        <div class="mb-3">
        <label class="form-label" for="descripcion_etapa">Descripción: *</label>
        <textarea class="form-control" id="descripcion_etapa" name="descripcion_etapa" rows="3" cols="50" required></textarea>
        </div>
        <div class="mb-3">
        <label class="form-label" for="fecha_inicio_etapa">Fecha inicio: *</label>
        <input class="form-control" type="date" id="fecha_inicio_etapa" name="fecha_inicio_etapa" required>
        </div>
        <div class="mb-3">
        <label class="form-label" for="fecha_fin_etapa">Fecha fin: *</label>
        <input class="form-control" type="date" name="fecha_fin_etapa" name="fecha_fin_etapa" required>
        </div>
        <button class="btn btn-primary" type="submit" name="nueva_etapa">Crear etapa</button>
    </form>
  </div>
</div>

</div>
<?php require_once '../../modules/layouts/footer.php'; ?>
