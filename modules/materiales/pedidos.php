<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('gestionar_pedidos');
registrarAccion('Vio pedidos');

$pdo   = conectar();
$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_proveedor = intval($_POST['id_proveedor'] ?? 0);
    $id_almacen   = intval($_POST['id_almacen'] ?? 0);
    $fecha_pedido = $_POST['fecha_pedido'] ?? date('Y-m-d');
    $materiales   = $_POST['materiales'] ?? [];
    $cantidades   = $_POST['cantidades'] ?? [];
    $precios      = $_POST['precios'] ?? [];

    if ($id_proveedor && $id_almacen && !empty($materiales)) {
        // Crea el pedido
        $stmt = $pdo->prepare("
            INSERT INTO pedidos (id_proveedor, id_almacen, fecha_pedido, estado)
            VALUES (?, ?, ?, 'pendiente')
        ");
        $stmt->execute([$id_proveedor, $id_almacen, $fecha_pedido]);
        $id_pedido = $pdo->lastInsertId();

        // Inserta el detalle
        $stmt2 = $pdo->prepare("
            INSERT INTO detalle_pedido (id_pedido, id_material, cantidad, precio_unitario)
            VALUES (?, ?, ?, ?)
        ");
        foreach ($materiales as $i => $id_material) {
            if ($id_material && $cantidades[$i] > 0) {
                $stmt2->execute([
                    $id_pedido,
                    intval($id_material),
                    floatval($cantidades[$i]),
                    floatval($precios[$i])
                ]);
            }
        }

        registrarAccion("Creó pedido ID: $id_pedido al proveedor ID: $id_proveedor");
        $exito = "Pedido #$id_pedido creado correctamente";
    } else {
        $error = 'Completa todos los campos y agrega al menos un material';
    }
}

$proveedores = $pdo->query("SELECT id_proveedor, nombre FROM proveedores ORDER BY nombre ASC")->fetchAll();
$almacenes   = $pdo->query("SELECT id_almacen, nombre FROM almacenes ORDER BY nombre ASC")->fetchAll();
$materiales_lista = $pdo->query("SELECT id_material, nombre, precio_unitario_base FROM materiales ORDER BY nombre ASC")->fetchAll();

// Lista de pedidos existentes
$pedidos = $pdo->query("
    SELECT p.id_pedido, p.fecha_pedido, p.estado,
           pr.nombre as proveedor, a.nombre as almacen,
           COUNT(dp.id_material) as total_items
    FROM pedidos p
    JOIN proveedores pr ON pr.id_proveedor = p.id_proveedor
    JOIN almacenes a ON a.id_almacen = p.id_almacen
    LEFT JOIN detalle_pedido dp ON dp.id_pedido = p.id_pedido
    GROUP BY p.id_pedido, p.fecha_pedido, p.estado, pr.nombre, a.nombre
    ORDER BY p.fecha_pedido DESC
    LIMIT 20
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedidos — Vértice</title>
</head>
<body>

<h2>🛒 Pedidos a Proveedores</h2>
<a href="../../dashboard.php">← Volver al dashboard</a>

<br><br>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
<?php if ($exito): ?>
    <p style="color:green"><?= $exito ?></p>
<?php endif; ?>

<h3>Nuevo pedido</h3>
<form method="POST">
    <label>Proveedor: *</label><br>
    <select name="id_proveedor" required>
        <option value="">-- Selecciona --</option>
        <?php foreach ($proveedores as $p): ?>
            <option value="<?= $p['id_proveedor'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Almacén destino: *</label><br>
    <select name="id_almacen" required>
        <option value="">-- Selecciona --</option>
        <?php foreach ($almacenes as $a): ?>
            <option value="<?= $a['id_almacen'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Fecha del pedido: *</label><br>
    <input type="date" name="fecha_pedido" value="<?= date('Y-m-d') ?>"><br><br>

    <h4>Materiales del pedido</h4>
    <table border="1" cellpadding="6" id="tabla_materiales">
        <tr>
            <th>Material</th>
            <th>Cantidad</th>
            <th>Precio unitario (Bs)</th>
        </tr>
        <?php for ($i = 0; $i < 5; $i++): ?>
            <tr>
                <td>
                    <select name="materiales[]">
                        <option value="">-- Opcional --</option>
                        <?php foreach ($materiales_lista as $m): ?>
                            <option value="<?= $m['id_material'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="number" name="cantidades[]" step="0.01" min="0" value="0"></td>
                <td><input type="number" name="precios[]" step="0.01" min="0" value="0"></td>
            </tr>
        <?php endfor; ?>
    </table>

    <br>
    <button type="submit">Crear pedido</button>
</form>

<hr>

<h3>Pedidos recientes</h3>
<table border="1" cellpadding="8">
    <tr>
        <th>ID</th>
        <th>Fecha</th>
        <th>Proveedor</th>
        <th>Almacén</th>
        <th>Items</th>
        <th>Estado</th>
    </tr>
    <?php foreach ($pedidos as $p): ?>
        <tr>
            <td><?= $p['id_pedido'] ?></td>
            <td><?= $p['fecha_pedido'] ?></td>
            <td><?= htmlspecialchars($p['proveedor']) ?></td>
            <td><?= htmlspecialchars($p['almacen']) ?></td>
            <td><?= $p['total_items'] ?></td>
            <td><?= $p['estado'] ?></td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>