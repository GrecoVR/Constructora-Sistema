<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
require_once '../../triggers/TriggerManager.php';

requierePermiso('registrar_movimientos');

$pdo   = conectar();
$error = '';
$exito = '';

// Filtro por material específico si viene desde index
$id_material_filtro = intval($_GET['id_material'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_material     = intval($_POST['id_material'] ?? 0);
    $id_almacen      = intval($_POST['id_almacen'] ?? 0);
    $tipo_movimiento = $_POST['tipo_movimiento'] ?? '';
    $cantidad        = floatval($_POST['cantidad'] ?? 0);
    $fecha           = $_POST['fecha'] ?? date('Y-m-d');

    if ($id_material && $id_almacen && $tipo_movimiento && $cantidad > 0) {

        // Para salidas verifica que haya stock suficiente
        if ($tipo_movimiento === 'salida') {
            $stmt = $pdo->prepare("
                SELECT stock FROM inventarios
                WHERE id_material = ? AND id_almacen = ?
            ");
            $stmt->execute([$id_material, $id_almacen]);
            $inv = $stmt->fetch();

            if (!$inv || $inv['stock'] < $cantidad) {
                $error = 'Stock insuficiente para registrar esta salida';
            }
        }

        if (!$error) {
            // Registra el movimiento
            $stmt = $pdo->prepare("
                INSERT INTO movimientos_inventario 
                (id_material, id_almacen, tipo_movimiento, fecha, cantidad)
                VALUES (?, ?, ?, ?, ?)
            ");
            $cantidad_real = $tipo_movimiento === 'salida' ? -$cantidad : $cantidad;
            $stmt->execute([$id_material, $id_almacen, $tipo_movimiento, $fecha, $cantidad_real]);

            // Dispara el trigger correspondiente
            $manager = new TriggerManager($pdo);
            $datos   = [
                'id_material' => $id_material,
                'id_almacen'  => $id_almacen,
                'cantidad'    => $cantidad,
                'id_usuario'  => $_SESSION['id_usuario']
            ];

            if ($tipo_movimiento === 'entrada') {
                $manager->ejecutar('inventario.entrada', $datos);
            } elseif ($tipo_movimiento === 'salida') {
                $manager->ejecutar('inventario.salida', $datos);
            } elseif ($tipo_movimiento === 'ajuste') {
                $datos['cantidad'] = $cantidad_real;
                $manager->ejecutar('inventario.ajuste', $datos);
            }

            registrarAccion("Registró movimiento $tipo_movimiento — material ID: $id_material");
            $exito = 'Movimiento registrado correctamente';
        }
    } else {
        $error = 'Completa todos los campos';
    }
}

$materiales = $pdo->query("SELECT id_material, nombre FROM materiales ORDER BY nombre ASC")->fetchAll();
$almacenes  = $pdo->query("SELECT id_almacen, nombre FROM almacenes ORDER BY nombre ASC")->fetchAll();

// Historial de movimientos
$historial_stmt = $pdo->query("
    SELECT mi.fecha, mi.tipo_movimiento, mi.cantidad,
           m.nombre as material, a.nombre as almacen
    FROM movimientos_inventario mi
    JOIN materiales m ON m.id_material = mi.id_material
    JOIN almacenes a ON a.id_almacen = mi.id_almacen
    ORDER BY mi.fecha DESC, mi.id_movimiento DESC
    LIMIT 20
");
$historial = $historial_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movimientos — Vértice</title>
</head>
<body>

<h2>🔄 Movimientos de Inventario</h2>
<a href="../../dashboard.php">← Volver al dashboard</a>
&nbsp;&nbsp;
<a href="index.php">Ver materiales</a>

<br><br>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
<?php if ($exito): ?>
    <p style="color:green"><?= $exito ?></p>
<?php endif; ?>

<h3>Registrar movimiento</h3>
<form method="POST">
    <label>Material: *</label><br>
    <select name="id_material" required>
        <option value="">-- Selecciona --</option>
        <?php foreach ($materiales as $m): ?>
            <option value="<?= $m['id_material'] ?>"
                <?= $m['id_material'] == $id_material_filtro ? 'selected' : '' ?>>
                <?= htmlspecialchars($m['nombre']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Almacén: *</label><br>
    <select name="id_almacen" required>
        <option value="">-- Selecciona --</option>
        <?php foreach ($almacenes as $a): ?>
            <option value="<?= $a['id_almacen'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Tipo de movimiento: *</label><br>
    <select name="tipo_movimiento" required>
        <option value="">-- Selecciona --</option>
        <option value="entrada">Entrada</option>
        <option value="salida">Salida</option>
        <option value="ajuste">Ajuste</option>
    </select><br><br>

    <label>Cantidad: *</label><br>
    <input type="number" name="cantidad" step="0.01" min="0.01" required><br><br>

    <label>Fecha: *</label><br>
    <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required><br><br>

    <button type="submit">Registrar</button>
</form>

<hr>

<h3>Últimos 20 movimientos</h3>
<table border="1" cellpadding="8">
    <tr>
        <th>Fecha</th>
        <th>Material</th>
        <th>Almacén</th>
        <th>Tipo</th>
        <th>Cantidad</th>
    </tr>
    <?php foreach ($historial as $h): ?>
        <tr>
            <td><?= $h['fecha'] ?></td>
            <td><?= htmlspecialchars($h['material']) ?></td>
            <td><?= htmlspecialchars($h['almacen']) ?></td>
            <td><?= $h['tipo_movimiento'] ?></td>
            <td><?= $h['cantidad'] ?></td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>