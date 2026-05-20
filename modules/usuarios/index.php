<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('configurar_sistema');
registrarAccion('Vio lista de usuarios');

$pdo  = conectar();
$stmt = $pdo->query("
    SELECT us.id_usuario_sistema, us.nombre_usuario, us.estado,
           e.nombre as empleado,
           GROUP_CONCAT(r.nombre_rol SEPARATOR ', ') as roles
    FROM usuarios_sistema us
    JOIN empleados e ON e.id_empleado = us.id_empleado
    LEFT JOIN usuarios_roles ur ON ur.id_usuario_sistema = us.id_usuario_sistema
    LEFT JOIN roles r ON r.id_rol = ur.id_rol
    GROUP BY us.id_usuario_sistema, us.nombre_usuario, us.estado, e.nombre
    ORDER BY e.nombre ASC
");
$usuarios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios — Vértice</title>
</head>
<body>

<h2>👥 Gestión de Usuarios</h2>
<a href="../../dashboard.php">← Volver al dashboard</a>
&nbsp;&nbsp;
<a href="crear.php"><button>+ Nuevo usuario</button></a>

<br><br>

<table border="1" cellpadding="8">
    <tr>
        <th>ID</th>
        <th>Empleado</th>
        <th>Usuario</th>
        <th>Roles</th>
        <th>Estado</th>
        <th>Acciones</th>
    </tr>
    <?php foreach ($usuarios as $u): ?>
        <tr>
            <td><?= $u['id_usuario_sistema'] ?></td>
            <td><?= htmlspecialchars($u['empleado']) ?></td>
            <td><?= htmlspecialchars($u['nombre_usuario']) ?></td>
            <td><?= $u['roles'] ?? 'Sin roles' ?></td>
            <td><?= $u['estado'] ?></td>
            <td>
                <a href="editar.php?id=<?= $u['id_usuario_sistema'] ?>">Editar</a>
                &nbsp;|&nbsp;
                <a href="roles.php?id=<?= $u['id_usuario_sistema'] ?>">Roles</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>