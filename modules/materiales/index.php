<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('gestionar_materiales');
registrarAccion(LOG_VER_MATERIALES);

$pdo  = conectar();

$permisos = $_SESSION['permisos'];

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_material'];
    $nombre          = trim($_POST['nombre'] ?? '');
    $descripcion     = trim($_POST['descripcion'] ?? '');
    $precio          = floatval($_POST['precio'] ?? 0);
    $id_tipo         = intval($_POST['id_tipo_material'] ?? 0);
    $id_unidad       = intval($_POST['id_unidad_medida'] ?? 0);

    //crear
    if ($_POST['action'] == 'create') {

      if ($nombre && $precio && $id_tipo && $id_unidad) {
          $stmt = $pdo->prepare("
              INSERT INTO materiales (id_tipo_material, id_unidad_medida, nombre, descripcion, precio_unitario_base)
              VALUES (?, ?, ?, ?, ?)
          ");
          $stmt->execute([$id_tipo, $id_unidad, $nombre, $descripcion, $precio]);
          registrarAccion(LOG_CREAR_MATERIAL . ' — "' . $nombre . '"');
          $exito = 'Material creado correctamente';
      } else {
          $error = 'Completa todos los campos obligatorios';
      }

    }
    //actualizar datos
   if ($_POST['action'] == 'update') {

     if ($nombre && $precio && $id_tipo && $id_unidad) {
        $stmt2 = $pdo->prepare("
            UPDATE materiales
            SET nombre = ?, descripcion = ?, precio_unitario_base = ?,
                id_tipo_material = ?, id_unidad_medida = ?
            WHERE id_material = ?
        ");
        $stmt2->execute([$nombre, $descripcion, $precio, $id_tipo, $id_unidad, $id]);
        registrarAccion(LOG_EDITAR_MATERIAL . ' — ID:' . $id);
        $exito = 'Material actualizado correctamente';

     } else {
          $error = 'Completa todos los campos obligatorios';
     }
   }

   // Eliminar
   if ($_POST['action'] == 'delete') {
     $stmt = $pdo->prepare("DELETE FROM materiales WHERE id_material = ?");
     $result = $stmt->execute([$_POST['id_material']]);
   }
}


$stmt = $pdo->query("
    SELECT m.id_material, m.nombre, m.descripcion, m.precio_unitario_base,
           tm.nombre as tipo, um.descripcion as unidad
    FROM materiales m
    JOIN tipos_materiales tm ON tm.id_tipo_material = m.id_tipo_material
    JOIN unidades_medida um ON um.id_unidad_medida = m.id_unidad_medida
    ORDER BY m.id_material DESC
");

$materiales = $stmt->fetchAll();

$tipos   = $pdo->query("SELECT * FROM tipos_materiales ORDER BY nombre ASC")->fetchAll();
$unidades = $pdo->query("SELECT * FROM unidades_medida ORDER BY descripcion ASC")->fetchAll();
?>

<?php require_once '../../modules/layouts/header.php'; ?>


<nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="../../modules/dashboard/dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Materiales</li>
  </ol>
</nav>

<h2 class="mb-4 fw-semibold">🧱 Materiales</h2>

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

  <div class="card shadow mt-2">
      <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="mb-0">Lista de Materiales</h4>
          <button type="button" class="btn btn-primary" id="addRowBtn"><i class="bi bi-plus-lg"> <a href="crear.php" class="text-white text-decoration-none">Nuevo Material</a></i></button>
      </div>
      <div class="card-body table-responsive">
      <table id="tabla-datos" class="table table-striped table-bordered">
        <thead>
          <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Descripcion</th>
              <th>Tipo</th>
              <th>Unidad</th>
              <th>Precio_(Bs)</th>
              <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($materiales as $m): ?>
              <tr>
                  <td><?= $m['id_material'] ?></td>
                  <td><?= htmlspecialchars($m['nombre']) ?></td>
                  <td><?= htmlspecialchars($m['descripcion']) ?></td>
                  <td><?= htmlspecialchars($m['tipo']) ?></td>
                  <td><?= $m['unidad'] ?></td>
                  <td><?= number_format($m['precio_unitario_base'], 2) ?></td>
                  <td>
                      <button class="btn btn-sm btn-outline-secondary border-0 fw-semibold editBtn" data-id="<?= $m['id_material'] ?>">
                        <i class="bi bi-pencil-square"></i> Editar</button>
                     <!--<button class="btn btn-sm btn-outline-danger deleteBtn" data-id="<?= $m['id_material'] ?>">
                     Eliminar</button>-->
                      <a class="btn btn-outline-success btn-sm border-0 fw-semibold" href="movimientos.php?id_material=<?= $m['id_material'] ?>">
                       <i class="bi bi-eye-fill"></i> Ver stock</a>
                  </td>
              </tr>
          <?php endforeach; ?>
         </tbody>
      </table>
      </div>
  </div>

  <!--  Modal (Handles both Create and Update) -->
<form method="POST" id="dataForm">
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">Crear Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="id_material" id="id_material">
              <input type="hidden" name="action" id="action" value="create">
              <div class="mb-3">
            <label class="form-label" for="nombre">Nombre: *</label>
            <input class="form-control" type="text" id="nombre" name="nombre" required>
            </div>
            <div class="mb-3">
            <label class="form-label" for="descripcion">Descripción:</label>
            <textarea class="form-control" id="descripcion" name="descripcion" rows="3" cols="40"></textarea>
            </div>
            <div class="mb-3">
            <label class="form-label" for="id_tipo_material">Tipo de material: *</label>
            <select class="form-select" id="id_tipo_material" name="id_tipo_material" required>
                <option value="">-- Selecciona --</option>
                <?php foreach ($tipos as $t): ?>
                    <option value="<?= $t['id_tipo_material'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            </div>
            <div class="mb-3">
            <label class="form-label" for="id_unidad_medida">Unidad de medida: *</label>
            <select class="form-select" id="id_unidad_medida" name="id_unidad_medida" required>
                <option value="">-- Selecciona --</option>
                <?php foreach ($unidades as $u): ?>
                    <option value="<?= $u['id_unidad_medida'] ?>">
                        <?= htmlspecialchars($u['descripcion']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            </div>
            <div class="mb-3">
            <label class="form-label" for="precio">Precio unitario base (Bs): *</label>
            <input class="form-control" type="number" id="precio" name="precio" step="0.01" min="0" required>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type="submit">Guardar</button>
            </div>
        </div>
     </div>
   </div>
</div><!-- end modal -->
</form>

<?php if (empty($materiales)): ?>
    <p>No se encontraron materiales.</p>
<?php endif; ?>

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

    // Open Modal for Adding row
    $('#addRowBtn').click(function() {
        $('#dataForm')[0].reset();
        $('.modal-title').text('Crear Material');
        $('#action').val('create');
        $('#userModal').modal('show');
    });

    // Handle Edit Button Click
    $(document).on('click', '.editBtn', function() {
        var id = $(this).data('id');
        $('#id_material').val(id);

        //Get the row data
        var data = table.row($(this).parents('tr')).data();
        console.log(data);
        // Map data to Modal fields (using IDs of input elements)
        $('#nombre').val(data[1]);
        $('#descripcion').val(data[2]);

        var idTipoMaterial = $("#id_tipo_material option").filter(function() {
            return $(this).text().trim() === data[3];
        }).val();
        $('#id_tipo_material').val(idTipoMaterial);

        var idUnidadMedida = $("#id_unidad_medida option").filter(function() {
            return $(this).text().trim() === data[4];
        }).val();
        $('#id_unidad_medida').val(idUnidadMedida);

        var precioFloat = parseFloat(data[5].replaceAll(',', ''));
        $('#precio').val(precioFloat);

        $('.modal-title').text('Editar Material');
        $('#action').val('update');
        $('#userModal').modal('show');

    });

    // Handle Delete Button Click
    $(document).on('click', '.deleteBtn', function() {
        var id = $(this).data('id');
        $('#id_material').val(id);
        if(confirm("Estas seguro que deseas eliminar este material?")) {
            $('#action').val('delete');
            $('#dataForm').submit();
        }
    });
});
</script>
<?php require_once '../../modules/layouts/footer.php'; ?>