<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../config/database.php';


$pdo      = conectar();
$permisos = $_SESSION['permisos'];
$roles    = $_SESSION['roles'];
$nombre   = $_SESSION['nombre'];
?>

<?php require_once '../layouts/header.php'; ?>

  <h3>Bienvenido, <?= htmlspecialchars($nombre) ?></h3>
  <div class="mb-2 mt-3">
    <strong>Roles:</strong>
    <?php foreach ($roles as $r): ?>
    <span class="badge text-bg-secondary"><?= $r ?></span>
    <?php endforeach; ?>
  </div>
  <div class="mb-4">
   <strong>Permisos:</strong> <span><?= implode(', ', $permisos) ?></span>
  </div>


<div class="row">
<?php if (in_array('ver_reportes_financieros', $permisos)): ?>
<?php
        $stmt = $pdo->query("
            SELECT
                (SELECT COALESCE(SUM(monto),0) FROM pagos_cliente WHERE estado = 'completado') as ingresos,
                (SELECT COALESCE(SUM(monto),0) FROM pagos_empleados WHERE estado = 'completado') as gastos_personal,
                (SELECT COUNT(*) FROM pagos_cliente WHERE estado='pendiente') AS pagos_pend_count,
                (SELECT COALESCE(SUM(monto),0) FROM gastos) as gastos_obra
        ");
        $fin = $stmt->fetch();
 ?>
<!-- Metric Cards -->
  <div class="row mb-4">
      <div class="col-xl-3 col-md-6 mb-4">
          <div class="card border-left-primary shadow h-100 py-2">
              <div class="card-body">
                  <div class="row no-gutters align-items-center">
                      <div class="col mr-2">
                          <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Ingresos Recibidos</div>
                          <div class="h5 mb-0 font-weight-bold text-gray-800">Bs <?= number_format($fin['ingresos'], 2) ?></div>
                      </div>
                      <div class="col-auto">
                          <i class="bi bi-currency-dollar fs-2 text-secondary"></i>
                      </div>
                  </div>
              </div>
          </div>
      </div>

      <div class="col-xl-3 col-md-6 mb-4">
          <div class="card border-left-success shadow h-100 py-2">
              <div class="card-body">
                  <div class="row no-gutters align-items-center">
                      <div class="col mr-2">
                          <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Pagos Personal</div>
                          <div class="h5 mb-0 font-weight-bold text-gray-800">Bs <?= number_format($fin['gastos_personal'], 2) ?></div>
                      </div>
                      <div class="col-auto">
                          <i class="bi bi-cash fs-2 text-secondary"></i>
                      </div>
                  </div>
              </div>
          </div>
      </div>

      <div class="col-xl-3 col-md-6 mb-4">
          <div class="card border-left-info shadow h-100 py-2">
              <div class="card-body">
                  <div class="row no-gutters align-items-center">
                      <div class="col mr-2">
                          <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Gastos de Obra</div>
                          <div class="row no-gutters align-items-center">
                              <div class="col-auto">
                                  <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">Bs <?= number_format($fin['gastos_obra'], 2) ?></div>
                              </div>
                          </div>
                      </div>
                      <div class="col-auto">
                          <i class="bi bi-clipboard-data fs-2 text-secondary"></i>
                      </div>
                  </div>
              </div>
          </div>
      </div>

      <div class="col-xl-3 col-md-6 mb-4">
          <div class="card border-left-warning shadow h-100 py-2">
              <div class="card-body">
                  <div class="row no-gutters align-items-center">
                      <div class="col mr-2">
                          <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pagos pendientes</div>
                          <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $fin['pagos_pend_count'] ?></div>
                      </div>
                      <div class="col-auto">
                          <i class="bi bi-chat-dots fs-2 text-secondary"></i>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </div>
<?php endif; ?>
  
</div> <!-- end row -->


  <?php if (in_array('ver_proyectos', $permisos)): ?>
  <div class="card shadow mb-4">
      <div class="card-header">
          <h4 class="mb-0">📁 Proyectos</h4>
      </div>
      <div class="card-body table-responsive">
      <?php
      // Si es gerente o director ve todos
      // Si es jefe de obras solo ve los suyos
      if (in_array('ver_dashboard', $permisos) && in_array('gestionar_contratos', $permisos)) {
          // Rol gerencial — ve todos
          $stmt = $pdo->query("
              SELECT p.nombre, p.estado, p.fecha_fin_estimada, tp.nombre as tipo
              FROM proyectos p
              JOIN tipos_proyecto tp ON tp.id_tipo_proyecto = p.id_tipo_proyecto
              WHERE p.estado IN ('ejecucion', 'planificacion')
              ORDER BY p.fecha_fin_estimada ASC
          ");
      } else {
          // Rol operativo — solo los asignados
          $stmt = $pdo->prepare("
              SELECT p.nombre, p.estado, p.fecha_fin_estimada, tp.nombre as tipo
              FROM proyectos p
              JOIN tipos_proyecto tp ON tp.id_tipo_proyecto = p.id_tipo_proyecto
              JOIN asignaciones a ON a.id_proyecto = p.id_proyecto
              JOIN usuarios_sistema us ON us.id_empleado = a.id_empleado
              WHERE us.id_usuario_sistema = ?
              AND p.estado IN ('ejecucion', 'planificacion')
              ORDER BY p.fecha_fin_estimada ASC
          ");
          $stmt->execute([$_SESSION['id_usuario']]);
      }
      $proyectos = $stmt->fetchAll();
      ?>

      <?php if ($proyectos): ?>
          <table class="tabla-datos table table-striped table-bordered">
          <thead>
              <tr>
                  <th>Proyecto</th>
                  <th>Tipo</th>
                  <th>Estado</th>
                  <th>Fecha fin estimada</th>
              </tr>
           </thead>
           <tbody>
              <?php foreach ($proyectos as $p): ?>
                  <tr>
                      <td><?= htmlspecialchars($p['nombre']) ?></td>
                      <td><?= htmlspecialchars($p['tipo']) ?></td>
                      <td><?= $p['estado'] ?></td>
                      <td><?= $p['fecha_fin_estimada'] ?></td>
                  </tr>
              <?php endforeach; ?>
           </tbody>
          </table>
      <?php else: ?>
          <p>No tienes proyectos asignados.</p>
      <?php endif; ?>
  </div><!-- end card-body -->
  </div><!-- end card -->
  <?php endif; ?>


  <?php if (in_array('ver_inventarios', $permisos)): ?>
      <div class="card shadow mb-4">
      <div class="card-header">
          <h4 class="mb-0">📦 Inventario con stock bajo</h4>
      </div>
      <div class="card-body table-responsive">
      <?php
      $stmt = $pdo->query("
          SELECT m.nombre, i.stock, i.stock_minimo, a.nombre as almacen
          FROM inventarios i
          JOIN materiales m ON m.id_material = i.id_material
          JOIN almacenes a ON a.id_almacen = i.id_almacen
          WHERE i.stock <= i.stock_minimo
          ORDER BY i.stock ASC
      ");
      $stock_bajo = $stmt->fetchAll();
      ?>

      <?php if ($stock_bajo): ?>
          <table class="tabla-datos table table-striped table-bordered">
          <thead>
              <tr>
                  <th>Material</th>
                  <th>Almacén</th>
                  <th>Stock actual</th>
                  <th>Stock mínimo</th>
              </tr>
           </thead>
           <tbody>
              <?php foreach ($stock_bajo as $s): ?>
                  <tr>
                      <td><?= htmlspecialchars($s['nombre']) ?></td>
                      <td><?= htmlspecialchars($s['almacen']) ?></td>
                      <td style="color:red"><?= $s['stock'] ?></td>
                      <td><?= $s['stock_minimo'] ?></td>
                  </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
      <?php else: ?>
          <p>Todo el inventario está en niveles normales.</p>
      <?php endif; ?>
  </div><!-- end card-body -->
  </div><!-- end card -->
  <?php endif; ?>


  <?php if (in_array('ver_empleados', $permisos)): ?>
      <div class="card shadow mb-4">
      <div class="card-header">
          <h4 class="mb-0">👷 Empleados activos</h4>
      </div>
      <div class="card-body table-responsive">
      <?php
      $stmt = $pdo->query("
          SELECT e.nombre, MAX(c.nombre) as cargo
          FROM empleados e
          JOIN asignaciones a ON a.id_empleado = e.id_empleado
          JOIN cargos c ON c.id_cargo = a.id_cargo
          WHERE e.estado = 'activo'
          AND a.fecha_fin IS NULL
          GROUP BY e.id_empleado, e.nombre
          ORDER BY e.nombre ASC
  ");
      $empleados = $stmt->fetchAll();
      ?>

      <table class="tabla-datos table table-striped table-bordered">
      <thead>
          <tr>
              <th>Nombre</th>
              <th>Cargo actual</th>
          </tr>
       </thead>
       <tbody>
          <?php foreach ($empleados as $emp): ?>
              <tr>
                  <td><?= htmlspecialchars($emp['nombre']) ?></td>
                  <td><?= htmlspecialchars($emp['cargo']) ?></td>
              </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
  </div><!-- end card-body -->
  </div><!-- end card -->
  <?php endif; ?>

<script>
$(document).ready(function() {
    $('.tabla-datos').DataTable({
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
        }
    });
});
</script>
<?php require_once '../layouts/footer.php'; ?>