<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('gestionar_empleados');

$pdo = conectar();
$id  = intval($_GET['id'] ?? 0);

if (!$id) { header('Location: index.php'); exit; }

$error = '';
$exito = '';

$stmt = $pdo->prepare("SELECT * FROM empleados WHERE id_empleado = ?");
$stmt->execute([$id]);
$empleado = $stmt->fetch();

if (!$empleado) { header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $ci        = trim($_POST['ci'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $estado    = $_POST['estado'] ?? 'activo';

    if ($nombre && $ci && $direccion) {
        // Verifica CI único excluyendo el actual
        $stmt2 = $pdo->prepare("
            SELECT id_empleado FROM empleados WHERE ci = ? AND id_empleado != ?
        ");
        $stmt2->execute([$ci, $id]);

        if ($stmt2->fetch()) {
            $error = 'Ese CI ya pertenece a otro empleado';
        } else {
            $stmt3 = $pdo->prepare("
                UPDATE empleados SET nombre=?, ci=?, direccion=?, telefono=?, email=?, estado=?
                WHERE id_empleado=?
            ");
            $stmt3->execute([$nombre, $ci, $direccion, $telefono, $email, $estado, $id]);
            registrarAccion("Editó empleado ID: $id");
            $exito = 'Empleado actualizado correctamente';
            $stmt->execute([$id]);
            $empleado = $stmt->fetch();
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
    <title>Editar Empleado — Vértice</title>
</head>
<body>

<h2>✏️ Editar Empleado</h2>
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
    <input type="text" name="nombre" value="<?= htmlspecialchars($empleado['nombre']) ?>"
           required style="width:400px"><br><br>

    <label>CI: *</label><br>
    <input type="text" name="ci" value="<?= htmlspecialchars($empleado['ci']) ?>" required><br><br>

    <label>Dirección: *</label><br>
    <input type="text" name="direccion" value="<?= htmlspecialchars($empleado['direccion']) ?>"
           required style="width:400px"><br><br>

    <label>Teléfono:</label><br>
    <input type="text" name="telefono" value="<?= htmlspecialchars($empleado['telefono']) ?>"><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" value="<?= htmlspecialchars($empleado['email']) ?>"><br><br>

    <label>Estado:</label><br>
    <select name="estado">
        <option value="activo" <?= $empleado['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
        <option value="inactivo" <?= $empleado['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
    </select><br><br>

    <button type="submit">Guardar cambios</button>
</form>

</body>
</html>