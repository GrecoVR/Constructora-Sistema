<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
$pdo   = conectar();
$permisos = $_SESSION['permisos'];
$q = trim($_POST['q'] ?? '');

$stmt = $pdo->prepare("
    SELECT *
    FROM menus_sistema
    WHERE nombre LIKE ?
");

$stmt->execute(["%$q%"]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php require_once '../../modules/layouts/header.php'; ?>

    <h2>Resultados de busqueda</h2>

    <p>
        Resultados para:
        <strong><?= htmlspecialchars($q) ?></strong>
    </p>

    <?php if(count($results)): ?>

        <ul class="list-group">

            <?php foreach($results as $row): ?>

                <li class="list-group-item">
                    <a class="nav-link" href="<?= htmlspecialchars($row['enlace']) ?>"><?= htmlspecialchars($row['nombre']) ?>
                </li>

            <?php endforeach; ?>

        </ul>

    <?php else: ?>

        <div class="alert alert-warning">
            No se encontraron resultados.
        </div>

    <?php endif; ?>

<?php require_once '../../modules/layouts/footer.php'; ?>