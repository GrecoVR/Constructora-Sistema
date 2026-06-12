<?php
$current_url = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>

<a href="../../modules/dashboard/dashboard.php"
   class="sb-item <?= $current_dir === 'dashboard' ? 'active' : '' ?>"
   data-tooltip="Dashboard">
    <i class="bi bi-house"></i>
    <span class="sb-item-label">Inicio</span>
</a>

<?php if (in_array('ver_proyectos', $permisos)): ?>
<a href="../../modules/proyectos/index.php"
   class="sb-item <?= $current_dir === 'proyectos' ? 'active' : '' ?>"
   data-tooltip="Proyectos">
    <i class="bi bi-building-fill-gear"></i>
    <span class="sb-item-label">Proyectos</span>
</a>
<?php endif; ?>

<?php if (in_array('gestionar_materiales', $permisos)): ?>
<a href="../../modules/materiales/index.php"
   class="sb-item <?= ($current_dir === 'materiales' && $current_url === 'index.php') ? 'active' : '' ?>"
   data-tooltip="Materiales">
    <i class="bi bi-boxes"></i>
    <span class="sb-item-label">Materiales</span>
</a>
<?php endif; ?>

<?php if (in_array('registrar_movimientos', $permisos)): ?>
<a href="../../modules/materiales/movimientos.php"
   class="sb-item <?= ($current_dir === 'materiales' && $current_url === 'movimientos.php') ? 'active' : '' ?>"
   data-tooltip="Movimientos">
    <i class="bi bi-arrow-left-right"></i>
    <span class="sb-item-label">Movimientos</span>
</a>
<?php endif; ?>

<?php if (in_array('gestionar_pedidos', $permisos)): ?>
<a href="../../modules/materiales/pedidos.php"
   class="sb-item <?= ($current_dir === 'materiales' && $current_url === 'pedidos.php') ? 'active' : '' ?>"
   data-tooltip="Pedidos">
    <i class="bi bi-cart3"></i>
    <span class="sb-item-label">Pedidos</span>
</a>
<?php endif; ?>

<?php if (in_array('gestionar_pagos', $permisos)): ?>
<a href="../../modules/pagos/index.php"
   class="sb-item <?= $current_dir === 'pagos' ? 'active' : '' ?>"
   data-tooltip="Pagos">
    <i class="bi bi-credit-card"></i>
    <span class="sb-item-label">Pagos</span>
</a>
<?php endif; ?>

<?php if (in_array('ver_empleados', $permisos)): ?>
<a href="../../modules/empleados/index.php"
   class="sb-item <?= $current_dir === 'empleados' ? 'active' : '' ?>"
   data-tooltip="Empleados">
    <i class="bi bi-people"></i>
    <span class="sb-item-label">Empleados</span>
</a>
<?php endif; ?>

<?php if (in_array('ver_contratos', $permisos)): ?>
<a href="../../modules/contratos/index.php"
   class="sb-item <?= $current_dir === 'contratos' ? 'active' : '' ?>"
   data-tooltip="Contratos">
    <i class="bi bi-file-earmark-text"></i>
    <span class="sb-item-label">Contratos</span>
</a>
<?php endif; ?>

<?php if (in_array('gestionar_empleados', $permisos)): ?>
<a href="../../modules/notificaciones/index.php"
   class="sb-item <?= $current_dir === 'notificaciones' ? 'active' : '' ?>"
   data-tooltip="Notificaciones">
    <i class="bi bi-bell"></i>
    <span class="sb-item-label">Notificaciones</span>
</a>
<?php endif; ?>

<?php if (in_array('ver_reportes_financieros', $permisos)): ?>
<a href="../../modules/reportes/dashboard.php"
   class="sb-item <?= $current_dir === 'reportes' ? 'active' : '' ?>"
   data-tooltip="Reportes">
    <i class="bi bi-bar-chart-line"></i>
    <span class="sb-item-label">Reportes</span>
</a>
<?php endif; ?>

<?php if (in_array('configurar_sistema', $permisos)): ?>
<a href="../../modules/usuarios/index.php"
   class="sb-item <?= $current_dir === 'usuarios' ? 'active' : '' ?>"
   data-tooltip="Usuarios">
    <i class="bi bi-shield-lock"></i>
    <span class="sb-item-label">Usuarios</span>
</a>
<?php endif; ?>

<?php if (in_array('ver_auditoria', $permisos)): ?>
<a href="../../modules/logs/index.php"
   class="sb-item <?= $current_dir === 'logs' ? 'active' : '' ?>"
   data-tooltip="Registros">
    <i class="bi bi-clock-history"></i>
    <span class="sb-item-label">Registros</span>
</a>
<?php endif; ?>