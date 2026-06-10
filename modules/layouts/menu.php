 <?php
$current_url = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

function sb_item($href, $icon, $label, $current_dir, $match_dir) {
    $active = ($current_dir === $match_dir) ? 'active' : '';
    echo '<a href="' . $href . '" class="sb-item ' . $active . '">';
    echo '<i class="bi bi-' . $icon . '"></i>';
    echo '<span>' . $label . '</span>';
    echo '</a>';
}
?>

<a href="../../modules/dashboard/dashboard.php"
   class="sb-item <?= $current_dir === 'dashboard' ? 'active' : '' ?>">
    <i class="bi bi-house"></i>
    <span>Inicio</span>
</a>

<?php if (in_array('ver_proyectos', $permisos)): ?>
<a href="../../modules/proyectos/index.php"
   class="sb-item <?= $current_dir === 'proyectos' ? 'active' : '' ?>">
    <i class="bi bi-building-fill-gear"></i>
    <span>Proyectos</span>
</a>
<?php endif; ?>

<?php if (in_array('gestionar_materiales', $permisos)): ?>
<a href="../../modules/materiales/index.php"
   class="sb-item <?= $current_dir === 'materiales' ? 'active' : '' ?>">
    <i class="bi bi-boxes"></i>
    <span>Materiales</span>
</a>
<?php endif; ?>

<?php if (in_array('registrar_movimientos', $permisos)): ?>
<a href="../../modules/materiales/movimientos.php"
   class="sb-item <?= $current_dir === 'materiales' ? 'active' : '' ?>">
    <i class="bi bi-box"></i>
    <span>Movimientos</span>
</a>
<?php endif; ?>

<?php if (in_array('gestionar_pedidos', $permisos)): ?>
<a href="../../modules/materiales/pedidos.php"
   class="sb-item <?= ($current_dir === 'materiales' && $current_url === 'pedidos.php') ? 'active' : '' ?>">
    <i class="bi bi-cart3"></i>
    <span>Pedidos</span>
</a>
<?php endif; ?>

<?php if (in_array('gestionar_pagos', $permisos)): ?>
<a href="../../modules/pagos/index.php"
   class="sb-item <?= $current_dir === 'pagos' ? 'active' : '' ?>">
    <i class="bi bi-credit-card"></i>
    <span>Pagos</span>
</a>
<?php endif; ?>

<?php if (in_array('ver_empleados', $permisos)): ?>
<a href="../../modules/empleados/index.php"
   class="sb-item <?= $current_dir === 'empleados' ? 'active' : '' ?>">
    <i class="bi bi-people"></i>
    <span>Empleados</span>
</a>
<?php endif; ?>

<?php if (in_array('ver_contratos', $permisos)): ?>
<a href="../../modules/contratos/index.php"
   class="sb-item <?= $current_dir === 'contratos' ? 'active' : '' ?>">
    <i class="bi bi-file-earmark-text"></i>
    <span>Contratos</span>
</a>
<?php endif; ?>

<?php if (in_array('gestionar_empleados', $permisos)): ?>
<a href="../../modules/notificaciones/index.php"
   class="sb-item <?= $current_dir === 'notificaciones' ? 'active' : '' ?>">
    <i class="bi bi-bell"></i>
    <span>Notificaciones</span>
</a>
<?php endif; ?>

<?php if (in_array('ver_reportes_financieros', $permisos)): ?>
<a href="../../modules/reportes/dashboard.php"
   class="sb-item <?= $current_dir === 'reportes' ? 'active' : '' ?>">
    <i class="bi bi-bar-chart-line"></i>
    <span>Reportes</span>
</a>
<?php endif; ?>

<?php if (in_array('configurar_sistema', $permisos)): ?>
<a href="../../modules/usuarios/index.php"
   class="sb-item <?= $current_dir === 'usuarios' ? 'active' : '' ?>">
    <i class="bi bi-shield-lock"></i>
    <span>Usuarios</span>
</a>
<?php endif; ?>

<?php if (in_array('ver_auditoria', $permisos)): ?>
<a href="../../modules/logs/index.php"
   class="sb-item <?= $current_dir === 'logs' ? 'active' : '' ?>">
    <i class="bi bi-clock-history"></i>
    <span>Registros</span>
</a>
<?php endif; ?>