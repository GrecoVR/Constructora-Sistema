<?php
require_once __DIR__ . '/../config/database.php';

function registrarAccion(string $accion): void {
    if (!isset($_SESSION['id_usuario'])) return;

    // Enriquecer la acción con contexto del request
    $url     = $_SERVER['REQUEST_URI']      ?? '';
    $metodo  = $_SERVER['REQUEST_METHOD']   ?? '';
    $ip      = $_SERVER['REMOTE_ADDR']      ?? '';
    $usuario = $_SESSION['nombre']          ?? 'Desconocido';

    // Construir mensaje detallado según contexto
    $accion_detallada = _enriquecer_accion($accion, $metodo, $url);

    $pdo  = conectar();
    $stmt = $pdo->prepare("
        INSERT INTO registros_sistema (id_usuario_sistema, accion)
        VALUES (?, ?)
    ");
    $stmt->execute([
        $_SESSION['id_usuario'],
        $accion_detallada
    ]);
}

/**
 * Enriquece el mensaje de acción con detalles del contexto HTTP y POST
 */
function _enriquecer_accion(string $accion, string $metodo, string $url): string {

    // Si viene un POST con datos relevantes, agregar detalles
    if ($metodo === 'POST') {
        $extras = [];

        // ID de entidad modificada
        foreach (['id_proyecto','id_empleado','id_contrato','id_cotizacion',
                  'id_material','id_pedido','id_almacen','id_pago_empleado',
                  'id_etapa','id_asignacion','id_usuario'] as $campo) {
            if (!empty($_POST[$campo])) {
                $label = str_replace('id_', '#', $campo);
                $extras[] = $label . intval($_POST[$campo]);
            }
        }

        // Nombre de entidad
        foreach (['nombre','nombre_usuario','concepto','titulo'] as $campo) {
            if (!empty($_POST[$campo])) {
                $val = trim($_POST[$campo]);
                if (strlen($val) > 0) {
                    $extras[] = '"' . mb_substr($val, 0, 60) . '"';
                    break;
                }
            }
        }

        // Monto si aplica
        if (!empty($_POST['monto'])) {
            $extras[] = 'Bs ' . number_format(floatval($_POST['monto']), 2);
        }

        // Estado nuevo si aplica
        if (!empty($_POST['estado'])) {
            $extras[] = 'estado→' . $_POST['estado'];
        }

        // Porcentaje de avance
        if (!empty($_POST['porcentaje_avance'])) {
            $extras[] = 'avance→' . intval($_POST['porcentaje_avance']) . '%';
        }

        // Tipo de movimiento inventario
        if (!empty($_POST['tipo_movimiento'])) {
            $extras[] = 'tipo→' . $_POST['tipo_movimiento'];
        }

        // Cantidad en movimiento
        if (!empty($_POST['cantidad'])) {
            $extras[] = 'cantidad→' . floatval($_POST['cantidad']);
        }

        // Tipo de ajuste de pago
        if (!empty($_POST['tipo_ajuste'])) {
            $extras[] = 'ajuste→' . $_POST['tipo_ajuste'];
        }

        if (!empty($extras)) {
            $accion .= ' [' . implode(', ', $extras) . ']';
        }
    }

    // Agregar módulo desde la URL
    $modulo = _extraer_modulo($url);
    if ($modulo) {
        $accion = '[' . $modulo . '] ' . $accion;
    }

    return $accion;
}

/**
 * Extrae el nombre del módulo desde la URL
 */
function _extraer_modulo(string $url): string {
    $mapa = [
        'dashboard'      => 'Dashboard',
        'proyectos'      => 'Proyectos',
        'empleados'      => 'Empleados',
        'materiales'     => 'Materiales',
        'contratos'      => 'Contratos',
        'cotizaciones'   => 'Cotizaciones',
        'pagos'          => 'Pagos',
        'pedidos'        => 'Pedidos',
        'reportes'       => 'Reportes',
        'usuarios'       => 'Usuarios',
        'notificaciones' => 'Notificaciones',
        'logs'           => 'Registros',
        'auth'           => 'Autenticación',
        'almacen'        => 'Almacén',
        'inventario'     => 'Inventario',
    ];

    foreach ($mapa as $clave => $nombre) {
        if (str_contains($url, $clave)) {
            return $nombre;
        }
    }

    return '';
}

// ── Acciones predefinidas más descriptivas ────────────────────────────────────
// Se usan llamando registrarAccion() con estas constantes o strings directos

define('LOG_LOGIN',              'Inició sesión en el sistema');
define('LOG_LOGOUT',             'Cerró sesión del sistema');
define('LOG_VER_DASHBOARD',      'Accedió al dashboard principal');
define('LOG_VER_PROYECTOS',      'Consultó listado de proyectos');
define('LOG_CREAR_PROYECTO',     'Creó un nuevo proyecto');
define('LOG_EDITAR_PROYECTO',    'Modificó datos de un proyecto existente');
define('LOG_VER_DETALLE_PROY',   'Consultó el detalle completo de un proyecto');
define('LOG_CREAR_ETAPA',        'Agregó una nueva etapa al proyecto');
define('LOG_ACTUALIZAR_ETAPA',   'Actualizó el avance de una etapa del proyecto');
define('LOG_VER_EMPLEADOS',      'Consultó listado de empleados');
define('LOG_CREAR_EMPLEADO',     'Registró un nuevo empleado en el sistema');
define('LOG_EDITAR_EMPLEADO',    'Modificó los datos de un empleado');
define('LOG_ASIGNAR_EMPLEADO',   'Asignó un empleado a un proyecto');
define('LOG_FINALIZAR_ASIG',     'Finalizó la asignación de un empleado');
define('LOG_REG_ASISTENCIA',     'Registró asistencia de personal en obra');
define('LOG_VER_MATERIALES',     'Consultó el catálogo de materiales');
define('LOG_CREAR_MATERIAL',     'Creó un nuevo material en el catálogo');
define('LOG_EDITAR_MATERIAL',    'Modificó un material del catálogo');
define('LOG_REG_MOVIMIENTO',     'Registró movimiento de inventario');
define('LOG_ACTUALIZAR_MOVIMIENTO', 'Actualizó movimiento de inventario');
define('LOG_VER_MOVIMIENTOS',    'Consultó historial de movimientos de inventario');
define('LOG_CREAR_PEDIDO',       'Creó un nuevo pedido a proveedor');
define('LOG_VER_PEDIDOS',        'Consultó listado de pedidos a proveedores');
define('LOG_VER_CONTRATOS',      'Consultó listado de contratos');
define('LOG_CREAR_CONTRATO',     'Creó un nuevo contrato');
define('LOG_VER_COTIZACIONES',   'Consultó listado de cotizaciones');
define('LOG_CAMBIAR_ESTADO_COTIZ', 'Cambió el estado de una cotización');
define('LOG_VER_PAGOS_CLIENTE',       'Consultó pagos registrados de un contrato');
define('LOG_REG_PAGO_CLIENTE',   'Registró un pago recibido de cliente');
define('LOG_REG_PAGO_EMPLEADO',  'Registró un pago a empleado');
define('LOG_REG_AJUSTE_PAGO',    'Registró un ajuste (bono/descuento) en pago');
define('LOG_REG_PAGO_PEDIDO',    'Registró un pago a proveedor por pedido');
define('LOG_VER_REPORTES_FINANCIEROS', 'Consultó reportes financieros');
define('LOG_VER_REPORTES',      'Consultó reportes del sistema');
define('LOG_VER_INVENTARIO',     'Consultó reporte de inventario');
define('LOG_VER_USUARIOS',       'Consultó listado de usuarios del sistema');
define('LOG_CREAR_USUARIO',      'Creó un nuevo usuario del sistema');
define('LOG_EDITAR_USUARIO',     'Modificó datos de un usuario del sistema');
define('LOG_CAMBIAR_ROLES',      'Actualizó los roles de un usuario');
define('LOG_VER_NOTIFICACIONES_EMPLEADOS', 'Consultó notificaciones enviadas a empleados');
define('LOG_VER_NOTIFICACIONES_CLIENTES', 'Consultó notificaciones enviadas a clientes');
define('LOG_ENVIAR_NOTIF_EMP',   'Envió notificación a empleado(s)');
define('LOG_ENVIAR_NOTIF_CLI',   'Envió notificación a cliente');
define('LOG_VER_LOGS',           'Consultó registros de actividad del sistema');
define('LOG_CAMBIAR_AVATAR',     'Cambió su avatar de perfil');