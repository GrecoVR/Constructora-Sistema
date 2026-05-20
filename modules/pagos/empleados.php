<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
require_once '../../utils/fecha.php';
require_once '../../triggers/TriggerManager.php';

requierePermiso('gestionar_pagos');
registrarAccion('Vio módulo pagos empleados');

$pdo   = conectar();
$error = '';
$exito = '';

// Registrar pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_pago'])) {
    $id_empleado  = intval($_POST['id_empleado'] ?? 0);
    $id_metodo    = intval($_POST['id_metodo_pago'] ?? 0);
    $fecha        = $_POST['fecha_pago'] ?? date('Y-m-d');
    $monto        = floatval($_POST['monto'] ?? 0);
    $estado       = $_POST['estado'] ?? 'pendiente';

    if ($id_empleado && $id_metodo && $monto > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO pagos_empleados (id_empleado, id_metodo_pago, fecha_pago, monto, estado)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$id_empleado, $id_metodo, $fecha, $monto, $estado]);
        $id_pago = $pdo->lastInsertId();

        // Trigger si el pago falló
        if ($estado === 'fallido') {
            $manager = new TriggerManager($pdo);
            $manager->ejecutar('pagos.pago_empleado_fallido', [
                'id_pago_empleado' => $id_pago,
                'id_empleado'      => $id_empleado
            ]);
        }

        registrarAccion("Registró pago empleado ID: $id_empleado por Bs $monto");
        $exito = 'Pago registrado correctamente';
    } else {
        $error = 'Completa todos los campos';
    }
}

// Registrar ajuste
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_ajuste'])) {
    $id_pago_empleado = intval($_POST['id_pago_empleado'] ?? 0);
    $tipo_ajuste      = $_POST['tipo_ajuste'] ?? '';
    $concepto         = trim($_POST['concepto'] ?? '');
    $monto            = floatval($_POST['monto_ajuste'] ?? 0);

    if ($id_pago_empleado && $tipo_ajuste && $concepto && $monto != 0) {
        $monto_real = $tipo_ajuste === 'deduccion' ? -abs($monto) : abs($monto);

        $stmt = $pdo->prepare("
            INSERT INTO ajustes_pago (id_pago_empleado, tipo_ajuste, concepto, monto)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$id_pago_empleado, $tipo_ajuste, $concepto, $monto_real]);

        // Notifica al empleado del ajuste
        $manager = new TriggerManager($pdo);
        $manager->ejecutar('pagos.ajuste_aplicado', [
            'id_pago_empleado' => $id_pago_empleado,
            'tipo_ajuste'      => $tipo_ajuste,
            'concepto'         => $concepto,
            'monto'            => $monto_real
        ]);

        registrarAccion("Registró ajuste en pago ID: $id_pago_empleado");
        $exito = 'Ajuste registrado correctamente';
    } else {
        $error = 'Completa todos los campos del ajuste';
    }
}

