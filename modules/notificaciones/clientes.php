<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('ver_dashboard');
registrarAccion('Vio notificaciones de clientes');

$pdo   = conectar();
$permisos = $_SESSION['permisos'];
$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = intval($_POST['id_cliente'] ?? 0);
    $titulo     = trim($_POST['titulo'] ?? '');
    $contenido  = trim($_POST['contenido'] ?? '');

    if ($id_cliente && $titulo && $contenido) {
        $stmt = $pdo->prepare("
            INSERT INTO notificaciones_clientes (id_cliente, titulo, contenido)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$id_cliente, $titulo, $contenido]);
        registrarAccion("Envió notificación al cliente ID: $id_cliente");
        $exito = 'Notificación enviada correctamente';
    } else {
        $error = 'Completa todos los campos';
    }
}

$clientes = $pdo->query("
    SELECT id_cliente, nombre FROM clientes ORDER BY nombre ASC
")->fetchAll();

$historial = $pdo->query("
    SELECT n.id_notificacion, n.titulo, n.contenido,
           c.nombre as cliente
    FROM notificaciones_clientes n
    JOIN clientes c ON c.id_cliente = n.id_cliente
    ORDER BY n.id_notificacion DESC
    LIMIT 20
")->fetchAll();
?>

<?php require_once '../../modules/layouts/header.php'; ?>

    <!-- Encabezado -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h2 class="mb-4 fw-bold">
                <i class="bi bi-bell-fill me-2"></i>Notificaciones a Clientes
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="../../modules/dashboard/dashboard.php" class="text-decoration-none">
                            <i class="bi bi-house-door me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Notificaciones Clientes</li>
                </ol>
            </nav>
        </div>
        <a href="empleados.php" class="btn btn-secondary">
            <i class="bi bi-person-badge me-1"></i>Ver notificaciones empleados
        </a>
    </div>

    <div class="row g-4">

        <!-- FORMULARIO -->
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header d-flex align-items-center gap-2">
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
                            <label for="id_cliente" class="form-label fw-semibold">
                                <i class="bi bi-building me-1"></i>Cliente <span class="text-danger">*</span>
                            </label>
                            <select name="id_cliente" id="id_cliente" class="form-select" required>
                                <option value="">— Selecciona cliente —</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?= $c['id_cliente'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="titulo" class="form-label fw-semibold">
                                <i class="bi bi-fonts me-1"></i>Título <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="titulo" id="titulo" class="form-control" required placeholder="Ej: Actualización de proyecto">
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

        <!-- HISTORIAL -->
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history fs-5"></i>
                    <h5 class="mb-0">Historial de notificaciones enviadas</h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($historial): ?>
                        <div class="table-responsive">
                            <table id="tabla-datos" class="table table-striped table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3" style="width:60px">#</th>
                                        <th>Cliente</th>
                                        <th>Título</th>
                                        <th>Mensaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historial as $h): ?>
                                        <tr>
                                            <td class="ps-3 text-muted fw-semibold"><?= $h['id_notificacion'] ?></td>
                                            <td>
                                                <span class="badge bg-success bg-opacity-10 text-success fw-semibold">
                                                    <i class="bi bi-building me-1"></i><?= htmlspecialchars($h['cliente']) ?>
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
                            <p class="mb-0">No hay notificaciones enviadas aún.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

<script>
$(document).ready(function() {
   var table = $('#tabla-datos').DataTable({
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