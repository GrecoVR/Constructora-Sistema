    <div style="min-width:200px;">
      <a class="d-flex align-items-center text-decoration-none link-body-emphasis" href="/">
      <img class="me-2" src="../../public/assets/favicon.png" width="24" height="24">
      <span class="d-print-block">Empresa Constructora</span>
      </a>
      <hr>
      <ul class="nav nav-pills flex-column mb-auto">
        <li>
          <a href="../../modules/dashboard/dashboard.php" class="nav-link link-body-emphasis">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
          </a>
        </li>
        <?php if (in_array('gestionar_pedidos', $permisos)): ?>
        <li>
          <a href="../../modules/materiales/pedidos.php" class="nav-link link-body-emphasis">
            <i class="bi bi-table me-2"></i> Pedidos
          </a>
        </li>
        <?php endif; ?>
        <?php if (in_array('registrar_movimientos', $permisos)): ?>
        <li>
          <a href="../../modules/materiales/movimientos.php" class="nav-link link-body-emphasis">
            <i class="bi bi-file-bar-graph me-2"></i> Inventarios
          </a>
        </li>
        <?php endif; ?>
        <?php if (in_array('gestionar_materiales', $permisos)): ?>
        <li>
          <a href="../../modules/materiales/index.php" class="nav-link link-body-emphasis">
            <i class="bi bi-grid me-2"></i> Materiales
          </a>
        </li>
        <?php endif; ?>
        
        <?php if (in_array('gestionar_pagos', $permisos)): ?>
        <li>
          <a href="../../modules/pagos/index.php" class="nav-link link-body-emphasis">
            <i class="bi bi-credit-card me-2"></i> Pagos
          </a>
        </li>        
        <?php endif; ?>
        
        <?php if (in_array('gestionar_empleados', $permisos)): ?>
        <li>
          <a href="../../modules/empleados/index.php" class="nav-link link-body-emphasis">
            <i class="bi bi-people me-2"></i> Empleados
          </a>
        </li>
        <?php endif; ?>
        <?php if (in_array('ver_proyectos', $permisos)): ?>
        <li>
          <a href="../../modules/proyectos/index.php" class="nav-link link-body-emphasis">
            <i class="bi bi-building-gear me-2"></i> Proyectos
          </a>
        </li>
        <?php endif; ?>
        <?php if (in_array('gestionar_contratos', $permisos)): ?>
        <li>
          <a href="../../modules/contratos/index.php" class="nav-link link-body-emphasis">
            <i class="bi bi-clipboard2 me-2"></i> Contratos
          </a>
        </li>
        <?php endif; ?>
        
        <?php if (in_array('gestionar_empleados', $permisos)): ?>
        <li>
          <a href="../../modules/notificaciones/index.php" class="nav-link link-body-emphasis">
            <i class="bi bi-envelope-at me-2"></i> Notificaciones
          </a>
        </li>
        <?php endif; ?>
        
        <?php if (in_array('ver_reportes_financieros', $permisos)): ?>
        <li>
          <a href="../../modules/reportes/dashboard.php" class="nav-link link-body-emphasis">
            <i class="bi bi-file-bar-graph me-2"></i> Reportes
          </a>
        </li>
        <?php endif; ?>
        
        
        <?php if (in_array('configurar_sistema', $permisos)): ?>
        <li>
          <a href="../../modules/usuarios/index.php" class="nav-link link-body-emphasis">
            <i class="bi bi-person-circle me-2"></i> Usuarios Sistema
          </a>
        </li>
        <?php endif; ?>
        
        <?php if (in_array('ver_auditoria', $permisos)): ?>
        <li>
          <a href="../../modules/logs/index.php" class="nav-link link-body-emphasis">
            <i class="bi bi-clock-history me-2"></i> Registros Sistema
          </a>
        </li>
        <?php endif; ?>
        
      </ul>
      <hr>
    </div>