<?php
require_once '../config/session.php';
require_once '../middleware/auth_cliente.php';
require_once '../config/database.php';

$pdo        = conectar();
$id_cliente = $_SESSION['id_cliente'];

$stmt = $pdo->prepare("
    SELECT id_notificacion, titulo, contenido
    FROM notificaciones_clientes
    WHERE id_cliente = ?
    ORDER BY id_notificacion DESC
");
$stmt->execute([$id_cliente]);
$notifs = $stmt->fetchAll();
?>
<?php require_once '../modules/layouts/header_cliente.php'; ?>

<style>
.fade-up {
    opacity:0; transform:translateY(20px);
    animation:fadeUp .5s cubic-bezier(.22,1,.36,1) forwards;
}
@keyframes fadeUp { to{opacity:1;transform:translateY(0);} }
.delay-1{animation-delay:.06s}
.delay-2{animation-delay:.12s}

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
.notif-item {
    display:flex;
    gap:14px;
    padding:16px 20px;
    border-bottom:1px solid #F6F7F9;
    align-items:flex-start;
    transition:background .15s;
}
.notif-item:last-child { border-bottom:none; }
.notif-item:hover      { background:#F8FBF8; }
.notif-icon {
    width:38px; height:38px;
    border-radius:50%;
    background:linear-gradient(135deg,#D5F5E3,#A9DFBF);
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0;
    font-size:17px; color:#1E8449;
}

[data-bs-theme="dark"] .cl-card { background:#161b27 !important; }
[data-bs-theme="dark"] .cl-card-head { color:#e2e8f0 !important; border-bottom-color:#1e2535 !important; }
[data-bs-theme="dark"] .notif-item { border-bottom-color:#1a1f2e !important; }
[data-bs-theme="dark"] .notif-item:hover { background:#1a1f2e !important; }
[data-bs-theme="dark"] .notif-icon { background:linear-gradient(135deg,#1a3a2a,#0d2018) !important; }
</style>

<!-- Encabezado -->
<div class="fade-up mb-4">
    <h1 style="font-size:21px;font-weight:800;color:#1C1C1E;">
        Mis notificaciones
    </h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0" style="font-size:13px;margin-top:4px;">
            <li class="breadcrumb-item">
                <a href="index.php" style="color:#27AE60;">Inicio</a>
            </li>
            <li class="breadcrumb-item active">Notificaciones</li>
        </ol>
    </nav>
</div>

<div class="cl-card fade-up delay-1">
    <div class="cl-card-head">
        <i class="bi bi-bell-fill" style="color:#27AE60;"></i>
        Todas las notificaciones
        <span style="margin-left:auto;font-size:11px;font-weight:700;
                     background:#D5F5E3;color:#1E8449;
                     padding:3px 10px;border-radius:99px;">
            <?= count($notifs) ?>
        </span>
    </div>

    <?php if ($notifs): ?>

        <!-- Buscador manual tiempo real -->
        <div style="padding:14px 20px;border-bottom:1px solid #F0F2F4;">
            <div style="position:relative;max-width:340px;">
                <i class="bi bi-search"
                   style="position:absolute;left:12px;top:50%;
                          transform:translateY(-50%);color:#95A5A6;font-size:13px;"></i>
                <input type="text"
                       id="notif-search"
                       placeholder="Buscar notificación..."
                       style="width:100%;padding:8px 12px 8px 34px;
                              border:1.5px solid #E8ECF0;border-radius:9px;
                              font-size:13px;background:#F8F9FA;
                              transition:border-color .18s;"
                       oninput="filtrarNotifs(this.value)"
                       onfocus="this.style.borderColor='#27AE60'"
                       onblur="this.style.borderColor='#E8ECF0'">
            </div>
        </div>

        <div id="notif-list">
            <?php foreach ($notifs as $i => $n): ?>
            <div class="notif-item"
                 data-titulo="<?= htmlspecialchars(strtolower($n['titulo'])) ?>"
                 data-contenido="<?= htmlspecialchars(strtolower($n['contenido'])) ?>"
                 style="animation:fadeUp .4s cubic-bezier(.22,1,.36,1) <?= $i*0.05 ?>s both;">
                <div class="notif-icon">
                    <i class="bi bi-bell"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13.5px;font-weight:700;
                                color:#1C1C1E;margin-bottom:4px;">
                        <?= htmlspecialchars($n['titulo']) ?>
                    </div>
                    <div style="font-size:13px;color:#7F8C8D;line-height:1.55;">
                        <?= htmlspecialchars($n['contenido']) ?>
                    </div>
                </div>
                <div style="width:8px;height:8px;border-radius:50%;
                            background:#27AE60;flex-shrink:0;margin-top:5px;
                            box-shadow:0 0 0 3px rgba(39,174,96,.2);"></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Sin resultados -->
        <div id="notif-empty"
             style="display:none;padding:32px;text-align:center;color:#95A5A6;font-size:13.5px;">
            <i class="bi bi-search"
               style="font-size:26px;display:block;margin-bottom:8px;opacity:.4;"></i>
            No se encontraron notificaciones.
        </div>

    <?php else: ?>
        <div style="padding:48px;text-align:center;color:#95A5A6;">
            <i class="bi bi-bell-slash"
               style="font-size:40px;display:block;margin-bottom:14px;opacity:.3;"></i>
            <div style="font-size:15px;font-weight:700;margin-bottom:6px;">
                Sin notificaciones
            </div>
            <div style="font-size:13px;">
                Cuando la constructora te envíe un mensaje, aparecerá aquí.
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function filtrarNotifs(query) {
    const q     = query.toLowerCase().trim();
    const items = document.querySelectorAll('#notif-list .notif-item');
    let   vis   = 0;

    items.forEach(function(item) {
        const titulo    = item.dataset.titulo    || '';
        const contenido = item.dataset.contenido || '';
        const match     = !q || titulo.includes(q) || contenido.includes(q);
        item.style.display = match ? '' : 'none';
        if (match) vis++;
    });

    const empty = document.getElementById('notif-empty');
    if (empty) empty.style.display = vis === 0 ? 'block' : 'none';
}
</script>

<?php require_once '../modules/layouts/footer_cliente.php'; ?>