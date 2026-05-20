<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
require_once '../../utils/fecha.php';
require_once '../../triggers/TriggerManager.php';

requierePermiso('ver_cotizaciones');
registrarAccion('Vio cotizaciones');

$pdo   = conectar();
$error = '';
$exito = '';

// Cambiar estado de cotización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $id_cotizacion = intval($_POST['id_cotizacion'] ?? 0);
    $nuevo_estado  = $_POST['nuevo_estado'] ?? '';

    $estados_validos = ['pendiente', 'aprobada', 'rechazada'];

    if ($id_cotizacion && in_array($nuevo_estado, $estados_validos)) {
        $stmt = $pdo->prepare("
            UPDATE cotizaciones SET estado = ? WHERE id_cotizacion = ?
        ");
        $stmt->execute([$nuevo_estado, $id_cotizacion]);

        // Si se aprobó dispara el trigger
        if ($nuevo_estado === 'aprobada') {
            $manager = new TriggerManager($pdo);
            $manager->ejecutar('contratos.cotizacion_aprobada', [
                'id_cotizacion' => $id_cotizacion
            ]);
        }

        registrarAccion("Cambió estado cotización ID: $id_cotizacion a $nuevo_estado");
        $exito = 'Estado actualizado correctamente';
    } else {
        $error = 'Datos incorrectos';
    }
}

$cotizaciones = $pdo->query("
    SELECT co.id_cotizacion, co.fecha_creacion, co.estado, co.monto_total,
           cl.nombre as cliente
    FROM cotizaciones co
    JOIN clientes cl ON cl.id_cliente = co.id_cliente
    ORDER BY co.fecha_creacion DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotizaciones — Vértice</title>
</head>
<body>

<h2>📋 Cotizaciones</h2>
<a href="index.php">← Volver a contratos</a>
<a href="../../dashboard.php">← Dashboard</a>

<br><br>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
<?php if ($exito): ?>
    <p style="color:green"><?= $exito ?></p>
<?php endif; ?>

<table border="1" cellpadding="8">
    <tr>
        <th>ID</th>
        <th>Cliente</th>
        <th>Fecha</th>
        <th>Monto (Bs)</th>
        <th>Estado</th>
        <th>Acciones</th>
    </tr>
    <?php foreach ($cotizaciones as $co): ?>
        <tr>
            <td><?= $co['id_cotizacion'] ?></td>
            <td><?= htmlspecialchars($co['cliente']) ?></td>
            <td><?= formatoFechaCorta($co['fecha_creacion']) ?></td>
            <td><?= number_format($co['monto_total'], 2) ?></td>
            <td>
                <?php
                $color = match($co['estado']) {
                    'aprobada'  => 'green',
                    'rechazada' => 'red',
                    default     => 'orange'
                };
                ?>
                <span style="color:<?= $color ?>"><?= ucfirst($co['estado']) ?></span>
            </td>
            <td>
                <?php if ($co['estado'] === 'pendiente' && in_array('gestionar_cotizaciones', $_SESSION['permisos'])): ?>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="id_cotizacion" value="<?= $co['id_cotizacion'] ?>">
                        <input type="hidden" name="nuevo_estado" value="aprobada">
                        <button type="submit" name="cambiar_estado"
                                onclick="return confirm('¿Aprobar esta cotización?')">
                            ✅ Aprobar
                        </button>
                    </form>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="id_cotizacion" value="<?= $co['id_cotizacion'] ?>">
                        <input type="hidden" name="nuevo_estado" value="rechazada">
                        <button type="submit" name="cambiar_estado"
                                onclick="return confirm('¿Rechazar esta cotización?')">
                            ❌ Rechazar
                        </button>
                    </form>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>