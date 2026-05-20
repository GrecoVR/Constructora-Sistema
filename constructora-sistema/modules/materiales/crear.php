<?php

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_tipo_material = $_POST['id_tipo_material'];
    $id_unidad_medida = $_POST['id_unidad_medida'];
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];

    $sql = "INSERT INTO materiales(
                id_tipo_material,
                id_unidad_medida,
                nombre,
                descripcion,
                precio_unitario_base
            )
            VALUES(?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    
 
    $stmt->execute([
        $id_tipo_material,
        $id_unidad_medida,
        $nombre,
        $descripcion,
        $precio
    ]);

    header('Location: index.php');
    exit;
}
?>

<h2>Nuevo Material</h2>

<form method="POST">

<input type="number" name="id_tipo_material" placeholder="ID Tipo Material" required>
<br><br>

<input type="number" name="id_unidad_medida" placeholder="ID Unidad Medida" required>
<br><br>

<input type="text" name="nombre" placeholder="Nombre" required>
<br><br>

<textarea name="descripcion" placeholder="Descripción"></textarea>
<br><br>

<input type="number" step="0.01" name="precio" placeholder="Precio Base" required>
<br><br>

<button type="submit">
Guardar
</button>

</form>