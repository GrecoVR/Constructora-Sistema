<?php
require_once '../../config/database.php';
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../utils/permisos.php';
require_once '../../utils/fecha.php';
require_once '../../triggers/TriggerManager.php';

$pdo   = conectar();
$permisos = $_SESSION['permisos'];
$error = '';
$exito = '';

// Registrar pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_pago'])) {
    $id_empleado  = intval($_POST['id_empleado'] ?? 0);
    $id_metodo    = intval($_POST['id_metodo_pago'] ?? 0);
    $fecha        = $_POST['fecha_pago'] ?? date('Y-m-d');
    $monto        = floatval($_POST['monto'] ?? 0);
    $estado       = $_POST['estado'] ?? 'pendiente';

    if ($id_empleado && $id_metodo && $monto > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO pagos_empleados (id_empleado, id_metodo_pago, fecha_pago, monto, estado)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$id_empleado, $id_metodo, $fecha, $monto, $estado]);
        $id_pago = $pdo->lastInsertId();

        // Trigger si el pago falló
        if ($estado === 'fallido') {
            $manager = new TriggerManager($pdo);
            $manager->ejecutar('pagos.pago_empleado_fallido', [
                'id_pago_empleado' => $id_pago,
                'id_empleado'      => $id_empleado
            ]);
        }

        registrarAccion(LOG_REG_PAGO_EMPLEADO . ' — empleado ID:' . $id_empleado . ' Bs ' . number_format($monto, 2));
        $exito = 'Pago registrado correctamente';
    } else {
        $error = 'Completa todos los campos';
    }
}

// Registrar ajuste
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_ajuste'])) {
    $id_pago_empleado = intval($_POST['id_pago_empleado'] ?? 0);
    $tipo_ajuste      = $_POST['tipo_ajuste'] ?? '';
    $concepto         = trim($_POST['concepto'] ?? '');
    $monto            = floatval($_POST['monto_ajuste'] ?? 0);

    if ($id_pago_empleado && $tipo_ajuste && $concepto && $monto != 0) {
        $monto_real = $tipo_ajuste === 'deduccion' ? -abs($monto) : abs($monto);

        $stmt = $pdo->prepare("
            INSERT INTO ajustes_pago (id_pago_empleado, tipo_ajuste, concepto, monto)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$id_pago_empleado, $tipo_ajuste, $concepto, $monto_real]);

        // Notifica al empleado del ajuste
        $manager = new TriggerManager($pdo);
        $manager->ejecutar('pagos.ajuste_aplicado', [
            'id_pago_empleado' => $id_pago_empleado,
            'tipo_ajuste'      => $tipo_ajuste,
            'concepto'         => $concepto,
            'monto'            => $monto_real
        ]);

        registrarAccion(LOG_REG_AJUSTE_PAGO . ' — pago ID:' . $id_pago_empleado . ' ' . $tipo_ajuste . ' Bs ' . number_format(abs($monto_real), 2));
        $exito = 'Ajuste registrado correctamente';
    } else {
        $error = 'Completa todos los campos del ajuste';
    }
}

