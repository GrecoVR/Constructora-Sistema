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
  
  <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-2">Dashboard</h1>
            <p class="text-muted mb-2">Bienvenido, <?= htmlspecialchars($nombre) ?></p>
            <?php foreach ($roles as $r): ?>
            <span class="badge bg-secondary-subtle text-secondary-emphasis"><?= $r ?></span>
            <?php endforeach; ?>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#exampleModal">
                    <i class="bi bi-eye-fill me-1"></i> Ver permisos
                </button>
                
            <?php if (in_array('ver_proyectos', $permisos)): ?>
            <a class="btn btn-primary btn-sm" href="../../modules/proyectos/index.php">
                    <i class="bi bi-building me-1"></i> Gestionar Proyectos
                </a>
            <?php endif; ?>
        </div>
  </div>
  
  <!-- Stats Cards Row -->
    <div class="row g-3 mb-4">
    <?php if (in_array('ver_reportes_financieros', $permisos)): ?>
    <?php
            $stmt = $pdo->query("
                SELECT
                    (SELECT COALESCE(SUM(monto),0) FROM pagos_cliente WHERE estado = 'completado') as ingresos,
                    (SELECT COALESCE(SUM(monto),0) FROM pagos_empleados WHERE estado = 'completado') as gastos_personal,
                    (SELECT COUNT(*) FROM pagos_cliente WHERE estado='pendiente') AS pagos_pend_count,
                    (SELECT COALESCE(SUM(monto),0) FROM pagos_cliente WHERE estado='pendiente') AS pagos_pend_monto,
                    (SELECT COALESCE(SUM(monto),0) FROM gastos) as gastos_obra
            ");
            $fin = $stmt->fetch();
     ?>
      <div class="col-12 col-md-6 col-lg-3">
          <div class="card shadow-sm">
              <div class="card-body border-start border-4 rounded border-success">
                  <div class="d-flex justify-content-between align-items-center">
                      <div>
                          <h6 class="text-muted mb-2">Total Ingresos</h6>
                          <h4 class="mb-0 fw-semibold">Bs <?= number_format($fin['ingresos'], 2) ?></h4>
                           <small class="text-success">
                              <i class="fas fa-arrow-up me-1"></i> pagos completados
                           </small>
                      </div>
                      <div class="p-3">
                          <i class="bi bi-cash fs-2 text-secondary"></i>
                      </div>
                  </div>
              </div>
          </div>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
          <div class="card shadow-sm">
              <div class="card-body border-start border-4 rounded border-danger">
                  <div class="d-flex justify-content-between align-items-center">
                      <div>
                          <h6 class="text-muted mb-2">Gastos Totales</h6>
                          <h4 class="mb-0 fw-semibold">Bs <?= number_format($fin['gastos_personal'], 2) ?></h4>
                          <small class="text-secondary">
                              <i class="fas fa-arrow-dowb me-1"></i> personal + obra + pedidos
                           </small>
                      </div>
                      <div class="p-3">
                          <i class="bi bi-people-fill fs-2 text-secondary"></i>
                      </div>
                  </div>
              </div>
          </div>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
          <div class="card shadow-sm">
              <div class="card-body border-start border-4 rounded border-primary">
                  <div class="d-flex justify-content-between align-items-center">
                      <div>
                          <h6 class="text-muted mb-2">Balance Neto</h6>
                          <h4 class="mb-0 fw-semibold">Bs <?= number_format($fin['gastos_obra'], 2) ?></h4>
                          <small class="text-success">
                              <i class="fas fa-arrow-dowb me-1"></i> Superavit
                           </small>
                      </div>
                      <div class="p-3">
                          <i class="bi bi-clipboard-data fs-2 text-secondary"></i>
                      </div>
                  </div>
              </div>
          </div>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
          <div class="card shadow-sm">
              <div class="card-body border-start border-4 rounded border-warning">
                  <div class="d-flex justify-content-between align-items-center">
                      <div>
                          <h6 class="text-muted mb-2">Cobros pendientes</h6>
                          <h4 class="mb-0 fw-semibold">Bs <?= number_format($fin['pagos_pend_monto']) ?></h4>
                          <small class="text-warning">
                              <i class="fas fa-arrow-dowb me-1"></i> <?= $fin['pagos_pend_count'] ?> pago(s) por cobrar
                           </small>
                      </div>
                      <div class="p-3">
                          <i class="bi bi-credit-card fs-2 text-secondary"></i>
                      </div>
                  </div>
              </div>
          </div>
      </div>
      <?php endif; ?>
    </div>

<!-- Main Content Area -->
    <div class="row g-3 mb-4">
        <?php if (in_array('ver_auditoria', $permisos)): ?>
        <?php
          $stmt = $pdo->query("
              SELECT rs.accion,rs.descripcion, rs.fecha_hora, us.nombre_usuario
              FROM registros_sistema rs
              JOIN usuarios_sistema us ON us.id_usuario_sistema = rs.id_usuario_sistema
              ORDER BY rs.fecha_hora DESC
              LIMIT 5
          ");
          $logs = $stmt->fetchAll();
        ?>
        <!-- Activity Timeline -->
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Actividad Reciente</h5>
                        <a class="text-decoration-none" href="../../modules/logs/index.php">Ver todos</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="timeline p-2">
                        <?php foreach ($logs as $log): ?>
                          <div class="d-flex mb-3">
                            <div class="flex-shrink-0">
                                <div class="bg-primary bg-opacity-10 p-2 rounded">
                                    <i class="bi bi-person text-primary"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1"><?= htmlspecialchars($log['nombre_usuario']) ?> - <?= htmlspecialchars($log['descripcion']) ?></h6>
                                <p class="text-muted small mb-0">
                                <?php 
                                $fechaPasada = new DateTime($log['fecha_hora']); 
                                $fechaActual = new DateTime(); // Hora y fecha actual
                                $diferencia = $fechaPasada->diff($fechaActual);
                                  // Imprime el tiempo transcurrido
                                echo "Hace " . $diferencia->days . " días, " 
                                . $diferencia->h . " horas y "
                                . $diferencia->i . " minutos.";
                                ?>
                                </p>
                            </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <!-- Quick Actions -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header border-0">
                    <h5 class="mb-0">Acciones Rapidas</h5>
                </div>
                <div class="card-body">
                    <nav class="nav nav-pills flex-column">
                        <?php if (in_array('registrar_movimientos', $permisos)): ?>
                        <a class="nav-link" href="../../modules/materiales/movimientos.php">
                           <i class="bi bi-file-bar-graph me-2"></i> Movimientos de Inventario
                        </a>
                        <?php endif; ?>
                        <?php if (in_array('registrar_asistencia', $permisos)): ?>
                        <a class="nav-link" href="../../modules/empleados/asistencia.php">
                           <i class="bi bi-card-checklist me-2"></i> Registrar Asistencia Empleados
                        </a>
                        <?php endif; ?> 
                        <?php if (in_array('gestionar_materiales', $permisos)): ?>
                        <a class="nav-link" href="../../modules/materiales/index.php">
                           <i class="bi bi-grid me-2"></i> Ver Materiales
                        </a>
                        <?php endif; ?>
                        <?php if (in_array('gestionar_pedidos', $permisos)): ?>
                        <a class="nav-link" href="../../modules/materiales/pedidos.php">
                           <i class="bi bi-table me-2"></i> Ver Pedidos
                        </a>
                        <?php endif; ?>
                        <?php if (in_array('gestionar_contratos', $permisos)): ?>
                        <a class="nav-link" href="../../modules/contratos/index.php">
                           <i class="bi bi-clipboard me-2"></i> Ver Contratos
                        </a>
                        <a class="nav-link" href="../../modules/contratos/cotizaciones.php">
                           <i class="bi bi-coin me-2"></i> Ver Cotizaciones
                        </a>
                        <?php endif; ?>
                        <?php if (in_array('gestionar_pagos', $permisos)): ?>
                        <a class="nav-link" href="../../modules/pagos/pedidos.php">
                           <i class="bi bi-cash me-2"></i> Pagos Pedidos
                        </a>
                        <?php endif; ?>
                        <?php if (in_array('gestionar_empleados', $permisos)): ?>
                        <a class="nav-link" href="../../modules/empleados/index.php">
                           <i class="bi bi-people me-2"></i> Ver Enpleados
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </div>
        
    </div>

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

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabel">Lista de Permisos</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <ul class="list-group">
        <?php foreach ($permisos as $p): ?>
        <li class="list-group-item"><?= $p ?></li>
        <?php endforeach; ?>
        </ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

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