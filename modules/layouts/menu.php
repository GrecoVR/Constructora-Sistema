      <span class="d-print-block">Menu</span>
      <hr>
      <ul class="nav nav-pills flex-column">
        <li>
          <a href="../../modules/dashboard/dashboard.php" class="nav-link link-body-emphasis">
            <i class="bi bi-speedometer2"></i> Dashboard
          </a>
        </li>
        <?php if (in_array('gestionar_pedidos', $permisos)): ?>
        <li>
          <a href="../../modules/materiales/pedidos.php" class="nav-link link-body-emphasis">
            <i class="bi bi-table"></i> Pedidos
          </a>
        </li>
        <?php endif; ?>
        <?php if (in_array('gestionar_materiales', $permisos)): ?>
        <li>
          <a href="../../modules/materiales/index.php" class="nav-link link-body-emphasis">
            <i class="bi bi-grid"></i> Materiales
          </a>
        </li>
        <?php endif; ?>
        
        <?php if (in_array('gestionar_empleados', $permisos)): ?>
        <li>
          <a href="../../modules/empleados/index.php" class="nav-link link-body-emphasis">
            <i class="bi bi-people-fill"></i> Empleados
          </a>
        </li>
        <?php endif; ?>
        <?php if (in_array('ver_proyectos', $permisos)): ?>
        <li>
          <a href="../../modules/proyectos/index.php" class="nav-link link-body-emphasis">
            <i class="bi bi-building-fill-gear"></i> Proyectos
          </a>
        </li>
        <?php endif; ?>
        <?php if (in_array('gestionar_contratos', $permisos)): ?>
        <li>
          <a href="../../modules/contratos/index.php" class="nav-link link-body-emphasis">
            <i class="bi bi-clipboard2"></i> Contratos
          </a>
        </li>
        <?php endif; ?>
        <?php if (in_array('configurar_sistema', $permisos)): ?>
        <li>
          <a href="../../modules/usuarios/index.php" class="nav-link link-body-emphasis">
            <i class="bi bi-person-circle"></i> Usuarios Sistema
          </a>
        </li>
        <?php endif; ?>
      </ul>
      <hr>