$empleados = $pdo->query("
    SELECT id_empleado, nombre FROM empleados
    WHERE estado = 'activo' ORDER BY nombre ASC
")->fetchAll();

$metodos = $pdo->query("SELECT * FROM metodos_pago ORDER BY nombre ASC")->fetchAll();

// Pagos recientes con ajustes
$pagos = $pdo->query("
    SELECT pe.id_pago_empleado, pe.fecha_pago, pe.monto, pe.estado,
           e.nombre as empleado, mp.nombre as metodo,
           COALESCE(SUM(ap.monto), 0) as total_ajustes
    FROM pagos_empleados pe
    JOIN empleados e ON e.id_empleado = pe.id_empleado
    JOIN metodos_pago mp ON mp.id_metodo_pago = pe.id_metodo_pago
    LEFT JOIN ajustes_pago ap ON ap.id_pago_empleado = pe.id_pago_empleado
    GROUP BY pe.id_pago_empleado, pe.fecha_pago, pe.monto,
             pe.estado, e.nombre, mp.nombre
    ORDER BY pe.fecha_pago DESC
    LIMIT 30
")->fetchAll();
?>

<?php require_once '../../modules/layouts/header.php'; ?>

    <!-- Encabezado -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-4">
                <i class="bi bi-credit-card-fill text-primary me-2"></i>Pagos a Empleados
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="../../modules/dashboard/dashboard.php" class="text-decoration-none">
                            <i class="bi bi-house-door me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="index.php" class="text-decoration-none">Pagos</a>
                    </li>
                    <li class="breadcrumb-item active">Empleados</li>
                </ol>
            </nav>
        </div>
        <a href="pedidos.php" class="btn btn-secondary">
            <i class="bi bi-box-seam me-1"></i>Ver pagos pedidos
        </a>
    </div>

    <!-- Alertas -->
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

    <div class="row g-4 mb-4">

        <!-- FORMULARIO REGISTRAR PAGO -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-plus-circle-fill fs-5"></i>
                    <h5 class="mb-0">Registrar pago</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-person me-1"></i>Empleado <span class="text-danger">*</span>
                            </label>
                            <select name="id_empleado" class="form-select" required>
                                <option value="">— Selecciona —</option>
                                <?php foreach ($empleados as $e): ?>
                                    <option value="<?= $e['id_empleado'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-wallet2 me-1"></i>Método de pago <span class="text-danger">*</span>
                            </label>
                            <select name="id_metodo_pago" class="form-select" required>
                                <option value="">— Selecciona —</option>
                                <?php foreach ($metodos as $m): ?>
                                    <option value="<?= $m['id_metodo_pago'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-currency-dollar me-1"></i>Monto (Bs) <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="monto" class="form-control" step="0.01" min="0.01" required placeholder="0.00">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-calendar3 me-1"></i>Fecha de pago
                                </label>
                                <input type="date" name="fecha_pago" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-flag me-1"></i>Estado
                            </label>
                            <select name="estado" class="form-select">
                                <option value="completado">Completado</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="fallido">Fallido</option>
                            </select>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="registrar_pago" class="btn btn-primary btn-lg">
                                <i class="bi bi-send me-2"></i>Registrar pago
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- FORMULARIO REGISTRAR AJUSTE -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-sliders fs-5"></i>
                    <h5 class="mb-0">Registrar ajuste (bono o descuento)</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-receipt me-1"></i>Pago a ajustar <span class="text-danger">*</span>
                            </label>
                            <select name="id_pago_empleado" class="form-select" required>
                                <option value="">— Selecciona pago —</option>
                                <?php foreach ($pagos as $p): ?>
                                    <option value="<?= $p['id_pago_empleado'] ?>">
                                        #<?= $p['id_pago_empleado'] ?> — <?= htmlspecialchars($p['empleado']) ?>
                                        (<?= formatoFechaCorta($p['fecha_pago']) ?> — Bs <?= number_format($p['monto'], 2) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-tag me-1"></i>Tipo de ajuste <span class="text-danger">*</span>
                            </label>
                            <select name="tipo_ajuste" class="form-select" required>
                                <option value="percepcion">Bono / Percepción</option>
                                <option value="deduccion">Descuento / Deducción</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-chat-left-text me-1"></i>Concepto <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="concepto" class="form-control" required placeholder="Ej: Bono productividad">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-currency-dollar me-1"></i>Monto (Bs) <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="monto_ajuste" class="form-control" step="0.01" min="0.01" required placeholder="0.00">
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="registrar_ajuste" class="btn btn-success btn-lg">
                                <i class="bi bi-check2-circle me-2"></i>Registrar ajuste
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- HISTORIAL DE PAGOS -->
    <div class="card shadow-sm">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-clock-history fs-5"></i>
            <h5 class="mb-0">Últimos 30 pagos</h5>
        </div>
        <div class="card-body">
            <?php if ($pagos): ?>
                <div class="table-responsive">
                    <table id="tabla-datos" class="table table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">ID</th>
                                <th>Empleado</th>
                                <th>Fecha</th>
                                <th>Método</th>
                                <th class="text-end">Monto (Bs)</th>
                                <th class="text-end">Ajustes (Bs)</th>
                                <th class="text-end">Total real (Bs)</th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagos as $p): ?>
                                <tr>
                                    <td class="ps-3 text-muted fw-semibold">#<?= $p['id_pago_empleado'] ?></td>
                                    <td>
                                        <span class="fw-semibold">
                                            <i class="bi bi-person-fill text-primary me-1"></i><?= htmlspecialchars($p['empleado']) ?>
                                        </span>
                                    </td>
                                    <td class="text-muted"><?= formatoFechaCorta($p['fecha_pago']) ?></td>
                                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= htmlspecialchars($p['metodo']) ?></span></td>
                                    <td class="text-end"><?= number_format($p['monto'], 2) ?></td>
                                    <td class="text-end fw-semibold <?= $p['total_ajustes'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= number_format($p['total_ajustes'], 2) ?>
                                    </td>
                                    <td class="text-end fw-bold"><?= number_format($p['monto'] + $p['total_ajustes'], 2) ?></td>
                                    <td class="text-center">
                                        <?php if ($p['estado'] === 'completado'): ?>
                                            <span class="badge bg-success-subtle text-success-emphasis">Completado</span>
                                        <?php elseif ($p['estado'] === 'fallido'): ?>
                                            <span class="badge bg-danger-subtle text-danger-emphasis">Fallido</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                    <p class="mb-0">No hay pagos registrados aún.</p>
                </div>
            <?php endif; ?>
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