<?php
require_once '../middleware/auth_cliente.php';
require_once '../config/database.php';
require_once '../utils/fecha.php';

$pdo        = conectar();
$id_cliente = $_SESSION['id_cliente'];

$proyectos = $pdo->prepare("
    SELECT p.id_proyecto, p.nombre, p.descripcion, p.ubicacion,
           p.estado, p.fecha_inicio, p.fecha_fin_estimada,
           tp.nombre as tipo
    FROM proyectos p
    JOIN tipos_proyecto tp ON tp.id_tipo_proyecto = p.id_tipo_proyecto
    JOIN contratos c ON c.id_contrato = p.id_contrato
    JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
    WHERE co.id_cliente = ?
    ORDER BY p.fecha_inicio DESC
");
$proyectos->execute([$id_cliente]);
$proyectos = $proyectos->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Proyectos — Portal Cliente</title>
</head>
<body>

<h2>📁 Mis Proyectos</h2>
<a href="index.php">← Volver al portal</a>

<br><br>

<?php foreach ($proyectos as $p): ?>
    <?php
    // Etapas del proyecto
    $etapas = $pdo->prepare("
        SELECT nombre, porcentaje_avance, estado, fecha_inicio, fecha_fin
        FROM etapas_proyecto WHERE id_proyecto = ?
        ORDER BY fecha_inicio ASC
    ");
    $etapas->execute([$p['id_proyecto']]);
    $etapas = $etapas->fetchAll();
    $avance = count($etapas) > 0
        ? round(array_sum(array_column($etapas, 'porcentaje_avance')) / count($etapas))
        : 0;
    ?>

    <fieldset style="margin-bottom:20px">
        <legend><strong><?= htmlspecialchars($p['nombre']) ?></strong></legend>

        <p><?= htmlspecialchars($p['descripcion']) ?></p>
        <p>📍 <?= htmlspecialchars($p['ubicacion']) ?></p>
        <p>🏷️ Tipo: <?= htmlspecialchars($p['tipo']) ?></p>
        <p>📅 Inicio: <?= formatoFechaCorta($p['fecha_inicio']) ?></p>
        <p>🏁 Fin estimado: <?= estadoFecha($p['fecha_fin_estimada']) ?></p>
        <p>Estado: <?= ucfirst($p['estado']) ?></p>
        <p>Avance general: <?= $avance ?>%
            <progress value="<?= $avance ?>" max="100"></progress>
        </p>

        <h4>Etapas:</h4>
        <table border="1" cellpadding="6">
            <tr>
                <th>Etapa</th>
                <th>Estado</th>
                <th>Avance</th>
                <th>Fecha fin</th>
            </tr>
            <?php foreach ($etapas as $e): ?>
                <tr>
                    <td><?= htmlspecialchars($e['nombre']) ?></td>
                    <td><?= ucfirst($e['estado']) ?></td>
                    <td>
                        <?= $e['porcentaje_avance'] ?>%
                        <progress value="<?= $e['porcentaje_avance'] ?>" max="100"></progress>
                    </td>
                    <td><?= $e['fecha_fin'] ? estadoFecha($e['fecha_fin']) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </fieldset>

<?php endforeach; ?>

<?php if (empty($proyectos)): ?>
    <p>No tienes proyectos registrados.</p>
<?php endif; ?>

</body>
</html>