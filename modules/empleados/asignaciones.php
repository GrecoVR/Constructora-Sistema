<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
require_once '../../utils/fecha.php';
require_once '../../triggers/TriggerManager.php';

requierePermiso('ver_empleados');

$pdo = conectar();
$id  = intval($_GET['id'] ?? 0);

if (!$id) { header('Location: index.php'); exit; }

$error = '';
$exito = '';

$stmt = $pdo->prepare("SELECT * FROM empleados WHERE id_empleado = ?");
$stmt->execute([$id]);
$empleado = $stmt->fetch();

if (!$empleado) { header('Location: index.php'); exit; }

registrarAccion("Vio asignaciones empleado ID: $id");

// Nueva asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_asignacion'])) {
    $id_proyecto  = intval($_POST['id_proyecto'] ?? 0);
    $id_cargo     = intval($_POST['id_cargo'] ?? 0);
    $fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-d');

    if ($id_proyecto && $id_cargo) {
        // Verifica que no esté ya asignado activamente
        $stmt2 = $pdo->prepare("
            SELECT id_asignacion FROM asignaciones
            WHERE id_empleado = ? AND id_proyecto = ? AND fecha_fin IS NULL
        ");
        $stmt2->execute([$id, $id_proyecto]);

        if ($stmt2->fetch()) {
            $error = 'El empleado ya está asignado activamente a ese proyecto';
        } else {
            $stmt3 = $pdo->prepare("
                INSERT INTO asignaciones (id_proyecto, id_empleado, id_cargo, fecha_inicio)
                VALUES (?, ?, ?, ?)
            ");
            $stmt3->execute([$id_proyecto, $id, $id_cargo, $fecha_inicio]);

            // Trigger — notifica al empleado
            $manager = new TriggerManager($pdo);
            $manager->ejecutar('empleados.asignacion_nueva', [
                'id_empleado' => $id,
                'id_proyecto' => $id_proyecto,
                'id_cargo'    => $id_cargo
            ]);

            registrarAccion("Asignó empleado ID: $id al proyecto ID: $id_proyecto");
            $exito = 'Asignación registrada correctamente';
        }
    } else {
        $error = 'Selecciona proyecto y cargo';
    }
}

// Finalizar asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_asignacion'])) {
    $id_asignacion = intval($_POST['id_asignacion'] ?? 0);

    if ($id_asignacion) {
        $stmt4 = $pdo->prepare("
            UPDATE asignaciones SET fecha_fin = CURDATE() WHERE id_asignacion = ?
        ");
        $stmt4->execute([$id_asignacion]);
        registrarAccion("Finalizó asignación ID: $id_asignacion");
        $exito = 'Asignación finalizada';
    }
}

// Asignaciones actuales
$asignaciones = $pdo->prepare("
    SELECT a.id_asignacion, a.fecha_inicio, a.fecha_fin,
           p.nombre as proyecto, p.estado as estado_proyecto,
           c.nombre as cargo
    FROM asignaciones a
    JOIN proyectos p ON p.id_proyecto = a.id_proyecto
    JOIN cargos c ON c.id_cargo = a.id_cargo
    WHERE a.id_empleado = ?
    ORDER BY a.fecha_inicio DESC
");
$asignaciones->execute([$id]);
$asignaciones = $asignaciones->fetchAll();

$proyectos = $pdo->query("
    SELECT id_proyecto, nombre FROM proyectos
    WHERE estado IN ('planificacion','ejecucion')
    ORDER BY nombre ASC
")->fetchAll();

$cargos = $pdo->query("SELECT id_cargo, nombre FROM cargos ORDER BY nombre ASC")->fetchAll();
?>

<?php require_once '../../modules/layouts/header.php'; ?>

<nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index.php"> Empleados</a></li>
    <li class="breadcrumb-item active" aria-current="page"> Asignaciones</li>
  </ol>
</nav>

<h2 class="mb-4 fw-semibold">📋 Asignaciones — <?= htmlspecialchars($empleado['nombre']) ?></h2>

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

<!-- ASIGNACIONES ACTUALES -->
<div class="card shadow mt-2">
  <div class="card-header">
      <h4 class="mb-0">Historial de asignaciones</h4>
  </div>   
  <div class="card-body table-responsive">
    <table id="tabla-datos" class="table table-striped table-bordered">
    <thead>
    <tr>
        <th>Proyecto</th>
        <th>Cargo</th>
        <th>Desde</th>
        <th>Hasta</th>
        <th>Acciones</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($asignaciones as $a): ?>
        <tr>
            <td><?= htmlspecialchars($a['proyecto']) ?></td>
            <td><?= htmlspecialchars($a['cargo']) ?></td>
            <td><?= formatoFechaCorta($a['fecha_inicio']) ?></td>
            <td><?= $a['fecha_fin'] ? formatoFechaCorta($a['fecha_fin']) : '<span style="color:green">Activo</span>' ?></td>
            <td>
                <?php if (!$a['fecha_fin'] && in_array('gestionar_empleados', $_SESSION['permisos'])): ?>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="id_asignacion" value="<?= $a['id_asignacion'] ?>">
                        <button class="btn btn-secondary btn-sm" type="submit" name="finalizar_asignacion"
                                onclick="return confirm('¿Finalizar esta asignación?')">
                            Finalizar
                        </button>
                    </form>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php if (empty($asignaciones)): ?>
    <p>Este empleado no tiene asignaciones registradas.</p>
<?php endif; ?>

<hr>

<?php if (in_array('gestionar_empleados', $_SESSION['permisos'])): ?>
<div class="card shadow mt-2">
  <div class="card-header">
      <h4 class="mb-0">➕ Nueva asignación</h4>
  </div>   
  <div class="card-body">
<form method="POST">
    <div class="mb-3">
    <label class="form-label" for="id_proyecto">Proyecto: *</label>
    <select class="form-select" id="id_proyecto" name="id_proyecto" required>
        <option value="">-- Selecciona --</option>
        <?php foreach ($proyectos as $p): ?>
            <option value="<?= $p['id_proyecto'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
        <?php endforeach; ?>
    </select>
    </div>
    <div class="mb-3">
    <label class="form-label" for="id_cargo">Cargo: *</label>
    <select class="form-select" id="id_cargo" name="id_cargo" required>
        <option value="">-- Selecciona --</option>
        <?php foreach ($cargos as $c): ?>
            <option value="<?= $c['id_cargo'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
        <?php endforeach; ?>
    </select>
    </div>
    <div class="mb-3">
    <label class="form-label" for="fecha_inicio">Fecha de inicio:</label>
    <input class="form-control" type="date" id="fecha_inicio" name="fecha_inicio" value="<?= date('Y-m-d') ?>">
    </div>
    <button class="btn btn-primary" type="submit" name="nueva_asignacion">Asignar</button>
</form>
</div>
</div>
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
});    
</script>
<?php require_once '../../modules/layouts/footer.php'; ?>