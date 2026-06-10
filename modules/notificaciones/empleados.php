<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('ver_dashboard');
registrarAccion(LOG_VER_NOTIFICACIONES_EMPLEADOS);

$pdo   = conectar();
$permisos = $_SESSION['permisos'];
$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_empleado = intval($_POST['id_empleado'] ?? 0);
    $titulo      = trim($_POST['titulo'] ?? '');
    $contenido   = trim($_POST['contenido'] ?? '');
    $todos        = isset($_POST['todos']);

    if ($titulo && $contenido) {
        if ($todos) {
            $empleados_activos = $pdo->query("
                SELECT id_empleado FROM empleados WHERE estado = 'activo'
            ")->fetchAll();

            $stmt = $pdo->prepare("
                INSERT INTO notificaciones_empleados (id_empleado, titulo, contenido)
                VALUES (?, ?, ?)
            ");
            foreach ($empleados_activos as $emp) {
                $stmt->execute([$emp['id_empleado'], $titulo, $contenido]);
            }
            registrarAccion(LOG_ENVIAR_NOTIF_EMP . ' — "' . $titulo . '"' . ($todos ? ' (masiva a todos)' : ' empleado ID:' . $id_empleado));
            $exito = 'Notificación enviada a todos los empleados';

        } elseif ($id_empleado) {
            $stmt = $pdo->prepare("
                INSERT INTO notificaciones_empleados (id_empleado, titulo, contenido)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$id_empleado, $titulo, $contenido]);
            registrarAccion(LOG_ENVIAR_NOTIF_EMP . ' — "' . $titulo . '"' . ($todos ? ' (masiva a todos)' : ' empleado ID:' . $id_empleado));
            $exito = 'Notificación enviada correctamente';
        } else {
            $error = 'Selecciona un empleado o marca enviar a todos';
        }
    } else {
        $error = 'Completa título y mensaje';
    }
}

$empleados = $pdo->query("
    SELECT id_empleado, nombre FROM empleados 
    WHERE estado = 'activo' ORDER BY nombre ASC
")->fetchAll();

$stmt_mias = $pdo->prepare("
    SELECT n.id_notificacion_empleado, n.titulo, n.contenido
    FROM notificaciones_empleados n
    JOIN usuarios_sistema us ON us.id_empleado = n.id_empleado
    WHERE us.id_usuario_sistema = ?
    ORDER BY n.id_notificacion_empleado DESC
    LIMIT 10
");
$stmt_mias->execute([$_SESSION['id_usuario']]);
$mis_notificaciones = $stmt_mias->fetchAll();

$historial = [];
if (in_array('gestionar_empleados', $_SESSION['permisos'])) {
    $historial = $pdo->query("
        SELECT n.titulo, n.contenido, e.nombre as empleado
        FROM notificaciones_empleados n
        JOIN empleados e ON e.id_empleado = n.id_empleado
        ORDER BY n.id_notificacion_empleado DESC
        LIMIT 20
    ")->fetchAll();
}
?>

<?php require_once '../../modules/layouts/header.php'; ?>

    <!-- Encabezado -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h2 class="mb-4 fw-bold">
                <i class="bi bi-bell-fill text-warning me-2"></i>Notificaciones a Empleados
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="../../modules/dashboard/dashboard.php" class="text-decoration-none">
                            <i class="bi bi-house-door me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Notificaciones Empleados</li>
                </ol>
            </nav>
        </div>
        <a href="clientes.php" class="btn btn-secondary">
            <i class="bi bi-people me-1"></i>Ver notificaciones clientes
        </a>
    </div>

    <!-- MIS NOTIFICACIONES -->
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-inbox-fill fs-5"></i>
            <h5 class="mb-0">Mis notificaciones</h5>
        </div>
        <div class="card-body">
            <?php if ($mis_notificaciones): ?>
                <div class="table-responsive">
                    <table class="tabla-datos table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th class="ps-3" style="width:60px">#</th>
                                <th>Título</th>
                                <th>Mensaje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mis_notificaciones as $mn): ?>
                                <tr>
                                    <td class="ps-3 text-muted fw-semibold"><?= $mn['id_notificacion_empleado'] ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($mn['titulo']) ?></td>
                                    <td class="text-muted"><?= htmlspecialchars($mn['contenido']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bell-slash fs-1 d-block mb-2 opacity-50"></i>
                    <p class="mb-0">No tienes notificaciones.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (in_array('gestionar_empleados', $_SESSION['permisos'])): ?>

    <div class="row g-4">

        <!-- FORMULARIO -->
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white d-flex align-items-center gap-2">
                    <i class="bi bi-send-fill fs-5"></i>
                    <h5 class="mb-0">Enviar notificación</h5>
                </div>
                <div class="card-body">

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                            <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                            <span><?= $error ?></span>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($exito): ?>
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                            <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                            <span><?= $exito ?></span>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="todos" id="todos" role="switch"
                                       onchange="document.getElementById('select_empleado').disabled=this.checked">
                                <label class="form-check-label fw-semibold" for="todos">
                                    <i class="bi bi-broadcast me-1 text-warning"></i>Enviar a todos los empleados
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="select_empleado" class="form-label fw-semibold">
                                <i class="bi bi-person me-1"></i>Empleado específico
                            </label>
                            <select name="id_empleado" id="select_empleado" class="form-select">
                                <option value="">— Selecciona —</option>
                                <?php foreach ($empleados as $emp): ?>
                                    <option value="<?= $emp['id_empleado'] ?>"><?= htmlspecialchars($emp['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="titulo" class="form-label fw-semibold">
                                <i class="bi bi-fonts me-1"></i>Título <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="titulo" id="titulo" class="form-control" required placeholder="Ej: Recordatorio de reunión">
                        </div>

                        <div class="mb-4">
                            <label for="contenido" class="form-label fw-semibold">
                                <i class="bi bi-chat-left-text me-1"></i>Mensaje <span class="text-danger">*</span>
                            </label>
                            <textarea name="contenido" id="contenido" rows="5" class="form-control" required placeholder="Escribe el contenido de la notificación..."></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-send me-2"></i>Enviar notificación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- HISTORIAL GENERAL -->
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history fs-5"></i>
                    <h5 class="mb-0">Historial general</h5>
                </div>
                <div class="card-body">
                    <?php if ($historial): ?>
                        <div class="table-responsive">
                            <table class="tabla-datos table table-striped table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th class="ps-3">Empleado</th>
                                        <th>Título</th>
                                        <th>Mensaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historial as $h): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold">
                                                    <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($h['empleado']) ?>
                                                </span>
                                            </td>
                                            <td class="fw-semibold"><?= htmlspecialchars($h['titulo']) ?></td>
                                            <td class="text-muted small"><?= htmlspecialchars($h['contenido']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-journal-x fs-1 d-block mb-2 opacity-50"></i>
                            <p class="mb-0">No hay notificaciones enviadas.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <?php endif; ?>

<script>
$(document).ready(function() {
   var table = $('.tabla-datos').DataTable({
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
        },
        order: [],
        columnDefs: [
        {
          targets: -1,
          orderable: false
        }
        ]
    });
});    
</script>

<?php require_once '../../modules/layouts/footer.php'; ?>