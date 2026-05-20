<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('ver_inventarios');
registrarAccion('Vio reporte inventario');

$pdo = conectar();

// Stock actual por almacén
$stock_almacen = $pdo->query("
    SELECT a.nombre as almacen,
           COUNT(i.id_material) as total_materiales,
           SUM(i.stock) as stock_total
    FROM inventarios i
    JOIN almacenes a ON a.id_almacen = i.id_almacen
    GROUP BY i.id_almacen, a.nombre
    ORDER BY a.nombre ASC
")->fetchAll();

// Materiales bajo stock mínimo
$bajo_minimo = $pdo->query("
    SELECT m.nombre as material, a.nombre as almacen,
           i.stock, i.stock_minimo,
           (i.stock_minimo - i.stock) as diferencia
    FROM inventarios i
    JOIN materiales m ON m.id_material = i.id_material
    JOIN almacenes a ON a.id_almacen = i.id_almacen
    WHERE i.stock <= i.stock_minimo
    ORDER BY diferencia DESC
")->fetchAll();

// Materiales agotados
$agotados = $pdo->query("
    SELECT m.nombre as material, a.nombre as almacen
    FROM inventarios i
    JOIN materiales m ON m.id_material = i.id_material
    JOIN almacenes a ON a.id_almacen = i.id_almacen
    WHERE i.stock <= 0
")->fetchAll();

// Últimos movimientos
$ultimos_movimientos = $pdo->query("
    SELECT mi.fecha, mi.tipo_movimiento, mi.cantidad,
           m.nombre as material, a.nombre as almacen
    FROM movimientos_inventario mi
    JOIN materiales m ON m.id_material = mi.id_material
    JOIN almacenes a ON a.id_almacen = mi.id_almacen
    ORDER BY mi.fecha DESC, mi.id_movimiento DESC
    LIMIT 15
")->fetchAll();

// Materiales más usados en proyectos
$mas_usados = $pdo->query("
    SELECT m.nombre, SUM(um.cantidad) as total,
           COUNT(DISTINCT um.id_proyecto) as en_proyectos
    FROM uso_materiales um
    JOIN materiales m ON m.id_material = um.id_material
    GROUP BY um.id_material, m.nombre
    ORDER BY total DESC
    LIMIT 10
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Inventario — Vértice</title>
</head>
<body>

<h2>📦 Reporte de Inventario</h2>
<a href="../../dashboard.php">← Volver al dashboard</a>
&nbsp;&nbsp;
<a href="dashboard.php">Ver reporte general</a>

<hr>

<!-- STOCK POR ALMACÉN -->
<h3>🏭 Stock por almacén</h3>
<table border="1" cellpadding="8">
    <tr>
        <th>Almacén</th>
        <th>Materiales distintos</th>
        <th>Stock total</th>
    </tr>
    <?php foreach ($stock_almacen as $sa): ?>
        <tr>
            <td><?= htmlspecialchars($sa['almacen']) ?></td>
            <td><?= $sa['total_materiales'] ?></td>
            <td><?= number_format($sa['stock_total'], 2) ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<hr>

<!-- BAJO STOCK MÍNIMO -->
<h3>⚠️ Materiales bajo stock mínimo</h3>
<?php if ($bajo_minimo): ?>
    <table border="1" cellpadding="8">
        <tr>
            <th>Material</th>
            <th>Almacén</th>
            <th>Stock actual</th>
            <th>Stock mínimo</th>
            <th>Diferencia</th>
        </tr>
        <?php foreach ($bajo_minimo as $bm): ?>
            <tr>
                <td><?= htmlspecialchars($bm['material']) ?></td>
                <td><?= htmlspecialchars($bm['almacen']) ?></td>
                <td style="color:orange"><?= $bm['stock'] ?></td>
                <td><?= $bm['stock_minimo'] ?></td>
                <td style="color:red">-<?= $bm['diferencia'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p style="color:green">Todos los materiales están sobre el stock mínimo.</p>
<?php endif; ?>

<hr>

<!-- AGOTADOS -->
<h3>🚨 Materiales agotados</h3>
<?php if ($agotados): ?>
    <table border="1" cellpadding="8">
        <tr>
            <th>Material</th>
            <th>Almacén</th>
        </tr>
        <?php foreach ($agotados as $ag): ?>
            <tr>
                <td style="color:red"><?= htmlspecialchars($ag['material']) ?></td>
                <td><?= htmlspecialchars($ag['almacen']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p style="color:green">No hay materiales agotados.</p>
<?php endif; ?>

<hr>

<!-- MÁS USADOS -->
<h3>📈 Top 10 materiales más usados en proyectos</h3>
<table border="1" cellpadding="8">
    <tr>
        <th>Material</th>
        <th>Cantidad total usada</th>
        <th>En proyectos</th>
    </tr>
    <?php foreach ($mas_usados as $mu): ?>
        <tr>
            <td><?= htmlspecialchars($mu['nombre']) ?></td>
            <td><?= number_format($mu['total'], 2) ?></td>
            <td><?= $mu['en_proyectos'] ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<hr>

<!-- ÚLTIMOS MOVIMIENTOS -->
<h3>🔄 Últimos 15 movimientos</h3>
<table border="1" cellpadding="8">
    <tr>
        <th>Fecha</th>
        <th>Material</th>
        <th>Almacén</th>
        <th>Tipo</th>
        <th>Cantidad</th>
    </tr>
    <?php foreach ($ultimos_movimientos as $mv): ?>
        <tr>
            <td><?= $mv['fecha'] ?></td>
            <td><?= htmlspecialchars($mv['material']) ?></td>
            <td><?= htmlspecialchars($mv['almacen']) ?></td>
            <td><?= $mv['tipo_movimiento'] ?></td>
            <td><?= $mv['cantidad'] ?></td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>