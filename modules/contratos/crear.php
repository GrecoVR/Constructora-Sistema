<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('gestionar_contratos');

$pdo   = conectar();
$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cotizacion = intval($_POST['id_cotizacion'] ?? 0);
    $fecha_firma   = $_POST['fecha_firma'] ?? '';
    $clausulas     = trim($_POST['clausulas'] ?? '');
    $estado        = $_POST['estado'] ?? 'borrador';

    if ($id_cotizacion && $fecha_firma) {
        // Verifica que la cotización no tenga ya contrato
        $stmt = $pdo->prepare("SELECT id_contrato FROM contratos WHERE id_cotizacion = ?");
        $stmt->execute([$id_cotizacion]);

        if ($stmt->fetch()) {
            $error = 'Esta cotización ya tiene un contrato asociado';
        } else {
            $stmt2 = $pdo->prepare("
                INSERT INTO contratos (id_cotizacion, fecha_firma, clausulas, estado)
                VALUES (?, ?, ?, ?)
            ");
            $stmt2->execute([$id_cotizacion, $fecha_firma, $clausulas, $estado]);
            $id_nuevo = $pdo->lastInsertId();
            registrarAccion("Creó contrato ID: $id_nuevo");
            $exito = "Contrato #$id_nuevo creado correctamente";
        }
    } else {
        $error = 'Completa los campos obligatorios';
    }
}

// Solo cotizaciones aprobadas sin contrato
$cotizaciones = $pdo->query("
    SELECT co.id_cotizacion, co.monto_total, cl.nombre as cliente
    FROM cotizaciones co
    JOIN clientes cl ON cl.id_cliente = co.id_cliente
    WHERE co.estado = 'aprobada'
    AND co.id_cotizacion NOT IN (SELECT id_cotizacion FROM contratos)
    ORDER BY cl.nombre ASC
")->fetchAll();
?>

<?php require_once '../../modules/layouts/header.php'; ?>

<nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index.php"> Contratos</a></li>
    <li class="breadcrumb-item active" aria-current="page"> Nuevo Contrato</li>
  </ol>
</nav>

<h2 class="mb-4 fw-semibold">➕ Nuevo Contrato</h2>


<?php if ($error): ?>
    <div class="toast fade show align-items-center text-bg-danger border-0 w-100" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <?= $error ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
<?php endif; ?>
<?php if ($exito): ?>
    <div class="toast fade show align-items-center text-bg-success border-0 w-100" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <?= $exito ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
<?php endif; ?>

<?php if (empty($cotizaciones)): ?>
    <p style="color:orange">No hay cotizaciones aprobadas sin contrato disponibles.</p>
<?php else: ?>

<div class="row">
<div class="col-lg-4 col-md-6 col-sm-8 col-xs-12">
<div class="card shadow mt-2">
  <div class="card-body">
    <form method="POST">
        <div class="mb-3">
        <label class="form-label" for="id_cotizacion">Cotización aprobada: *</label>
        <select class="form-select" id="id_cotizacion" name="id_cotizacion" required>
            <option value="">-- Selecciona --</option>
            <?php foreach ($cotizaciones as $co): ?>
                <option value="<?= $co['id_cotizacion'] ?>">
                    #<?= $co['id_cotizacion'] ?> — <?= htmlspecialchars($co['cliente']) ?>
                    (Bs <?= number_format($co['monto_total'], 2) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        </div>
        <div class="mb-3">
        <label class="form-control" class="form-label" for="fecha_firma">Fecha de firma: *</label>
        <input type="date" id="fecha_firma" name="fecha_firma" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="mb-3">
        <label class="form-label" for="clausulas">Cláusulas:</label>
        <textarea class="form-control" id="clausulas" name="clausulas" rows="6" cols="60"
                  placeholder="Condiciones, plazos, penalidades..."></textarea>
        </div>
        <div class="mb-3">
        <label class="form-label" for="estado">Estado inicial:</label>
        <select class="form-select" id="estado" name="estado">
            <option value="borrador">Borrador</option>
            <option value="activo">Activo</option>
        </select>
        </div>
        <button class="btn btn-primary" type="submit">Crear contrato</button>
    </form>
  </div>
</div>
</div><!-- end col -->
</div><!-- end row -->

<?php endif; ?>

<?php require_once '../../modules/layouts/footer.php'; ?>