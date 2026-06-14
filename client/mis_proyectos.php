<?php
require_once '../config/session.php';
require_once '../middleware/auth_cliente.php';
require_once '../config/database.php';

$pdo        = conectar();
$id_cliente = $_SESSION['id_cliente'];

// Proyectos con etapas
$stmt = $pdo->prepare("
    SELECT p.id_proyecto, p.nombre, p.estado,
           p.fecha_inicio, p.fecha_fin_estimada,
           p.descripcion, p.ubicacion,
           tp.nombre AS tipo,
           COALESCE(AVG(ep.porcentaje_avance), 0) AS avance,
           COUNT(ep.id_etapa_proyecto) AS total_etapas
    FROM proyectos p
    JOIN tipos_proyecto tp   ON tp.id_tipo_proyecto = p.id_tipo_proyecto
    JOIN contratos c         ON c.id_contrato       = p.id_contrato
    JOIN cotizaciones co     ON co.id_cotizacion    = c.id_cotizacion
    LEFT JOIN etapas_proyecto ep ON ep.id_proyecto  = p.id_proyecto
    WHERE co.id_cliente = ?
    GROUP BY p.id_proyecto, p.nombre, p.estado,
             p.fecha_inicio, p.fecha_fin_estimada,
             p.descripcion, p.ubicacion, tp.nombre
    ORDER BY p.fecha_inicio DESC
");
$stmt->execute([$id_cliente]);
$proyectos = $stmt->fetchAll();

// Si hay id en GET, traer detalle de ese proyecto
$proyecto_detalle = null;
$etapas_detalle   = [];

if (!empty($_GET['id'])) {
    $id_proy = (int)$_GET['id'];

    $sd = $pdo->prepare("
        SELECT p.*, tp.nombre AS tipo,
               cl.nombre AS cliente_nombre
        FROM proyectos p
        JOIN tipos_proyecto tp   ON tp.id_tipo_proyecto = p.id_tipo_proyecto
        JOIN contratos c         ON c.id_contrato       = p.id_contrato
        JOIN cotizaciones co     ON co.id_cotizacion    = c.id_cotizacion
        JOIN clientes cl         ON cl.id_cliente       = co.id_cliente
        WHERE p.id_proyecto = ? AND co.id_cliente = ?
    ");
    $sd->execute([$id_proy, $id_cliente]);
    $proyecto_detalle = $sd->fetch();

    if ($proyecto_detalle) {
        $se = $pdo->prepare("
            SELECT nombre, descripcion,
                   porcentaje_avance, estado,
                   fecha_inicio, fecha_fin
            FROM etapas_proyecto
            WHERE id_proyecto = ?
            ORDER BY fecha_inicio ASC
        ");
        $se->execute([$id_proy]);
        $etapas_detalle = $se->fetchAll();
    }
}

function diasR($f) {
    $d = (new DateTime(date('Y-m-d')))->diff(new DateTime($f));
    return $d->invert ? -$d->days : $d->days;
}
?>
<?php require_once '../modules/layouts/header_cliente.php'; ?>

<style>
.fade-up {
    opacity: 0;
    transform: translateY(20px);
    animation: fadeUp 0.5s cubic-bezier(.22,1,.36,1) forwards;
}
@keyframes fadeUp { to { opacity:1; transform:translateY(0); } }
.delay-1{animation-delay:.06s}
.delay-2{animation-delay:.12s}
.delay-3{animation-delay:.18s}

.cl-card {
    background:#fff;
    border-radius:14px;
    box-shadow:0 2px 14px rgba(0,0,0,.06);
    overflow:hidden;
    transition:box-shadow .22s,transform .22s;
    margin-bottom:16px;
}
.cl-card:hover {
    box-shadow:0 6px 24px rgba(0,0,0,.09);
    transform:translateY(-2px);
}
.cl-card-head {
    padding:16px 20px;
    border-bottom:1px solid #F0F2F4;
    display:flex;
    align-items:center;
    gap:10px;
    font-size:14px;
    font-weight:700;
    color:#1C1C1E;
}
.proy-card {
    background:#fff;
    border-radius:14px;
    box-shadow:0 2px 14px rgba(0,0,0,.06);
    padding:20px;
    margin-bottom:16px;
    border-left:4px solid #27AE60;
    transition:box-shadow .22s,transform .22s;
    cursor:pointer;
    text-decoration:none;
    display:block;
    color:inherit;
}
.proy-card:hover {
    box-shadow:0 6px 24px rgba(0,0,0,.10);
    transform:translateY(-2px);
    color:inherit;
}
.proy-card.selected {
    border-left-color:#3498DB;
    box-shadow:0 0 0 2px #3498DB33,0 6px 24px rgba(0,0,0,.10);
}
.prog-wrap {
    background:#E8ECF2;
    border-radius:99px;
    height:7px;
    overflow:hidden;
    flex:1;
}
.prog-fill {
    height:100%;
    border-radius:99px;
    width:0;
    transition:width 1.1s cubic-bezier(.22,1,.36,1);
}
.est-badge {
    font-size:10.5px;
    font-weight:700;
    padding:3px 10px;
    border-radius:99px;
    white-space:nowrap;
}

/* Timeline etapas */
.etapa-row {
    display:flex;
    gap:14px;
    padding:14px 20px;
    border-bottom:1px solid #F6F7F9;
    align-items:flex-start;
}
.etapa-row:last-child{border-bottom:none;}
.etapa-num {
    width:30px;height:30px;
    border-radius:50%;
    display:flex;align-items:center;justify-content:center;
    font-size:12px;font-weight:800;
    flex-shrink:0;
}

/* Detalle panel */
.detalle-panel {
    background:#fff;
    border-radius:14px;
    box-shadow:0 2px 14px rgba(0,0,0,.06);
    overflow:hidden;
    animation:fadeUp .4s cubic-bezier(.22,1,.36,1) both;
}

/* Modo oscuro */
[data-bs-theme="dark"] .cl-card,
[data-bs-theme="dark"] .proy-card,
[data-bs-theme="dark"] .detalle-panel {
    background:#161b27 !important;
}
[data-bs-theme="dark"] .cl-card-head { color:#e2e8f0 !important; border-bottom-color:#1e2535 !important; }
[data-bs-theme="dark"] .etapa-row    { border-bottom-color:#1a1f2e !important; }
[data-bs-theme="dark"] .prog-wrap    { background:#1e2535 !important; }
</style>

<!-- Encabezado -->
<div class="fade-up mb-4">
    <h1 style="font-size:21px;font-weight:800;color:#1C1C1E;">Mis proyectos</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0" style="font-size:13px;margin-top:4px;">
            <li class="breadcrumb-item">
                <a href="index.php" style="color:#27AE60;">Inicio</a>
            </li>
            <li class="breadcrumb-item active">Mis proyectos</li>
        </ol>
    </nav>
</div>

<?php if ($proyecto_detalle): ?>
<!-- ══ DETALLE DE UN PROYECTO ══ -->
<?php
    $avance = round($proyecto_detalle['avance'] ?? 0);
    $dias   = diasR($proyecto_detalle['fecha_fin_estimada']);
    $est    = $proyecto_detalle['estado'];
    $pc = match($est){
        'ejecucion'=>'#27AE60','planificacion'=>'#3498DB',
        'finalizado'=>'#95A5A6',default=>'#95A5A6'
    };
?>
<div class="fade-up delay-1 mb-3">
    <a href="mis_proyectos.php"
       style="font-size:13px;color:#27AE60;font-weight:600;
              text-decoration:none;display:inline-flex;align-items:center;gap:5px;">
        <i class="bi bi-arrow-left"></i> Volver a proyectos
    </a>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-4 fade-up delay-1">
        <div class="detalle-panel">
            <div style="background:linear-gradient(135deg,#1a3a2a,#0d2018);
                        padding:24px 22px;">
                <div style="font-size:11px;font-weight:700;color:rgba(255,255,255,.45);
                            letter-spacing:1px;text-transform:uppercase;margin-bottom:8px;">
                    <?= htmlspecialchars($proyecto_detalle['tipo']) ?>
                </div>
                <div style="font-size:17px;font-weight:800;color:#fff;margin-bottom:16px;
                            line-height:1.3;">
                    <?= htmlspecialchars($proyecto_detalle['nombre']) ?>
                </div>

                <!-- Progreso circular simulado con barra -->
                <div style="background:rgba(255,255,255,.1);border-radius:99px;
                            height:8px;overflow:hidden;margin-bottom:8px;">
                    <div style="height:100%;border-radius:99px;
                                background:linear-gradient(90deg,#27AE60,#2ECC71);
                                width:<?= $avance ?>%;
                                transition:width 1.1s cubic-bezier(.22,1,.36,1);">
                    </div>
                </div>
                <div style="display:flex;justify-content:space-between;
                            color:rgba(255,255,255,.6);font-size:12px;">
                    <span>Avance general</span>
                    <span style="color:#fff;font-weight:700;"><?= $avance ?>%</span>
                </div>
            </div>

            <div style="padding:18px 20px;">
                <?php
                $items = [
                    ['Estado',        ucfirst($est)],
                    ['Ubicación',     $proyecto_detalle['ubicacion']],
                    ['Inicio',        date('d/m/Y', strtotime($proyecto_detalle['fecha_inicio']))],
                    ['Fin estimado',  date('d/m/Y', strtotime($proyecto_detalle['fecha_fin_estimada']))],
                ];
                foreach ($items as $it):
                ?>
                <div style="display:flex;justify-content:space-between;
                            padding:9px 0;border-bottom:1px solid #F0F2F4;
                            font-size:13px;">
                    <span style="color:#95A5A6;font-weight:600;"><?= $it[0] ?></span>
                    <span style="font-weight:700;color:#1C1C1E;text-align:right;
                                 max-width:60%;">
                        <?= htmlspecialchars($it[1]) ?>
                    </span>
                </div>
                <?php endforeach; ?>

                <div style="margin-top:12px;padding:10px 14px;
                            border-radius:10px;
                            background:<?= $dias < 0 ? '#FDECEA' : '#D5F5E3' ?>;
                            text-align:center;">
                    <span style="font-size:13px;font-weight:700;
                                 color:<?= $dias < 0 ? '#E74C3C' : '#1E8449' ?>;">
                        <?php if ($dias < 0): ?>
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            Vencido hace <?= abs($dias) ?> días
                        <?php elseif ($dias === 0): ?>
                            Vence hoy
                        <?php else: ?>
                            <i class="bi bi-clock me-1"></i>
                            <?= $dias ?> días restantes
                        <?php endif; ?>
                    </span>
                </div>

                <?php if ($proyecto_detalle['descripcion']): ?>
                <div style="margin-top:14px;font-size:13px;color:#7F8C8D;
                            line-height:1.6;">
                    <?= htmlspecialchars($proyecto_detalle['descripcion']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Etapas -->
    <div class="col-12 col-lg-8 fade-up delay-2">
        <div class="detalle-panel">
            <div class="cl-card-head">
                <i class="bi bi-layers-half" style="color:#27AE60;"></i>
                Etapas del proyecto
                <span style="margin-left:auto;font-size:11px;font-weight:700;
                             background:#D5F5E3;color:#1E8449;
                             padding:3px 10px;border-radius:99px;">
                    <?= count($etapas_detalle) ?> etapas
                </span>
            </div>

            <?php if ($etapas_detalle): ?>
                <?php foreach ($etapas_detalle as $i => $etapa):
                    $ec = match($etapa['estado']){
                        'finalizado'   => ['#D5F5E3','#1E8449','#27AE60','bi-check-circle-fill'],
                        'ejecucion'    => ['#D1ECF1','#0C5460','#3498DB','bi-hammer'],
                        'planificacion'=> ['#FFF3CD','#856404','#F39C12','bi-clock'],
                        default        => ['#F4F6F9','#555','#95A5A6','bi-dash-circle'],
                    };
                ?>
                <div class="etapa-row">
                    <div class="etapa-num"
                         style="background:<?= $ec[0] ?>;color:<?= $ec[1] ?>;">
                        <?= $i + 1 ?>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;justify-content:space-between;
                                    align-items:flex-start;gap:8px;margin-bottom:4px;">
                            <div>
                                <div style="font-size:13.5px;font-weight:700;color:#1C1C1E;">
                                    <?= htmlspecialchars($etapa['nombre']) ?>
                                </div>
                                <div style="font-size:11.5px;color:#95A5A6;margin-top:1px;">
                                    <?= htmlspecialchars($etapa['descripcion']) ?>
                                </div>
                            </div>
                            <span class="est-badge"
                                  style="background:<?= $ec[0] ?>;color:<?= $ec[1] ?>;">
                                <i class="bi <?= $ec[3] ?> me-1"></i>
                                <?= ucfirst($etapa['estado']) ?>
                            </span>
                        </div>

                        <div style="display:flex;align-items:center;gap:8px;margin-top:8px;">
                            <div class="prog-wrap">
                                <div class="prog-fill"
                                     data-width="<?= $etapa['porcentaje_avance'] ?>"
                                     style="background:<?= $ec[2] ?>;"></div>
                            </div>
                            <span style="font-size:11.5px;font-weight:700;
                                         color:<?= $ec[2] ?>;min-width:30px;text-align:right;">
                                <?= $etapa['porcentaje_avance'] ?>%
                            </span>
                        </div>

                        <?php if ($etapa['fecha_inicio']): ?>
                        <div style="font-size:11px;color:#BDC3C7;margin-top:5px;">
                            <?= date('d/m/Y', strtotime($etapa['fecha_inicio'])) ?>
                            <?= $etapa['fecha_fin']
                                ? ' → ' . date('d/m/Y', strtotime($etapa['fecha_fin']))
                                : '' ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

            <?php else: ?>
                <div style="padding:30px;text-align:center;color:#95A5A6;font-size:13.5px;">
                    Sin etapas registradas aún.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ══ LISTA DE PROYECTOS ══ -->
<?php if ($proyectos): ?>
    <div class="row g-3">
        <?php foreach ($proyectos as $i => $p):
            $avance = round($p['avance']);
            $dias   = diasR($p['fecha_fin_estimada']);
            $est    = $p['estado'];
            $est_bg    = match($est){
                'ejecucion'=>'#D4EDDA','planificacion'=>'#D1ECF1',
                'finalizado'=>'#D6D8D9',default=>'#F4F6F9'
            };
            $est_color = match($est){
                'ejecucion'=>'#155724','planificacion'=>'#0C5460',
                'finalizado'=>'#383D41',default=>'#555'
            };
            $est_lbl = match($est){
                'ejecucion'=>'En ejecución','planificacion'=>'Planificación',
                'finalizado'=>'Finalizado',default=>ucfirst($est)
            };
            $pc = match($est){
                'ejecucion'=>($avance>=70?'#27AE60':($avance>=30?'#3498DB':'#E74C3C')),
                'planificacion'=>'#95A5A6','finalizado'=>'#27AE60',default=>'#95A5A6'
            };
        ?>
        <div class="col-12 col-md-6 fade-up"
             style="animation-delay:<?= $i * 0.07 ?>s;">
            <a href="mis_proyectos.php?id=<?= $p['id_proyecto'] ?>"
               class="proy-card"
               style="border-left-color:<?= $pc ?>;">
                <div style="display:flex;justify-content:space-between;
                            align-items:flex-start;gap:8px;margin-bottom:10px;">
                    <div>
                        <div style="font-size:14px;font-weight:800;color:#1C1C1E;
                                    margin-bottom:3px;">
                            <?= htmlspecialchars($p['nombre']) ?>
                        </div>
                        <div style="font-size:11.5px;color:#95A5A6;">
                            <?= htmlspecialchars($p['tipo']) ?>
                        </div>
                    </div>
                    <span class="est-badge"
                          style="background:<?= $est_bg ?>;color:<?= $est_color ?>;">
                        <?= $est_lbl ?>
                    </span>
                </div>

                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                    <div class="prog-wrap">
                        <div class="prog-fill"
                             data-width="<?= $avance ?>"
                             style="background:<?= $pc ?>;"></div>
                    </div>
                    <span style="font-size:12px;font-weight:700;
                                 color:#2C3E50;min-width:34px;text-align:right;">
                        <?= $avance ?>%
                    </span>
                </div>

                <div style="display:flex;justify-content:space-between;
                            font-size:11.5px;color:#95A5A6;">
                    <span>
                        <i class="bi bi-layers me-1"></i>
                        <?= $p['total_etapas'] ?> etapas
                    </span>
                    <span style="color:<?= $dias<0?'#E74C3C':($dias<30?'#E67E22':'#95A5A6') ?>;">
                        <?php if ($dias<0): ?>
                            Vencido (<?=abs($dias)?>d)
                        <?php elseif($dias===0): ?>
                            Vence hoy
                        <?php else: ?>
                            <?=$dias?>d restantes
                        <?php endif; ?>
                    </span>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

<?php else: ?>
    <div class="cl-card fade-up delay-1">
        <div style="padding:48px;text-align:center;color:#95A5A6;">
            <i class="bi bi-building"
               style="font-size:40px;display:block;margin-bottom:14px;opacity:0.3;"></i>
            <div style="font-size:15px;font-weight:700;margin-bottom:6px;">
                Sin proyectos registrados
            </div>
            <div style="font-size:13px;">
                Cuando la constructora asigne un proyecto a tu cuenta,
                aparecerá aquí.
            </div>
        </div>
    </div>
<?php endif; ?>
<?php endif; ?>

<script>
window.addEventListener('load', function() {
    document.querySelectorAll('.prog-fill[data-width]').forEach(function(bar) {
        setTimeout(function() { bar.style.width = bar.dataset.width + '%'; }, 250);
    });
});
</script>

<?php require_once '../modules/layouts/footer_cliente.php'; ?>