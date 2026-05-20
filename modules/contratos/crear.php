<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('gestionar_contratos');

$pdo   = conectar();
$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cotizacion = intval($_POST['id_cotizacion'] ?? 0);
    $fecha_firma   = $_POST['fecha_firma'] ?? '';
    $clausulas     = trim($_POST['clausulas'] ?? '');
    $estado        = $_POST['estado'] ?? 'borrador';

    if ($id_cotizacion && $fecha_firma) {
        // Verifica que la cotización no tenga ya contrato
        $stmt = $pdo->prepare("SELECT id_contrato FROM contratos WHERE id_cotizacion = ?");
        $stmt->execute([$id_cotizacion]);

        if ($stmt->fetch()) {
            $error = 'Esta cotización ya tiene un contrato asociado';
        } else {
            $stmt2 = $pdo->prepare("
                INSERT INTO contratos (id_cotizacion, fecha_firma, clausulas, estado)
                VALUES (?, ?, ?, ?)
            ");
            $stmt2->execute([$id_cotizacion, $fecha_firma, $clausulas, $estado]);
            $id_nuevo = $pdo->lastInsertId();
            registrarAccion("Creó contrato ID: $id_nuevo");
            $exito = "Contrato #$id_nuevo creado correctamente";
        }
    } else {
        $error = 'Completa los campos obligatorios';
    }
}

// Solo cotizaciones aprobadas sin contrato
$cotizaciones = $pdo->query("
    SELECT co.id_cotizacion, co.monto_total, cl.nombre as cliente
    FROM cotizaciones co
    JOIN clientes cl ON cl.id_cliente = co.id_cliente
    WHERE co.estado = 'aprobada'
    AND co.id_cotizacion NOT IN (SELECT id_cotizacion FROM contratos)
    ORDER BY cl.nombre ASC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Contrato — Vértice</title>
</head>
<body>

<h2>➕ Nuevo Contrato</h2>
<a href="index.php">← Volver a contratos</a>

<br><br>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
<?php if ($exito): ?>
    <p style="color:green"><?= $exito ?></p>
<?php endif; ?>

<?php if (empty($cotizaciones)): ?>
    <p style="color:orange">No hay cotizaciones aprobadas sin contrato disponibles.</p>
<?php else: ?>

<form method="POST">
    <label>Cotización aprobada: *</label><br>
    <select name="id_cotizacion" required>
        <option value="">-- Selecciona --</option>
        <?php foreach ($cotizaciones as $co): ?>
            <option value="<?= $co['id_cotizacion'] ?>">
                #<?= $co['id_cotizacion'] ?> — <?= htmlspecialchars($co['cliente']) ?>
                (Bs <?= number_format($co['monto_total'], 2) ?>)
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Fecha de firma: *</label><br>
    <input type="date" name="fecha_firma" value="<?= date('Y-m-d') ?>" required><br><br>

    <label>Cláusulas:</label><br>
    <textarea name="clausulas" rows="6" cols="60"
              placeholder="Condiciones, plazos, penalidades..."></textarea><br><br>

    <label>Estado inicial:</label><br>
    <select name="estado">
        <option value="borrador">Borrador</option>
        <option value="activo">Activo</option>
    </select><br><br>

    <button type="submit">Crear contrato</button>
</form>

<?php endif; ?>

</body>
</html>