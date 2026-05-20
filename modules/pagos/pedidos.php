<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
require_once '../../utils/fecha.php';

requierePermiso('gestionar_pagos');
registrarAccion('Vio módulo pagos pedidos');

$pdo   = conectar();
$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pedido = intval($_POST['id_pedido'] ?? 0);
    $id_metodo = intval($_POST['id_metodo_pago'] ?? 0);
    $fecha     = $_POST['fecha_pago'] ?? date('Y-m-d');
    $monto     = floatval($_POST['monto'] ?? 0);
    $estado    = $_POST['estado'] ?? 'completado';

    if ($id_pedido && $id_metodo && $monto > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO pagos_pedidos (id_pedido, id_metodo_pago, fecha_pago, monto, estado)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$id_pedido, $id_metodo, $fecha, $monto, $estado]);
        registrarAccion("Registró pago pedido ID: $id_pedido por Bs $monto");
        $exito = 'Pago registrado correctamente';
    } else {
        $error = 'Completa todos los campos';
    }
}

$pedidos = $pdo->query("
    SELECT p.id_pedido, p.fecha_pedido, p.estado as estado_pedido,
           pr.nombre as proveedor,
           COALESCE(SUM(dp.cantidad * dp.precio_unitario), 0) as total_pedido,
           COALESCE(SUM(pp.monto), 0) as total_pagado
    FROM pedidos p
    JOIN proveedores pr ON pr.id_proveedor = p.id_proveedor
    LEFT JOIN detalle_pedido dp ON dp.id_pedido = p.id_pedido
    LEFT JOIN pagos_pedidos pp ON pp.id_pedido = p.id_pedido AND pp.estado = 'completado'
    GROUP BY p.id_pedido, p.fecha_pedido, p.estado, pr.nombre
    ORDER BY p.fecha_pedido DESC
    LIMIT 30
")->fetchAll();

$metodos = $pdo->query("SELECT * FROM metodos_pago ORDER BY nombre ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pagos Pedidos — Vértice</title>
</head>
<body>

<h2>💳 Pagos a Proveedores</h2>
<a href="../../dashboard.php">← Volver al dashboard</a>
&nbsp;&nbsp;
<a href="empleados.php">Ver pagos empleados</a>

<br><br>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
<?php if ($exito): ?>
    <p style="color:green"><?= $exito ?></p>
<?php endif; ?>

<h3>Registrar pago a proveedor</h3>
<form method="POST">
    <label>Pedido: *</label><br>
    <select name="id_pedido" required>
        <option value="">-- Selecciona pedido --</option>
        <?php foreach ($pedidos as $p): ?>
            <option value="<?= $p['id_pedido'] ?>">
                #<?= $p['id_pedido'] ?> — <?= htmlspecialchars($p['proveedor']) ?>
                (<?= formatoFechaCorta($p['fecha_pedido']) ?>
                — Total: Bs <?= number_format($p['total_pedido'], 2) ?>
                — Pagado: Bs <?= number_format($p['total_pagado'], 2) ?>)
            </option>
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

    <label>Fecha:</label><br>
    <input type="date" name="fecha_pago" value="<?= date('Y-m-d') ?>"><br><br>

    <label>Estado:</label><br>
    <select name="estado">
        <option value="completado">Completado</option>
        <option value="pendiente">Pendiente</option>
    </select><br><br>

    <button type="submit">Registrar pago</button>
</form>

<hr>

<h3>Estado de pagos por pedido</h3>
<table border="1" cellpadding="8">
    <tr>
        <th>Pedido</th>
        <th>Proveedor</th>
        <th>Fecha</th>
        <th>Total pedido (Bs)</th>
        <th>Total pagado (Bs)</th>
        <th>Saldo (Bs)</th>
        <th>Estado pedido</th>
    </tr>
    <?php foreach ($pedidos as $p): ?>
        <?php $saldo = $p['total_pedido'] - $p['total_pagado']; ?>
        <tr>
            <td>#<?= $p['id_pedido'] ?></td>
            <td><?= htmlspecialchars($p['proveedor']) ?></td>
            <td><?= formatoFechaCorta($p['fecha_pedido']) ?></td>
            <td><?= number_format($p['total_pedido'], 2) ?></td>
            <td style="color:green"><?= number_format($p['total_pagado'], 2) ?></td>
            <td style="color:<?= $saldo > 0 ? 'red' : 'green' ?>">
                <?= number_format($saldo, 2) ?>
            </td>
            <td><?= ucfirst($p['estado_pedido']) ?></td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>