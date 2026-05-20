<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('configurar_sistema');

$pdo    = conectar();
$error  = '';
$exito  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario     = trim($_POST['usuario'] ?? '');
    $contrasena  = trim($_POST['contrasena'] ?? '');
    $id_empleado = intval($_POST['id_empleado'] ?? 0);
    $estado      = $_POST['estado'] ?? 'activo';

    if ($usuario && $contrasena && $id_empleado) {
        $stmt = $pdo->prepare("SELECT id_usuario_sistema FROM usuarios_sistema WHERE nombre_usuario = ?");
        $stmt->execute([$usuario]);

        if ($stmt->fetch()) {
            $error = 'Ese nombre de usuario ya existe';
        } else {
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            $stmt2 = $pdo->prepare("
                INSERT INTO usuarios_sistema (id_empleado, nombre_usuario, password_hash, estado)
                VALUES (?, ?, ?, ?)
            ");
            $stmt2->execute([$id_empleado, $usuario, $hash, $estado]);
            registrarAccion("Creó usuario: $usuario");
            $exito = 'Usuario creado correctamente';
        }
    } else {
        $error = 'Completa todos los campos';
    }
}

$empleados = $pdo->query("
    SELECT e.id_empleado, e.nombre
    FROM empleados e
    WHERE e.id_empleado NOT IN (SELECT id_empleado FROM usuarios_sistema)
    ORDER BY e.nombre ASC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Usuario — Vértice</title>
</head>
<body>

<h2>➕ Crear Usuario</h2>
<a href="index.php">← Volver a usuarios</a>

<br><br>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
<?php if ($exito): ?>
    <p style="color:green"><?= $exito ?></p>
<?php endif; ?>

<form method="POST">
    <label>Empleado:</label><br>
    <select name="id_empleado" required>
        <option value="">-- Selecciona empleado --</option>
        <?php foreach ($empleados as $emp): ?>
            <option value="<?= $emp['id_empleado'] ?>"><?= htmlspecialchars($emp['nombre']) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Nombre de usuario:</label><br>
    <input type="text" name="usuario" required><br><br>

    <label>Contraseña:</label><br>
    <input type="password" name="contrasena" required><br><br>

    <label>Estado:</label><br>
    <select name="estado">
        <option value="activo">Activo</option>
        <option value="inactivo">Inactivo</option>
    </select><br><br>

    <button type="submit">Crear usuario</button>
</form>

</body>
</html>