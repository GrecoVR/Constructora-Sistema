<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
require_once '../../utils/fecha.php';

requierePermiso('ver_proyectos');

$pdo = conectar();
$id  = intval($_GET['id'] ?? 0);

if (!$id) { header('Location: index.php'); exit; }

// Info del proyecto
$stmt = $pdo->prepare("
    SELECT p.*, tp.nombre as tipo,
           cl.nombre as cliente, cl.telefono, cl.email
    FROM proyectos p
    JOIN tipos_proyecto tp ON tp.id_tipo_proyecto = p.id_tipo_proyecto
    JOIN contratos c ON c.id_contrato = p.id_contrato
    JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
    JOIN clientes cl ON cl.id_cliente = co.id_cliente
    WHERE p.id_proyecto = ?
");
$stmt->execute([$id]);
$proyecto = $stmt->fetch();

if (!$proyecto) { header('Location: index.php'); exit; }

registrarAccion("Vio detalle proyecto ID: $id");

// Etapas
$etapas = $pdo->prepare("
    SELECT * FROM etapas_proyecto WHERE id_proyecto = ? ORDER BY fecha_inicio ASC
");
$etapas->execute([$id]);
$etapas = $etapas->fetchAll();

// Personal asignado
$personal = $pdo->prepare("
    SELECT e.nombre, ca.nombre as cargo, a.fecha_inicio, a.fecha_fin
    FROM asignaciones a
    JOIN empleados e ON e.id_empleado = a.id_empleado
    JOIN cargos ca ON ca.id_cargo = a.id_cargo
    WHERE a.id_proyecto = ?
    ORDER BY e.nombre ASC
");
$personal->execute([$id]);
$personal = $personal->fetchAll();

// Materiales usados
$materiales = $pdo->prepare("
    SELECT m.nombre, um.cantidad, um.fecha, a.nombre as almacen
    FROM uso_materiales um
    JOIN materiales m ON m.id_material = um.id_material
    JOIN almacenes a ON a.id_almacen = um.id_almacen
    WHERE um.id_proyecto = ?
    ORDER BY um.fecha DESC
");
$materiales->execute([$id]);
$materiales = $materiales->fetchAll();

// Gastos
$gastos = $pdo->prepare("
    SELECT concepto, monto, fecha FROM gastos
    WHERE id_proyecto = ? ORDER BY fecha DESC
");
$gastos->execute([$id]);
$gastos = $gastos->fetchAll();
$total_gastos = array_sum(array_column($gastos, 'monto'));

// Avance promedio
$avance_promedio = count($etapas) > 0
    ? round(array_sum(array_column($etapas, 'porcentaje_avance')) / count($etapas))
    : 0;
?>

<?php require_once '../../modules/layouts/header.php'; ?>

<div class="p-4">

<h2>📁 <?= htmlspecialchars($proyecto['nombre']) ?></h2>

<a href="index.php">← Volver a proyectos</a>

<?php if (in_array('editar_proyectos', $_SESSION['permisos'])): ?>
    &nbsp;&nbsp;
    <a href="editar.php?id=<?= $id ?>">✏️ Editar</a>
    &nbsp;&nbsp;
    <a href="etapas.php?id=<?= $id ?>">📋 Gestionar etapas</a>
<?php endif; ?>

<hr>

<!-- INFO GENERAL -->
<div class="card shadow mt-2">
  <div class="card-header">
      <h4 class="mb-0">📌 Información general</h4>
  </div>   
  <div class="card-body table-responsive">
  <table class="table table-striped table-bordered">
      <tr><td><strong>Tipo</strong></td><td><?= htmlspecialchars($proyecto['tipo']) ?></td></tr>
      <tr><td><strong>Cliente</strong></td><td><?= htmlspecialchars($proyecto['cliente']) ?></td></tr>
      <tr><td><strong>Contacto</strong></td><td><?= $proyecto['telefono'] ?> — <?= $proyecto['email'] ?></td></tr>
      <tr><td><strong>Ubicación</strong></td><td><?= htmlspecialchars($proyecto['ubicacion']) ?></td></tr>
      <tr><td><strong>Estado</strong></td><td><?= ucfirst($proyecto['estado']) ?></td></tr>
      <tr><td><strong>Inicio</strong></td><td><?= formatoFechaCorta($proyecto['fecha_inicio']) ?></td></tr>
      <tr><td><strong>Fin estimado</strong></td><td><?= estadoFecha($proyecto['fecha_fin_estimada']) ?></td></tr>
      <tr><td><strong>Avance promedio</strong></td><td><?= $avance_promedio ?>%</td></tr>
  </table>
  </div>
</div>



<!-- ETAPAS -->
<div class="card shadow mt-2">
  <div class="card-header">
      <h4 class="mb-0">📋 Etapas del proyecto</h4>
  </div>   
  <div class="card-body table-responsive">
    <table class="table table-striped table-bordered">
        <tr>
            <th>Etapa</th>
            <th>Estado</th>
            <th>Avance</th>
            <th>Fecha inicio</th>
            <th>Fecha fin</th>
        </tr>
        <?php foreach ($etapas as $e): ?>
            <tr>
                <td><?= htmlspecialchars($e['nombre']) ?></td>
                <td><?= ucfirst($e['estado']) ?></td>
                <td>
                    <?= $e['porcentaje_avance'] ?>%
                    <progress value="<?= $e['porcentaje_avance'] ?>" max="100"></progress>
                </td>
                <td><?= formatoFechaCorta($e['fecha_inicio']) ?></td>
                <td><?= estadoFecha($e['fecha_fin']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
  </div>
</div>

<!-- PERSONAL -->
<div class="card shadow mt-2">
  <div class="card-header">
      <h4 class="mb-0">👷 Personal asignado</h4>
  </div>   
  <div class="card-body table-responsive">
    <table class="table table-striped table-bordered">
        <tr>
            <th>Empleado</th>
            <th>Cargo</th>
            <th>Desde</th>
            <th>Hasta</th>
        </tr>
        <?php foreach ($personal as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['nombre']) ?></td>
                <td><?= htmlspecialchars($p['cargo']) ?></td>
                <td><?= formatoFechaCorta($p['fecha_inicio']) ?></td>
                <td><?= $p['fecha_fin'] ? formatoFechaCorta($p['fecha_fin']) : 'Activo' ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
  </div>
</div>

<!-- MATERIALES -->
<div class="card shadow mt-2">
  <div class="card-header">
      <h4 class="mb-0">🧱 Materiales usados</h4>
  </div>   
  <div class="card-body table-responsive">
    <table class="table table-striped table-bordered">
        <tr>
            <th>Material</th>
            <th>Almacén</th>
            <th>Cantidad</th>
            <th>Fecha</th>
        </tr>
        <?php foreach ($materiales as $m): ?>
            <tr>
                <td><?= htmlspecialchars($m['nombre']) ?></td>
                <td><?= htmlspecialchars($m['almacen']) ?></td>
                <td><?= $m['cantidad'] ?></td>
                <td><?= formatoFechaCorta($m['fecha']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
   </div>
</div>

<!-- GASTOS -->
<div class="card shadow mt-2">
  <div class="card-header">
      <h4 class="mb-0">💰 Gastos del proyecto</h4>
  </div>   
  <div class="card-body table-responsive">
    <table class="table table-striped table-bordered">
        <tr>
            <th>Concepto</th>
            <th>Monto (Bs)</th>
            <th>Fecha</th>
        </tr>
        <?php foreach ($gastos as $g): ?>
            <tr>
                <td><?= htmlspecialchars($g['concepto']) ?></td>
                <td><?= number_format($g['monto'], 2) ?></td>
                <td><?= formatoFechaCorta($g['fecha']) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td><strong>TOTAL</strong></td>
            <td><strong>Bs <?= number_format($total_gastos, 2) ?></strong></td>
            <td></td>
        </tr>
    </table>
  </div>
</div>

</div>
<?php require_once '../../modules/layouts/footer.php'; ?>