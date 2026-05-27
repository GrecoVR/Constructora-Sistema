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

<div class="p-4">
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
    <div class="col-md-4 col-sm-6 col-xs-12">
      <div class="card shadow mb-4">
        <div class="card-header">
            <h4 class="mb-0">💰 Resumen financiero</h4>
        </div> 
        <div class="card-body"> 
        <?php
        $stmt = $pdo->query("
            SELECT 
                (SELECT COALESCE(SUM(monto),0) FROM pagos_cliente WHERE estado = 'completado') as ingresos,
                (SELECT COALESCE(SUM(monto),0) FROM pagos_empleados WHERE estado = 'completado') as gastos_personal,
                (SELECT COALESCE(SUM(monto),0) FROM gastos) as gastos_obra
        ");
        $fin = $stmt->fetch();
        ?>
        <p>✅ Ingresos recibidos: <strong>Bs <?= number_format($fin['ingresos'], 2) ?></strong></p>
        <p>👷 Pagos personal: <strong>Bs <?= number_format($fin['gastos_personal'], 2) ?></strong></p>
        <p>🏗️ Gastos de obra: <strong>Bs <?= number_format($fin['gastos_obra'], 2) ?></strong></p>
      </div>
    </div>
   </div>
  <?php endif; ?>

  <?php if (in_array('registrar_movimientos', $permisos)): ?>
  <div class="col-md-4 col-sm-6 col-xs-12">
    <div class="card shadow mb-4">
      <div class="card-header">
          <h4 class="mb-0">🔄 Movimientos de Inventario</h4>
      </div> 
      <div class="card-body"> 
        <p>Puedes registrar entradas, salidas y ajustes de materiales.</p>
        <a class="btn btn-primary" href="../materiales/movimientos.php">
            Registrar movimiento
        </a>
      </div>
    </div>
  </div>
  <?php endif; ?>
  
  <?php if (in_array('registrar_asistencia', $permisos)): ?>
  <div class="col-md-4 col-sm-6 col-xs-12">
    <div class="card shadow mb-4">
      <div class="card-header">
          <h4 class="mb-0">📋 Asistencia</h4>
      </div> 
      <div class="card-body"> 
      <p>Registra la asistencia del personal en obra.</p>
      <a class="btn btn-primary" href="../../modules/empleados/asistencia.php">
          Registrar asistencia
      </a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (in_array('gestionar_materiales', $permisos)): ?>
  <div class="col-md-4 col-sm-6 col-xs-12">
    <div class="card shadow mb-4">
      <div class="card-header">
          <h4 class="mb-0">🧱 Gestión de Materiales</h4>
      </div> 
      <div class="card-body"> 
      <p>Administra el catálogo de materiales del sistema.</p>
      <a class="btn btn-primary" href="../../modules/materiales/index.php">
          Ver materiales
      </a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (in_array('gestionar_pedidos', $permisos)): ?>
  <div class="col-md-4 col-sm-6 col-xs-12">
    <div class="card shadow mb-4">
      <div class="card-header">
          <h4 class="mb-0">🛒 Pedidos a Proveedores</h4>
      </div> 
      <div class="card-body"> 
      <p>Crea y gestiona pedidos de materiales.</p>
      <a class="btn btn-primary" href="../../modules/materiales/pedidos.php">
          Ver pedidos
      </a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (in_array('gestionar_contratos', $permisos)): ?>
  <div class="col-md-4 col-sm-6 col-xs-12">
    <div class="card shadow mb-4">
      <div class="card-header">
          <h4 class="mb-0">📄 Contratos y Cotizaciones</h4>
      </div> 
      <div class="card-body"> 
      <p>Gestiona contratos activos y cotizaciones pendientes.</p>
      <a class="btn btn-primary" href="../../modules/contratos/index.php">
         Ver contratos
      </a>
      <a class="btn btn-secondary" href="../../modules/contratos/cotizaciones.php">
          Ver cotizaciones
      </a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (in_array('gestionar_pagos', $permisos)): ?>
  <div class="col-md-4 col-sm-6 col-xs-12">
    <div class="card shadow mb-4">
      <div class="card-header">
          <h4 class="mb-0">💳 Pagos</h4>
      </div> 
      <div class="card-body"> 
      <p>Procesa pagos a empleados y proveedores.</p>
      <a class="btn btn-primary" class="btn btn-primary" href="../../modules/pagos/empleados.php">
          Pagos empleados
      </a>
      <a class="btn btn-primary" href="../../modules/pagos/pedidos.php">
          Pagos pedidos
      </a>
      </div>
    </div>
  </div>
  <?php endif; ?>
  
  <?php if (in_array('gestionar_empleados', $permisos)): ?>
  <div class="col-md-4 col-sm-6 col-xs-12">
    <div class="card shadow mb-4">
      <div class="card-header">
          <h4 class="mb-0">👥 Gestión de Empleados</h4>
      </div> 
      <div class="card-body"> 
      <p>Administra el personal de la empresa.</p>
      <a href="../../modules/empleados/index.php">
         Ver empleados
      </a>
      <a href="../../modules/empleados/crear.php">
          Nuevo empleado
      </a>
      </div>
    </div>
  </div>
  <?php endif; ?>
  
  <?php if (in_array('gestionar_proveedores', $permisos)): ?>
  <div class="col-md-4 col-sm-6 col-xs-12">
    <div class="card shadow mb-4">
      <div class="card-header">
          <h4 class="mb-0">🏭 Proveedores</h4>
      </div> 
      <div class="card-body"> 
      <p>Administra el catálogo de proveedores.</p>
      <a class="btn btn-primary" href="../../modules/proveedores/index.php">
          Ver proveedores
      </a>
      </div>
    </div>
  </div>
  <?php endif; ?>
  
  <?php if (in_array('crear_proyectos', $permisos)): ?>
  <div class="col-md-4 col-sm-6 col-xs-12">
    <div class="card shadow mb-4">
      <div class="card-header">
          <h4 class="mb-0">➕ Nuevo Proyecto</h4>
      </div> 
      <div class="card-body"> 
      <p>Crea un nuevo proyecto en el sistema.</p>
      <a class="btn btn-primary" href="../../modules/proyectos/crear.php">
          Crear proyecto
      </a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (in_array('configurar_sistema', $permisos)): ?>
  <div class="col-md-4 col-sm-6 col-xs-12">
    <div class="card shadow mb-4">
      <div class="card-header">
          <h4 class="mb-0">⚙️ Configuración del Sistema</h4>
      </div> 
      <div class="card-body"> 
      <p>Gestiona usuarios, roles y permisos.</p>
      <a class="btn btn-primary" href="../../modules/usuarios/index.php">
          Gestionar usuarios
      </a>
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

  <?php if (in_array('ver_auditoria', $permisos)): ?>
      <div class="card shadow mb-4">
      <div class="card-header">
          <h4 class="mb-0">🔍 Últimas acciones en el sistema</h4>
      </div> 
      <div class="card-body table-responsive"> 
      <?php
      $stmt = $pdo->query("
          SELECT rs.accion, rs.fecha_hora, us.nombre_usuario
          FROM registros_sistema rs
          JOIN usuarios_sistema us ON us.id_usuario_sistema = rs.id_usuario_sistema
          ORDER BY rs.fecha_hora DESC
          LIMIT 10
      ");
      $logs = $stmt->fetchAll();
      ?>
      <table class="tabla-datos table table-striped table-bordered">
        <thead>
          <tr>
              <th>Usuario</th>
              <th>Acción</th>
              <th>Fecha y hora</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $log): ?>
              <tr>
                  <td><?= htmlspecialchars($log['nombre_usuario']) ?></td>
                  <td><?= htmlspecialchars($log['accion']) ?></td>
                  <td><?= $log['fecha_hora'] ?></td>
              </tr>
          <?php endforeach; ?>
         </tbody>
      </table>
    </div><!-- end card-body -->
    </div><!-- end card -->
  <?php endif; ?>
  
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