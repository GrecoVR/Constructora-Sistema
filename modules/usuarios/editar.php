<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('configurar_sistema');

$pdo = conectar();
$id  = intval($_GET['id'] ?? 0);

if (!$id) {
    header('Location: index.php');
    exit;
}

$error = '';
$exito = '';

// Carga el usuario
$stmt = $pdo->prepare("
    SELECT us.*, e.nombre as empleado
    FROM usuarios_sistema us
    JOIN empleados e ON e.id_empleado = us.id_empleado
    WHERE us.id_usuario_sistema = ?
");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_usuario = trim($_POST['usuario'] ?? '');
    $contrasena    = trim($_POST['contrasena'] ?? '');
    $estado        = $_POST['estado'] ?? 'activo';

    if ($nuevo_usuario) {
        // Verifica que no exista otro con ese nombre
        $stmt2 = $pdo->prepare("
            SELECT id_usuario_sistema FROM usuarios_sistema 
            WHERE nombre_usuario = ? AND id_usuario_sistema != ?
        ");
        $stmt2->execute([$nuevo_usuario, $id]);

        if ($stmt2->fetch()) {
            $error = 'Ese nombre de usuario ya existe';
        } else {
            if ($contrasena) {
                $hash = password_hash($contrasena, PASSWORD_DEFAULT);
                $stmt3 = $pdo->prepare("
                    UPDATE usuarios_sistema 
                    SET nombre_usuario = ?, password_hash = ?, estado = ?
                    WHERE id_usuario_sistema = ?
                ");
                $stmt3->execute([$nuevo_usuario, $hash, $estado, $id]);
            } else {
                $stmt3 = $pdo->prepare("
                    UPDATE usuarios_sistema 
                    SET nombre_usuario = ?, estado = ?
                    WHERE id_usuario_sistema = ?
                ");
                $stmt3->execute([$nuevo_usuario, $estado, $id]);
            }
            registrarAccion("Editó usuario ID: $id");
            $exito = 'Usuario actualizado correctamente';
            // Recarga datos
            $stmt->execute([$id]);
            $usuario = $stmt->fetch();
        }
    } else {
        $error = 'El nombre de usuario no puede estar vacío';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario — Vértice</title>
</head>
<body>

<h2>✏️ Editar Usuario — <?= htmlspecialchars($usuario['empleado']) ?></h2>
<a href="index.php">← Volver a usuarios</a>

<br><br>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
<?php if ($exito): ?>
    <p style="color:green"><?= $exito ?></p>
<?php endif; ?>

<form method="POST">
    <label>Nombre de usuario:</label><br>
    <input type="text" name="usuario" value="<?= htmlspecialchars($usuario['nombre_usuario']) ?>" required><br><br>

    <label>Nueva contraseña (dejar vacío para no cambiar):</label><br>
    <input type="password" name="contrasena"><br><br>

    <label>Estado:</label><br>
    <select name="estado">
        <option value="activo" <?= $usuario['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
        <option value="inactivo" <?= $usuario['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
    </select><br><br>

    <button type="submit">Guardar cambios</button>
</form>

</body>
</html>