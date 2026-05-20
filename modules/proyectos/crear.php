<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('crear_proyectos');

$pdo   = conectar();
$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre           = trim($_POST['nombre'] ?? '');
    $descripcion      = trim($_POST['descripcion'] ?? '');
    $ubicacion        = trim($_POST['ubicacion'] ?? '');
    $fecha_inicio     = $_POST['fecha_inicio'] ?? '';
    $fecha_fin        = $_POST['fecha_fin_estimada'] ?? '';
    $id_tipo_proyecto = intval($_POST['id_tipo_proyecto'] ?? 0);
    $id_contrato      = intval($_POST['id_contrato'] ?? 0);
    $estado           = $_POST['estado'] ?? 'planificacion';

    if ($nombre && $descripcion && $ubicacion && $fecha_inicio && $fecha_fin && $id_tipo_proyecto && $id_contrato) {
        $stmt = $pdo->prepare("
            INSERT INTO proyectos 
            (id_tipo_proyecto, id_contrato, nombre, descripcion, ubicacion, fecha_inicio, fecha_fin_estimada, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$id_tipo_proyecto, $id_contrato, $nombre, $descripcion, $ubicacion, $fecha_inicio, $fecha_fin, $estado]);
        $id_nuevo = $pdo->lastInsertId();
        registrarAccion("Creó proyecto: $nombre (ID: $id_nuevo)");
        $exito = 'Proyecto creado correctamente';
    } else {
        $error = 'Completa todos los campos obligatorios';
    }
}

$tipos     = $pdo->query("SELECT * FROM tipos_proyecto ORDER BY nombre ASC")->fetchAll();
$contratos = $pdo->query("
    SELECT c.id_contrato, cl.nombre as cliente, c.estado
    FROM contratos c
    JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
    JOIN clientes cl ON cl.id_cliente = co.id_cliente
    WHERE c.estado = 'activo'
    ORDER BY cl.nombre ASC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Proyecto — Vértice</title>
</head>
<body>

<h2>➕ Nuevo Proyecto</h2>
<a href="index.php">← Volver a proyectos</a>

<br><br>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
<?php if ($exito): ?>
    <p style="color:green"><?= $exito ?></p>
<?php endif; ?>

<form method="POST">
    <label>Nombre: *</label><br>
    <input type="text" name="nombre" required style="width:400px"><br><br>

    <label>Descripción: *</label><br>
    <textarea name="descripcion" rows="3" cols="50" required></textarea><br><br>

    <label>Ubicación: *</label><br>
    <input type="text" name="ubicacion" required style="width:400px"><br><br>

    <label>Tipo de proyecto: *</label><br>
    <select name="id_tipo_proyecto" required>
        <option value="">-- Selecciona --</option>
        <?php foreach ($tipos as $t): ?>
            <option value="<?= $t['id_tipo_proyecto'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Contrato activo: *</label><br>
    <select name="id_contrato" required>
        <option value="">-- Selecciona --</option>
        <?php foreach ($contratos as $c): ?>
            <option value="<?= $c['id_contrato'] ?>">
                #<?= $c['id_contrato'] ?> — <?= htmlspecialchars($c['cliente']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Fecha de inicio: *</label><br>
    <input type="date" name="fecha_inicio" required><br><br>

    <label>Fecha fin estimada: *</label><br>
    <input type="date" name="fecha_fin_estimada" required><br><br>

    <label>Estado inicial:</label><br>
    <select name="estado">
        <option value="planificacion">Planificación</option>
        <option value="ejecucion">Ejecución</option>
    </select><br><br>

    <button type="submit">Crear proyecto</button>
</form>

</body>
</html>