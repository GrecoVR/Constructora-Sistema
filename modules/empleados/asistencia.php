<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
require_once '../../utils/fecha.php';

requierePermiso('registrar_asistencia');
registrarAccion('Vio módulo de asistencia');

$pdo   = conectar();

$permisos = $_SESSION['permisos'];

$error = '';
$exito = '';

// Filtro por fecha
$fecha_filtro = $_GET['fecha'] ?? date('Y-m-d');

// Registrar asistencia
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_asignacion = intval($_POST['id_asignacion'] ?? 0);
    $fecha         = $_POST['fecha'] ?? date('Y-m-d');
    $hora_entrada  = $_POST['hora_entrada'] ?? '';
    $hora_salida   = $_POST['hora_salida'] ?? '';

    if ($id_asignacion && $fecha && $hora_entrada) {
        // Verifica que no exista ya para esa fecha
        $stmt = $pdo->prepare("
            SELECT id_asistencia FROM asistencia
            WHERE id_asignacion = ? AND fecha = ?
        ");
        $stmt->execute([$id_asignacion, $fecha]);

        if ($stmt->fetch()) {
            $error = 'Ya existe un registro de asistencia para ese empleado en esa fecha';
        } else {
            $stmt2 = $pdo->prepare("
                INSERT INTO asistencia (id_asignacion, fecha, hora_entrada, hora_salida)
                VALUES (?, ?, ?, ?)
            ");
            $stmt2->execute([
                $id_asignacion, $fecha, $hora_entrada,
                $hora_salida ?: null
            ]);
            registrarAccion("Registró asistencia asignación ID: $id_asignacion fecha: $fecha");
            $exito = 'Asistencia registrada correctamente';
        }
    } else {
        $error = 'Completa los campos obligatorios';
    }
}

// Asignaciones activas para el dropdown
$asignaciones = $pdo->query("
    SELECT a.id_asignacion, e.nombre as empleado,
           p.nombre as proyecto, c.nombre as cargo
    FROM asignaciones a
    JOIN empleados e ON e.id_empleado = a.id_empleado
    JOIN proyectos p ON p.id_proyecto = a.id_proyecto
    JOIN cargos c ON c.id_cargo = a.id_cargo
    WHERE a.fecha_fin IS NULL
    AND e.estado = 'activo'
    ORDER BY e.nombre ASC
")->fetchAll();

// Registros del día filtrado
$registros = $pdo->prepare("
    SELECT ast.id_asistencia, ast.hora_entrada, ast.hora_salida,
           e.nombre as empleado, p.nombre as proyecto, c.nombre as cargo,
           TIMEDIFF(ast.hora_salida, ast.hora_entrada) as horas_trabajadas
    FROM asistencia ast
    JOIN asignaciones a ON a.id_asignacion = ast.id_asignacion
    JOIN empleados e ON e.id_empleado = a.id_empleado
    JOIN proyectos p ON p.id_proyecto = a.id_proyecto
    JOIN cargos c ON c.id_cargo = a.id_cargo
    WHERE ast.fecha = ?
    ORDER BY e.nombre ASC
");
$registros->execute([$fecha_filtro]);
$registros = $registros->fetchAll();
?>

<?php require_once '../../modules/layouts/header.php'; ?>

<nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="../../modules/dashboard/dashboard.php"> Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page"> Asistencia</li>
  </ol>
</nav>

<h2 class="mb-4 fw-semibold">📋 Registro de Asistencia</h2>

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
<div class="col-md-8 col-sm-6 col-xs-12">
<!-- REGISTRAR -->
<div class="card shadow mt-2">
  <div class="card-header">
      <h4 class="mb-0">Registrar asistencia</h4>
  </div>   
  <div class="card-body">
  <form method="POST">
    <div class="mb-3">
    <label class="form-label" for="id_asignacion">Empleado / Asignación: *</label>
    <select class="form-select" id="id_asignacion" name="id_asignacion" required>
        <option value="">-- Selecciona --</option>
        <?php foreach ($asignaciones as $a): ?>
            <option value="<?= $a['id_asignacion'] ?>">
                <?= htmlspecialchars($a['empleado']) ?>
                — <?= htmlspecialchars($a['proyecto']) ?>
                (<?= htmlspecialchars($a['cargo']) ?>)
            </option>
        <?php endforeach; ?>
    </select>
    </div>
    <div class="mb-3">
    <label class="form-label" for="fecha">Fecha: *</label>
    <input class="form-control" type="date" id="fecha" name="fecha" value="<?= $fecha_filtro ?>">
    </div>
    <div class="mb-3">
    <label class="form-label" for="hora_entrada">Hora de entrada: *</label>
    <input class="form-control" type="time" id="hora_entrada" name="hora_entrada" value="07:00">
    </div>
    <div class="mb-3">
    <label class="form-label" for="hora_salida">Hora de salida:</label>
    <input class="form-control" type="time" id="hora_salida" name="hora_salida" value="17:00">
    </div>
    <button class="btn btn-primary" type="submit">Registrar</button>
    </form>
  </div>
</div>
</div><!-- end col -->
<div class="col-md-4 col-sm-6 col-xs-12">
<!-- VER POR FECHA -->
<div class="card shadow mt-2">
  <div class="card-header">
      <h4 class="mb-0">Asistencia del día</h4>
  </div>   
  <div class="card-body">
  <form method="GET">
      <input class="form-control" type="date" name="fecha" value="<?= $fecha_filtro ?>">
      <button class="btn btn-primary mt-2" type="submit">Ver</button>
  </form>
  </div>
</div>
</div><!-- end col -->
</div><!-- end row -->

<?php if ($registros): ?>
<div class="card shadow">
<div class="card-body table-responsive">
    <table id="tabla-datos" class="table table-striped table-bordered">
      <thead>
        <tr>
            <th>Empleado</th>
            <th>Proyecto</th>
            <th>Cargo</th>
            <th>Entrada</th>
            <th>Salida</th>
            <th>Horas</th>
        </tr>
       </thead>
       <tbody>
        <?php foreach ($registros as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['empleado']) ?></td>
                <td><?= htmlspecialchars($r['proyecto']) ?></td>
                <td><?= htmlspecialchars($r['cargo']) ?></td>
                <td><?= $r['hora_entrada'] ?></td>
                <td><?= $r['hora_salida'] ?? '—' ?></td>
                <td><?= $r['hora_salida'] ? $r['horas_trabajadas'] : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</div>

<?php else: ?>
    <p>No hay registros de asistencia para el <?= formatoFechaCorta($fecha_filtro) ?>.</p>
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