<?php

require_once '../../config/database.php';

$sql = "SELECT * FROM materiales";

$stmt = $pdo->query($sql);

$materiales = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<h2>Materiales</h2>

<a href="crear.php">Nuevo Material</a>

<table border="1">

<tr>
    <th>ID</th>
    <th>Nombre</th>
    <th>Descripción</th>
    <th>Precio Base</th>
</tr>

<?php foreach($materiales as $m): ?>

<tr>

    <td><?= $m['id_material'] ?></td>

    <td><?= $m['nombre'] ?></td>

    <td><?= $m['descripcion'] ?></td>

    <td><?= $m['precio_unitario_base'] ?></td>

</tr>

<?php endforeach; ?>

</table>