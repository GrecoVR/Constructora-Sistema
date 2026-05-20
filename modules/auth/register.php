<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

$error   = '';
$exito   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario    = trim($_POST['usuario'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');
    $id_empleado = intval($_POST['id_empleado'] ?? 0);

    if ($usuario && $contrasena && $id_empleado) {
        $pdo = conectar();

        // Verifica que el usuario no exista
        $stmt = $pdo->prepare("SELECT id_usuario_sistema FROM usuarios_sistema WHERE nombre_usuario = ?");
        $stmt->execute([$usuario]);

        if ($stmt->fetch()) {
            $error = 'Ese nombre de usuario ya existe';
        } else {
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            $stmt2 = $pdo->prepare("
                INSERT INTO usuarios_sistema (id_empleado, nombre_usuario, password_hash, estado)
                VALUES (?, ?, ?, 'activo')
            ");
            $stmt2->execute([$id_empleado, $usuario, $hash]);
            $exito = 'Cuenta creada correctamente. Ya puedes iniciar sesión.';
        }
    } else {
        $error = 'Completa todos los campos';
    }
}

// Carga empleados disponibles para el dropdown
$pdo       = conectar();
$empleados = $pdo->query("
    SELECT e.id_empleado, e.nombre 
    FROM empleados e
    WHERE e.id_empleado NOT IN (SELECT id_empleado FROM usuarios_sistema)
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear cuenta — Constructora</title>
</head>
<body>
    <h1>Constructora</h1>
    <h2>Crear cuenta</h2>

    <?php if ($error): ?>
        <p style="color:red"><?= $error ?></p>
    <?php endif; ?>

    <?php if ($exito): ?>
        <p style="color:green"><?= $exito ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Empleado:</label><br>
        <select name="id_empleado" required>
            <option value="">-- Selecciona --</option>
            <?php foreach ($empleados as $emp): ?>
                <option value="<?= $emp['id_empleado'] ?>"><?= $emp['nombre'] ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Nombre de usuario:</label><br>
        <input type="text" name="usuario" required><br><br>

        <label>Contraseña:</label><br>
        <input type="password" name="contrasena" required><br><br>

        <button type="submit">Crear cuenta</button>
    </form>

    <a href="login.php">Volver al login</a>
</body>
</html>