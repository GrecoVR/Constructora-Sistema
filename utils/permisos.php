<?php
// Define qué permiso necesita cada módulo
// Se usa en middleware/roles.php para verificar acceso

const PERMISOS_MODULOS = [
    // Usuarios
    'usuarios.ver'      => 'configurar_sistema',
    'usuarios.crear'    => 'configurar_sistema',
    'usuarios.editar'   => 'configurar_sistema',
    'usuarios.roles'    => 'configurar_sistema',

    // Proyectos
    'proyectos.ver'     => 'ver_proyectos',
    'proyectos.crear'   => 'crear_proyectos',
    'proyectos.editar'  => 'editar_proyectos',

    // Materiales
    'materiales.ver'    => 'gestionar_materiales',
    'materiales.crear'  => 'gestionar_materiales',
    'materiales.editar' => 'gestionar_materiales',
    'movimientos.ver'   => 'registrar_movimientos',
    'pedidos.ver'       => 'gestionar_pedidos',

    // Empleados
    'empleados.ver'     => 'ver_empleados',
    'empleados.crear'   => 'gestionar_empleados',
    'empleados.editar'  => 'gestionar_empleados',
    'asistencia.ver'    => 'registrar_asistencia',

    // Contratos
    'contratos.ver'     => 'ver_contratos',
    'contratos.crear'   => 'gestionar_contratos',
    'cotizaciones.ver'  => 'ver_cotizaciones',

    // Pagos
    'pagos.ver'         => 'gestionar_pagos',

    // Reportes
    'reportes.ver'      => 'ver_reportes_financieros',
    'inventario.reporte'=> 'ver_inventarios',

    // Notificaciones
    'notificaciones.ver'=> 'ver_dashboard',

    // Auditoría
    'auditoria.ver'     => 'ver_auditoria',
];

// Función para verificar permiso desde cualquier módulo
function tienePermiso(string $modulo): bool {
    if (!isset(PERMISOS_MODULOS[$modulo])) return false;
    $permiso_requerido = PERMISOS_MODULOS[$modulo];
    return in_array($permiso_requerido, $_SESSION['permisos'] ?? []);
}