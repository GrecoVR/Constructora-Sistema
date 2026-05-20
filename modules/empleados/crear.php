<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('gestionar_empleados');

$pdo   = conectar();
$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $ci        = trim($_POST['ci'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $estado    = $_POST['estado'] ?? 'activo';

    if ($nombre && $ci && $direccion) {
        // Verifica CI único
        $stmt = $pdo->prepare("SELECT id_empleado FROM empleados WHERE ci = ?");
        $stmt->execute([$ci]);

        if ($stmt->fetch()) {
            $error = 'Ya existe un empleado con ese CI';
        } else {
            $stmt2 = $pdo->prepare("
                INSERT INTO empleados (nombre, ci, direccion, telefono, email, estado)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt2->execute([$nombre, $ci, $direccion, $telefono, $email, $estado]);
            $id_nuevo = $pdo->lastInsertId();
            registrarAccion("Creó empleado: $nombre (ID: $id_nuevo)");
            $exito = 'Empleado creado correctamente';
        }
    } else {
        $error = 'Completa los campos obligatorios';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Empleado — Vértice</title>
</head>
<body>

<h2>➕ Nuevo Empleado</h2>
<a href="index.php">← Volver a empleados</a>

<br><br>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
<?php if ($exito): ?>
    <p style="color:green"><?= $exito ?></p>
<?php endif; ?>

<form method="POST">
    <label>Nombre completo: *</label><br>
    <input type="text" name="nombre" required style="width:400px"><br><br>

    <label>CI: *</label><br>
    <input type="text" name="ci" required><br><br>

    <label>Dirección: *</label><br>
    <input type="text" name="direccion" required style="width:400px"><br><br>

    <label>Teléfono:</label><br>
    <input type="text" name="telefono"><br><br>

    <label>Email:</label><br>
    <input type="email" name="email"><br><br>

    <label>Estado:</label><br>
    <select name="estado">
        <option value="activo">Activo</option>
        <option value="inactivo">Inactivo</option>
    </select><br><br>

    <button type="submit">Crear empleado</button>
</form>

</body>
</html>