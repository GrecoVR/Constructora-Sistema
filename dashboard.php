<?php
require_once 'middleware/auth.php';
require_once 'middleware/logger.php';
require_once 'config/database.php';


$pdo      = conectar();
$permisos = $_SESSION['permisos'];
$roles    = $_SESSION['roles'];
$nombre   = $_SESSION['nombre'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard — Constructora</title>
</head>
<body>

<h2>Bienvenido, <?= htmlspecialchars($nombre) ?></h2>
<p><strong>Roles:</strong> <?= implode(', ', $roles) ?></p>
<p><strong>Permisos:</strong> <?= implode(', ', $permisos) ?></p>
<a href="modules/auth/logout.php">Cerrar sesión</a>
<hr>

<?php if (in_array('ver_proyectos', $permisos)): ?>
    <h3>📁 Proyectos</h3>
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
        <table border="1" cellpadding="8">
            <tr>
                <th>Proyecto</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Fecha fin estimada</th>
            </tr>
            <?php foreach ($proyectos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                    <td><?= htmlspecialchars($p['tipo']) ?></td>
                    <td><?= $p['estado'] ?></td>
                    <td><?= $p['fecha_fin_estimada'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No tienes proyectos asignados.</p>
    <?php endif; ?>
<?php endif; ?>

<hr>

<?php if (in_array('ver_inventarios', $permisos)): ?>
    <h3>📦 Inventario con stock bajo</h3>
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
        <table border="1" cellpadding="8">
            <tr>
                <th>Material</th>
                <th>Almacén</th>
                <th>Stock actual</th>
                <th>Stock mínimo</th>
            </tr>
            <?php foreach ($stock_bajo as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['nombre']) ?></td>
                    <td><?= htmlspecialchars($s['almacen']) ?></td>
                    <td style="color:red"><?= $s['stock'] ?></td>
                    <td><?= $s['stock_minimo'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Todo el inventario está en niveles normales.</p>
    <?php endif; ?>
<?php endif; ?>

<hr>

<?php if (in_array('ver_empleados', $permisos)): ?>
    <h3>👷 Empleados activos</h3>
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

    <table border="1" cellpadding="8">
        <tr>
            <th>Nombre</th>
            <th>Cargo actual</th>
        </tr>
        <?php foreach ($empleados as $emp): ?>
            <tr>
                <td><?= htmlspecialchars($emp['nombre']) ?></td>
                <td><?= htmlspecialchars($emp['cargo']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<hr>

<?php if (in_array('ver_reportes_financieros', $permisos)): ?>
    <h3>💰 Resumen financiero</h3>
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
<?php endif; ?>

<hr>

<?php if (in_array('ver_auditoria', $permisos)): ?>
    <h3>🔍 Últimas acciones en el sistema</h3>
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
    <table border="1" cellpadding="8">
        <tr>
            <th>Usuario</th>
            <th>Acción</th>
            <th>Fecha y hora</th>
        </tr>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= htmlspecialchars($log['nombre_usuario']) ?></td>
                <td><?= htmlspecialchars($log['accion']) ?></td>
                <td><?= $log['fecha_hora'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php if (in_array('registrar_movimientos', $permisos)): ?>
    <h3>🔄 Movimientos de Inventario</h3>
    <p>Puedes registrar entradas, salidas y ajustes de materiales.</p>
    <a href="modules/materiales/movimientos.php">
        <button>Registrar movimiento</button>
    </a>
<?php endif; ?>

<hr>

<?php if (in_array('registrar_asistencia', $permisos)): ?>
    <h3>📋 Asistencia</h3>
    <p>Registra la asistencia del personal en obra.</p>
    <a href="modules/empleados/asistencia.php">
        <button>Registrar asistencia</button>
    </a>
<?php endif; ?>

<hr>

<?php if (in_array('gestionar_materiales', $permisos)): ?>
    <h3>🧱 Gestión de Materiales</h3>
    <p>Administra el catálogo de materiales del sistema.</p>
    <a href="modules/materiales/index.php">
        <button>Ver materiales</button>
    </a>
<?php endif; ?>

<hr>

<?php if (in_array('gestionar_pedidos', $permisos)): ?>
    <h3>🛒 Pedidos a Proveedores</h3>
    <p>Crea y gestiona pedidos de materiales.</p>
    <a href="modules/materiales/pedidos.php">
        <button>Ver pedidos</button>
    </a>
<?php endif; ?>

<hr>

<?php if (in_array('gestionar_contratos', $permisos)): ?>
    <h3>📄 Contratos y Cotizaciones</h3>
    <p>Gestiona contratos activos y cotizaciones pendientes.</p>
    <a href="modules/contratos/index.php">
        <button>Ver contratos</button>
    </a>
    <a href="modules/contratos/cotizaciones.php">
        <button>Ver cotizaciones</button>
    </a>
<?php endif; ?>

<hr>

<?php if (in_array('gestionar_pagos', $permisos)): ?>
    <h3>💳 Pagos</h3>
    <p>Procesa pagos a empleados y proveedores.</p>
    <a href="modules/pagos/empleados.php">
        <button>Pagos empleados</button>
    </a>
    <a href="modules/pagos/pedidos.php">
        <button>Pagos pedidos</button>
    </a>
<?php endif; ?>

<hr>

<?php if (in_array('gestionar_empleados', $permisos)): ?>
    <h3>👥 Gestión de Empleados</h3>
    <p>Administra el personal de la empresa.</p>
    <a href="modules/empleados/index.php">
        <button>Ver empleados</button>
    </a>
    <a href="modules/empleados/crear.php">
        <button>Nuevo empleado</button>
    </a>
<?php endif; ?>

<hr>

<?php if (in_array('gestionar_proveedores', $permisos)): ?>
    <h3>🏭 Proveedores</h3>
    <p>Administra el catálogo de proveedores.</p>
    <a href="modules/proveedores/index.php">
        <button>Ver proveedores</button>
    </a>
<?php endif; ?>

<hr>

<?php if (in_array('crear_proyectos', $permisos)): ?>
    <h3>➕ Nuevo Proyecto</h3>
    <p>Crea un nuevo proyecto en el sistema.</p>
    <a href="modules/proyectos/crear.php">
        <button>Crear proyecto</button>
    </a>
<?php endif; ?>

<hr>

<?php if (in_array('configurar_sistema', $permisos)): ?>
    <h3>⚙️ Configuración del Sistema</h3>
    <p>Gestiona usuarios, roles y permisos.</p>
    <a href="modules/usuarios/index.php">
        <button>Gestionar usuarios</button>
    </a>
<?php endif; ?>

<hr>

</body>
</html>