$empleados = $pdo->query("
    SELECT id_empleado, nombre FROM empleados
    WHERE estado = 'activo' ORDER BY nombre ASC
")->fetchAll();

$metodos = $pdo->query("SELECT * FROM metodos_pago ORDER BY nombre ASC")->fetchAll();

// Pagos recientes con ajustes
$pagos = $pdo->query("
    SELECT pe.id_pago_empleado, pe.fecha_pago, pe.monto, pe.estado,
           e.nombre as empleado, mp.nombre as metodo,
           COALESCE(SUM(ap.monto), 0) as total_ajustes
    FROM pagos_empleados pe
    JOIN empleados e ON e.id_empleado = pe.id_empleado
    JOIN metodos_pago mp ON mp.id_metodo_pago = pe.id_metodo_pago
    LEFT JOIN ajustes_pago ap ON ap.id_pago_empleado = pe.id_pago_empleado
    GROUP BY pe.id_pago_empleado, pe.fecha_pago, pe.monto,
             pe.estado, e.nombre, mp.nombre
    ORDER BY pe.fecha_pago DESC
    LIMIT 30
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pagos Empleados — Vértice</title>
</head>
<body>

<h2>💳 Pagos a Empleados</h2>
<a href="../../dashboard.php">← Volver al dashboard</a>
&nbsp;&nbsp;
<a href="pedidos.php">Ver pagos pedidos</a>

<br><br>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
<?php if ($exito): ?>
    <p style="color:green"><?= $exito ?></p>
<?php endif; ?>

<!-- REGISTRAR PAGO -->
<h3>Registrar pago</h3>
<form method="POST">
    <label>Empleado: *</label><br>
    <select name="id_empleado" required>
        <option value="">-- Selecciona --</option>
        <?php foreach ($empleados as $e): ?>
            <option value="<?= $e['id_empleado'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Método de pago: *</label><br>
    <select name="id_metodo_pago" required>
        <option value="">-- Selecciona --</option>
        <?php foreach ($metodos as $m): ?>
            <option value="<?= $m['id_metodo_pago'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Monto (Bs): *</label><br>
    <input type="number" name="monto" step="0.01" min="0.01" required><br><br>

    <label>Fecha de pago:</label><br>
    <input type="date" name="fecha_pago" value="<?= date('Y-m-d') ?>"><br><br>

    <label>Estado:</label><br>
    <select name="estado">
        <option value="completado">Completado</option>
        <option value="pendiente">Pendiente</option>
        <option value="fallido">Fallido</option>
    </select><br><br>

    <button type="submit" name="registrar_pago">Registrar pago</button>
</form>

<hr>

<!-- REGISTRAR AJUSTE -->
<h3>Registrar ajuste (bono o descuento)</h3>
<form method="POST">
    <label>Pago a ajustar: *</label><br>
    <select name="id_pago_empleado" required>
        <option value="">-- Selecciona pago --</option>
        <?php foreach ($pagos as $p): ?>
            <option value="<?= $p['id_pago_empleado'] ?>">
                #<?= $p['id_pago_empleado'] ?> — <?= htmlspecialchars($p['empleado']) ?>
                (<?= formatoFechaCorta($p['fecha_pago']) ?> — Bs <?= number_format($p['monto'], 2) ?>)
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Tipo de ajuste: *</label><br>
    <select name="tipo_ajuste" required>
        <option value="percepcion">Bono / Percepción</option>
        <option value="deduccion">Descuento / Deducción</option>
    </select><br><br>

    <label>Concepto: *</label><br>
    <input type="text" name="concepto" required style="width:400px"><br><br>

    <label>Monto (Bs): *</label><br>
    <input type="number" name="monto_ajuste" step="0.01" min="0.01" required><br><br>

    <button type="submit" name="registrar_ajuste">Registrar ajuste</button>
</form>

<hr>

<!-- HISTORIAL -->
<h3>Últimos 30 pagos</h3>
<table border="1" cellpadding="8">
    <tr>
        <th>ID</th>
        <th>Empleado</th>
        <th>Fecha</th>
        <th>Método</th>
        <th>Monto (Bs)</th>
        <th>Ajustes (Bs)</th>
        <th>Total real (Bs)</th>
        <th>Estado</th>
    </tr>
    <?php foreach ($pagos as $p): ?>
        <tr>
            <td><?= $p['id_pago_empleado'] ?></td>
            <td><?= htmlspecialchars($p['empleado']) ?></td>
            <td><?= formatoFechaCorta($p['fecha_pago']) ?></td>
            <td><?= htmlspecialchars($p['metodo']) ?></td>
            <td><?= number_format($p['monto'], 2) ?></td>
            <td style="color:<?= $p['total_ajustes'] >= 0 ? 'green' : 'red' ?>">
                <?= number_format($p['total_ajustes'], 2) ?>
            </td>
            <td><strong><?= number_format($p['monto'] + $p['total_ajustes'], 2) ?></strong></td>
            <td style="color:<?= $p['estado'] === 'completado' ? 'green' : ($p['estado'] === 'fallido' ? 'red' : 'orange') ?>">
                <?= ucfirst($p['estado']) ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>