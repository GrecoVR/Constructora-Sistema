<?php
date_default_timezone_set('America/La_Paz');

require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('ver_auditoria');
registrarAccion(LOG_VER_LOGS);

$pdo      = conectar();
$permisos = $_SESSION['permisos'];

// Filtros GET
$filtro_usuario = trim($_GET['usuario'] ?? '');
$filtro_desde   = $_GET['desde'] ?? '';
$filtro_hasta   = $_GET['hasta'] ?? '';

$where  = ['1=1'];
$params = [];

if ($filtro_usuario) {
    $where[]  = 'us.nombre_usuario LIKE ?';
    $params[] = "%{$filtro_usuario}%";
}
if ($filtro_desde) {
    $where[]  = 'DATE(rs.fecha_hora) >= ?';
    $params[] = $filtro_desde;
}
if ($filtro_hasta) {
    $where[]  = 'DATE(rs.fecha_hora) <= ?';
    $params[] = $filtro_hasta;
}

$sql = "
    SELECT rs.id_registro_sistema,
           us.nombre_usuario,
           rs.accion,
           rs.fecha_hora
    FROM registros_sistema rs
    JOIN usuarios_sistema us
      ON us.id_usuario_sistema = rs.id_usuario_sistema
    WHERE " . implode(' AND ', $where) . "
    ORDER BY rs.fecha_hora DESC
    LIMIT 200
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Usuarios para filtro
$usuarios = $pdo->query("
    SELECT DISTINCT nombre_usuario
    FROM usuarios_sistema
    ORDER BY nombre_usuario ASC
")->fetchAll(PDO::FETCH_COLUMN);

// Clasificar tipo de acción para badge
function tipoBadge(string $accion): array {
    if (str_starts_with($accion, 'CREAR'))    return ['bg'=>'#D4EDDA','color'=>'#155724','label'=>'CREAR'];
    if (str_starts_with($accion, 'EDITAR'))   return ['bg'=>'#D1ECF1','color'=>'#0C5460','label'=>'EDITAR'];
    if (str_starts_with($accion, 'ELIMINAR')) return ['bg'=>'#FDECEA','color'=>'#721C24','label'=>'ELIMINAR'];
    if (str_starts_with($accion, 'VER'))      return ['bg'=>'#EBF5FB','color'=>'#1A5276','label'=>'VER'];
    if (str_contains($accion,   '['))         return ['bg'=>'#FFF3CD','color'=>'#856404','label'=>'ACCIÓN'];
    return ['bg'=>'#F4F6F9','color'=>'#555','label'=>'INFO'];
}
?>
<?php require_once '../../modules/layouts/header.php'; ?>

<style>
.log-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 99px;
    letter-spacing: 0.5px;
    white-space: nowrap;
}
.filter-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 14px rgba(0,0,0,0.06);
    padding: 18px 22px;
    margin-bottom: 20px;
}
</style>

<!-- Encabezado -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fw-bold mb-1" style="font-size:20px;color:#1C1C1E;">
            Registros del sistema
        </h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0" style="font-size:13px;">
                <li class="breadcrumb-item">
                    <a href="../../modules/dashboard/dashboard.php">Dashboard</a>
                </li>
                <li class="breadcrumb-item active">Registros</li>
            </ol>
        </nav>
    </div>
    <span style="font-size:12px;font-weight:700;background:#EBF5FB;
                 color:#2980B9;padding:5px 14px;border-radius:99px;">
        <?= count($logs) ?> registros
    </span>
</div>

<!-- Filtros -->
<div class="filter-card">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-12 col-sm-4 col-lg-3">
            <label class="form-label"
                   style="font-size:11px;font-weight:700;color:#95A5A6;
                          text-transform:uppercase;letter-spacing:0.5px;">
                Usuario
            </label>
            <select name="usuario" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach ($usuarios as $u): ?>
                <option value="<?= htmlspecialchars($u) ?>"
                    <?= $filtro_usuario === $u ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-sm-4 col-lg-3">
            <label class="form-label"
                   style="font-size:11px;font-weight:700;color:#95A5A6;
                          text-transform:uppercase;letter-spacing:0.5px;">
                Desde
            </label>
            <input type="date" name="desde"
                   class="form-control form-control-sm"
                   value="<?= htmlspecialchars($filtro_desde) ?>">
        </div>
        <div class="col-6 col-sm-4 col-lg-3">
            <label class="form-label"
                   style="font-size:11px;font-weight:700;color:#95A5A6;
                          text-transform:uppercase;letter-spacing:0.5px;">
                Hasta
            </label>
            <input type="date" name="hasta"
                   class="form-control form-control-sm"
                   value="<?= htmlspecialchars($filtro_hasta) ?>">
        </div>
        <div class="col-12 col-lg-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-fill">
                <i class="bi bi-search me-1"></i> Filtrar
            </button>
            <?php if ($filtro_usuario || $filtro_desde || $filtro_hasta): ?>
            <a href="index.php"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x"></i>
            </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Tabla -->
<div class="section-card">
    <div class="section-head">
        <i class="bi bi-clock-history" style="color:#3498DB;font-size:15px;"></i>
        <h5>Historial de acciones</h5>
    </div>
    <div class="table-responsive">
        <table id="tabla-logs"
               class="table table-hover mb-0"
               style="font-size:13px;">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th style="width:110px;">Tipo</th>
                    <th style="width:120px;">Usuario</th>
                    <th>Acción</th>
                    <th style="width:140px;">Fecha y hora</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log):
                    $badge = tipoBadge($log['accion']);
                    // Limpiar IP del texto visible
                    $accion_limpia = preg_replace('/ — IP: [\d\.]+$/', '', $log['accion']);
                ?>
                <tr>
                    <td style="color:#95A5A6;font-weight:600;">
                        <?= $log['id_registro_sistema'] ?>
                    </td>
                    <td>
                        <span class="log-badge"
                              style="background:<?= $badge['bg'] ?>;
                                     color:<?= $badge['color'] ?>;">
                            <?= $badge['label'] ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:7px;">
                            <div style="width:26px;height:26px;border-radius:50%;
                                        background:linear-gradient(135deg,#EBF5FB,#D6EAF8);
                                        color:#2980B9;font-size:11px;font-weight:800;
                                        display:flex;align-items:center;
                                        justify-content:center;flex-shrink:0;">
                                <?= mb_strtoupper(mb_substr($log['nombre_usuario'], 0, 1)) ?>
                            </div>
                            <span style="font-weight:600;color:#1C1C1E;">
                                <?= htmlspecialchars($log['nombre_usuario']) ?>
                            </span>
                        </div>
                    </td>
                    <td style="color:#34495E;max-width:420px;
                               overflow:hidden;text-overflow:ellipsis;
                               white-space:nowrap;"
                        title="<?= htmlspecialchars($log['accion']) ?>">
                        <?= htmlspecialchars($accion_limpia) ?>
                    </td>
                    <td style="color:#95A5A6;white-space:nowrap;">
                        <?= date('d/m/Y H:i:s', strtotime($log['fecha_hora'])) ?>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="5" class="text-center py-4"
                        style="color:#95A5A6;">
                        No hay registros con los filtros aplicados.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function () {
    $('#tabla-logs').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
        },
        order: [[4, 'desc']],   // fecha desc por defecto
        columnDefs: [
            { orderable: false, targets: [3] }  // acción no ordenable
        ],
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        dom: '<"d-flex justify-content-between align-items-center mb-3"lf>rtip'
    });
});
</script>

<?php require_once '../../modules/layouts/footer.php'; ?>