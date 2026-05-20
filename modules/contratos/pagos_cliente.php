<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
require_once '../../utils/fecha.php';

requierePermiso('gestionar_pagos');

$pdo = conectar();
$id  = intval($_GET['id'] ?? 0);

if (!$id) { header('Location: index.php'); exit; }

$error = '';
$exito = '';

// Info del contrato
$stmt = $pdo->prepare("
    SELECT c.id_contrato, c.estado, c.fecha_firma,
           cl.nombre as cliente, co.monto_total
    FROM contratos c
    JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
    JOIN clientes cl ON cl.id_cliente = co.id_cliente
    WHERE c.id_contrato = ?
");
$stmt->execute([$id]);
$contrato = $stmt->fetch();

if (!$contrato) { header('Location: index.php'); exit; }

registrarAccion("Vio pagos contrato ID: $id");

// Registrar nuevo pago
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_metodo = intval($_POST['id_metodo_pago'] ?? 0);
    $fecha     = $_POST['fecha_pago'] ?? date('Y-m-d');
    $monto     = floatval($_POST['monto'] ?? 0);
    $estado    = $_POST['estado'] ?? 'pendiente';

    if ($id_metodo && $monto > 0) {
        $stmt2 = $pdo->prepare("
            INSERT INTO pagos_cliente (id_contrato, id_metodo_pago, fecha_pago, monto, estado)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt2->execute([$id, $id_metodo, $fecha, $monto, $estado]);
        registrarAccion("Registró pago cliente contrato ID: $id por Bs $monto");
        $exito = 'Pago registrado correctamente';
    } else {
        $error = 'Completa todos los campos';
    }
}

// Pagos existentes
$pagos = $pdo->prepare("
    SELECT pc.id_pago_cliente, pc.fecha_pago, pc.monto,
           pc.estado, mp.nombre as metodo
    FROM pagos_cliente pc
    JOIN metodos_pago mp ON mp.id_metodo_pago = pc.id_metodo_pago
    WHERE pc.id_contrato = ?
    ORDER BY pc.fecha_pago DESC
");
$pagos->execute([$id]);
$pagos = $pagos->fetchAll();

$total_pagado  = array_sum(array_column(
    array_filter($pagos, fn($p) => $p['estado'] === 'completado'), 'monto'
));
$saldo_pendiente = $contrato['monto_total'] - $total_pagado;

$metodos = $pdo->query("SELECT * FROM metodos_pago ORDER BY nombre ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pagos Contrato #<?= $id ?> — Vértice</title>
</head>
<body>

<h2>💳 Pagos — Contrato #<?= $id ?></h2>
<a href="index.php">← Volver a contratos</a>

<br><br>

<!-- RESUMEN -->
<table border="1" cellpadding="8">
    <tr><td><strong>Cliente</strong></td><td><?= htmlspecialchars($contrato['cliente']) ?></td></tr>
    <tr><td><strong>Monto total contrato</strong></td><td>Bs <?= number_format($contrato['monto_total'], 2) ?></td></tr>
    <tr><td><strong>Total pagado</strong></td><td style="color:green">Bs <?= number_format($total_pagado, 2) ?></td></tr>
    <tr>
        <td><strong>Saldo pendiente</strong></td>
        <td style="color:<?= $saldo_pendiente > 0 ? 'red' : 'green' ?>">
            Bs <?= number_format($saldo_pendiente, 2) ?>
        </td>
    </tr>
    <tr><td><strong>Estado contrato</strong></td><td><?= ucfirst($contrato['estado']) ?></td></tr>
</table>

<hr>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
<?php if ($exito): ?>
    <p style="color:green"><?= $exito ?></p>
<?php endif; ?>

<!-- REGISTRAR PAGO -->
<h3>Registrar nuevo pago</h3>
<form method="POST">
    <label>Método de pago: *</label><br>
    <select name="id_metodo_pago" required>
        <option value="">-- Selecciona --</option>
        <?php foreach ($metodos as $m): ?>
            <option value="<?= $m['id_metodo_pago'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Monto (Bs): *</label><br>
    <input type="number" name="monto" step="0.01" min="0.01"
           placeholder="<?= number_format($saldo_pendiente, 2) ?>" required><br><br>

    <label>Fecha de pago: *</label><br>
    <input type="date" name="fecha_pago" value="<?= date('Y-m-d') ?>"><br><br>

    <label>Estado:</label><br>
    <select name="estado">
        <option value="completado">Completado</option>
        <option value="pendiente">Pendiente</option>
    </select><br><br>

    <button type="submit">Registrar pago</button>
</form>

<hr>

<!-- HISTORIAL -->
<h3>Historial de pagos</h3>
<table border="1" cellpadding="8">
    <tr>
        <th>ID</th>
        <th>Fecha</th>
        <th>Método</th>
        <th>Monto (Bs)</th>
        <th>Estado</th>
    </tr>
    <?php foreach ($pagos as $p): ?>
        <tr>
            <td><?= $p['id_pago_cliente'] ?></td>
            <td><?= formatoFechaCorta($p['fecha_pago']) ?></td>
            <td><?= htmlspecialchars($p['metodo']) ?></td>
            <td><?= number_format($p['monto'], 2) ?></td>
            <td style="color:<?= $p['estado'] === 'completado' ? 'green' : ($p['estado'] === 'fallido' ? 'red' : 'orange') ?>">
                <?= ucfirst($p['estado']) ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php if (empty($pagos)): ?>
    <p>No hay pagos registrados para este contrato.</p>
<?php endif; ?>

</body>
</html>