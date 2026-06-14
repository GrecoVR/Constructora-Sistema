<?php
require_once '../config/session.php';
require_once '../middleware/auth_cliente.php';
require_once '../config/database.php';

$pdo        = conectar();
$id_cliente = $_SESSION['id_cliente'];

// Todos los pagos del cliente
$stmt = $pdo->prepare("
    SELECT pc.id_pago_cliente, pc.monto, pc.fecha_pago,
           pc.estado, mp.nombre AS metodo,
           p.nombre AS proyecto
    FROM pagos_cliente pc
    JOIN contratos c     ON c.id_contrato     = pc.id_contrato
    JOIN cotizaciones co ON co.id_cotizacion  = c.id_cotizacion
    JOIN metodos_pago mp ON mp.id_metodo_pago = pc.id_metodo_pago
    LEFT JOIN proyectos p ON p.id_contrato    = c.id_contrato
    WHERE co.id_cliente = ?
    ORDER BY pc.fecha_pago DESC
");
$stmt->execute([$id_cliente]);
$pagos = $stmt->fetchAll();

// Totales
$completados = array_filter($pagos, fn($p) => $p['estado'] === 'completado');
$pendientes  = array_filter($pagos, fn($p) => $p['estado'] === 'pendiente');
$fallidos    = array_filter($pagos, fn($p) => $p['estado'] === 'fallido');

$total_pagado   = array_sum(array_column(iterator_to_array($completados), 'monto'));
$total_pendiente= array_sum(array_column(iterator_to_array($pendientes),  'monto'));
?>
<?php require_once '../modules/layouts/header_cliente.php'; ?>

<style>
.fade-up {
    opacity:0; transform:translateY(20px);
    animation:fadeUp .5s cubic-bezier(.22,1,.36,1) forwards;
}
@keyframes fadeUp { to{opacity:1;transform:translateY(0);} }
.delay-1{animation-delay:.06s} .delay-2{animation-delay:.12s}
.delay-3{animation-delay:.18s} .delay-4{animation-delay:.24s}

.cl-kpi {
    background:#fff; border-radius:14px;
    padding:18px 20px;
    box-shadow:0 2px 14px rgba(0,0,0,.06);
    border-left:4px solid transparent;
    transition:box-shadow .2s,transform .2s;
}
.cl-kpi:hover { box-shadow:0 6px 24px rgba(0,0,0,.09); transform:translateY(-2px); }

.cl-card {
    background:#fff; border-radius:14px;
    box-shadow:0 2px 14px rgba(0,0,0,.06);
    overflow:hidden;
}
.cl-card-head {
    padding:16px 20px;
    border-bottom:1px solid #F0F2F4;
    display:flex; align-items:center; gap:10px;
    font-size:14px; font-weight:700; color:#1C1C1E;
}

.pago-estado {
    font-size:11px; font-weight:700;
    padding:3px 10px; border-radius:99px;
    white-space:nowrap;
}

[data-bs-theme="dark"] .cl-kpi,
[data-bs-theme="dark"] .cl-card { background:#161b27 !important; }
[data-bs-theme="dark"] .cl-card-head { color:#e2e8f0 !important; border-bottom-color:#1e2535 !important; }
[data-bs-theme="dark"] .table { color:#cbd5e1 !important; }
[data-bs-theme="dark"] .table thead th { background:#1a1f2e !important; color:#6b7a8d !important; border-bottom-color:#1e2535 !important; }
[data-bs-theme="dark"] .table tbody tr:hover { background:#1a1f2e !important; }
</style>

<!-- Encabezado -->
<div class="fade-up mb-4">
    <h1 style="font-size:21px;font-weight:800;color:#1C1C1E;">Mis pagos</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0" style="font-size:13px;margin-top:4px;">
            <li class="breadcrumb-item">
                <a href="index.php" style="color:#27AE60;">Inicio</a>
            </li>
            <li class="breadcrumb-item active">Mis pagos</li>
        </ol>
    </nav>
</div>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3 fade-up delay-1">
        <div class="cl-kpi" style="border-left-color:#27AE60;">
            <div style="font-size:10.5px;font-weight:700;color:#95A5A6;
                        text-transform:uppercase;letter-spacing:.8px;margin-bottom:5px;">
                Total pagado
            </div>
            <div style="font-size:22px;font-weight:800;color:#27AE60;">
                Bs <?= number_format($total_pagado, 0, '.', ',') ?>
            </div>
            <div style="font-size:12px;color:#95A5A6;margin-top:3px;">
                <?= count($completados) ?> pago(s) completados
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3 fade-up delay-2">
        <div class="cl-kpi" style="border-left-color:#E67E22;">
            <div style="font-size:10.5px;font-weight:700;color:#95A5A6;
                        text-transform:uppercase;letter-spacing:.8px;margin-bottom:5px;">
                Pendiente
            </div>
            <div style="font-size:22px;font-weight:800;color:#E67E22;">
                Bs <?= number_format($total_pendiente, 0, '.', ',') ?>
            </div>
            <div style="font-size:12px;color:#95A5A6;margin-top:3px;">
                <?= count($pendientes) ?> pago(s) por realizar
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3 fade-up delay-3">
        <div class="cl-kpi" style="border-left-color:#3498DB;">
            <div style="font-size:10.5px;font-weight:700;color:#95A5A6;
                        text-transform:uppercase;letter-spacing:.8px;margin-bottom:5px;">
                Total registros
            </div>
            <div style="font-size:22px;font-weight:800;color:#3498DB;">
                <?= count($pagos) ?>
            </div>
            <div style="font-size:12px;color:#95A5A6;margin-top:3px;">
                Historial completo
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3 fade-up delay-4">
        <div class="cl-kpi" style="border-left-color:#E74C3C;">
            <div style="font-size:10.5px;font-weight:700;color:#95A5A6;
                        text-transform:uppercase;letter-spacing:.8px;margin-bottom:5px;">
                Fallidos
            </div>
            <div style="font-size:22px;font-weight:800;color:#E74C3C;">
                <?= count($fallidos) ?>
            </div>
            <div style="font-size:12px;color:#95A5A6;margin-top:3px;">
                Requieren atención
            </div>
        </div>
    </div>
</div>

<!-- Tabla de pagos -->
<div class="cl-card fade-up delay-2">
    <div class="cl-card-head">
        <i class="bi bi-credit-card-fill" style="color:#27AE60;"></i>
        Historial de pagos
        <span style="margin-left:auto;font-size:11px;font-weight:700;
                     background:#D5F5E3;color:#1E8449;
                     padding:3px 10px;border-radius:99px;">
            <?= count($pagos) ?> registros
        </span>
    </div>
    <div class="table-responsive p-1">
        <table class="table table-hover" id="tabla-pagos">
            <thead>
                <tr>
                    <th>Proyecto</th>
                    <th>Método de pago</th>
                    <th class="text-end">Monto (Bs)</th>
                    <th class="text-center">Fecha</th>
                    <th class="text-center">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagos as $pago): ?>
                <tr>
                    <td style="font-weight:600;color:#1C1C1E;">
                        <?= htmlspecialchars($pago['proyecto'] ?? '—') ?>
                    </td>
                    <td style="color:#7F8C8D;font-size:13px;">
                        <?= htmlspecialchars($pago['metodo']) ?>
                    </td>
                    <td class="text-end"
                        style="font-weight:800;font-size:14px;
                               color:<?= $pago['estado']==='completado'?'#27AE60'
                                   :($pago['estado']==='pendiente'?'#E67E22':'#E74C3C') ?>;">
                        <?= number_format($pago['monto'], 2, '.', ',') ?>
                    </td>
                    <td class="text-center" style="color:#95A5A6;font-size:13px;">
                        <?= date('d/m/Y', strtotime($pago['fecha_pago'])) ?>
                    </td>
                    <td class="text-center">
                        <?php
                        $eb = match($pago['estado']){
                            'completado'=>['#D4EDDA','#155724','bi-check-circle-fill'],
                            'pendiente' =>['#FFF3CD','#856404','bi-clock-fill'],
                            'fallido'   =>['#FDECEA','#721C24','bi-x-circle-fill'],
                            default     =>['#F4F6F9','#555','bi-dash']
                        };
                        ?>
                        <span class="pago-estado"
                              style="background:<?=$eb[0]?>;color:<?=$eb[1]?>;">
                            <i class="bi <?=$eb[2]?> me-1"></i>
                            <?= ucfirst($pago['estado']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if (empty($pagos)): ?>
                <tr>
                    <td colspan="5" class="text-center py-4"
                        style="color:#95A5A6;">
                        No tienes pagos registrados.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function(){
    $('#tabla-pagos').DataTable({
        language:{ url:'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json' },
        order:[[3,'desc']],
        pageLength:10,
        lengthMenu:[5,10,25,50],
        searchDelay:0,
        dom:'<"d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2"lf>rtip',
        columnDefs:[{orderable:false,targets:[4]}],
        initComplete:function(){
            var input=$(this.api().table().container()).find('input[type="search"]');
            input.addClass('dt-search-custom').attr('placeholder','Buscar pago...');
            input.off('keyup.DT search.DT input.DT paste.DT cut.DT');
            const dtPagos = this.api();
            input[0].addEventListener('input', function() {
            dtPagos.search(this.value).draw(false);
            }, true);
        }
    });
});

</script>

<?php require_once '../modules/layouts/footer_cliente.php'; ?>