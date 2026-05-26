<?php
date_default_timezone_set('America/La_Paz');

require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
require_once '../../triggers/TriggerManager.php';

requierePermiso('registrar_movimientos');

$pdo   = conectar();
$error = '';
$exito = '';

// Filtro por material específico si viene desde index
$id_material_filtro = intval($_GET['id_material'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action'])) {
    $id_material     = intval($_POST['id_material'] ?? 0);
    $id_almacen      = intval($_POST['id_almacen'] ?? 0);
    $tipo_movimiento = $_POST['tipo_movimiento'] ?? '';
    $cantidad        = floatval($_POST['cantidad'] ?? 0);
    $fecha           = $_POST['fecha'] ?? date('Y-m-d');
  //crear
  if ($_POST['action'] == 'create') {
    if ($id_material && $id_almacen && $tipo_movimiento && $cantidad > 0) {

        // Para salidas verifica que haya stock suficiente
        if ($tipo_movimiento === 'salida') {
            $stmt = $pdo->prepare("
                SELECT stock FROM inventarios
                WHERE id_material = ? AND id_almacen = ?
            ");
            $stmt->execute([$id_material, $id_almacen]);
            $inv = $stmt->fetch();

            if (!$inv || $inv['stock'] < $cantidad) {
                $error = 'Stock insuficiente para registrar esta salida';
            }
        }

        if (!$error) {
            // Registra el movimiento
            $stmt = $pdo->prepare("
                INSERT INTO movimientos_inventario 
                (id_material, id_almacen, tipo_movimiento, fecha, cantidad)
                VALUES (?, ?, ?, ?, ?)
            ");
            $cantidad_real = $tipo_movimiento === 'salida' ? -$cantidad : $cantidad;
            $stmt->execute([$id_material, $id_almacen, $tipo_movimiento, $fecha, $cantidad_real]);

            // Dispara el trigger correspondiente
            $manager = new TriggerManager($pdo);
            $datos   = [
                'id_material' => $id_material,
                'id_almacen'  => $id_almacen,
                'cantidad'    => $cantidad,
                'id_usuario'  => $_SESSION['id_usuario']
            ];

            if ($tipo_movimiento === 'entrada') {
                $manager->ejecutar('inventario.entrada', $datos);
            } elseif ($tipo_movimiento === 'salida') {
                $manager->ejecutar('inventario.salida', $datos);
            } elseif ($tipo_movimiento === 'ajuste') {
                $datos['cantidad'] = $cantidad_real;
                $manager->ejecutar('inventario.ajuste', $datos);
            }

            registrarAccion("Registró movimiento $tipo_movimiento — material ID: $id_material");
            $exito = 'Movimiento registrado correctamente';
        }
    } else {
        $error = 'Completa todos los campos';
    }
   }
   
   //actualizar datos
   if ($_POST['action'] == 'update') {
    if ($id_material && $id_almacen && $tipo_movimiento && $cantidad > 0) {

        // Para salidas verifica que haya stock suficiente
        if ($tipo_movimiento === 'salida') {
            $stmt = $pdo->prepare("
                SELECT stock FROM inventarios
                WHERE id_material = ? AND id_almacen = ?
            ");
            $stmt->execute([$id_material, $id_almacen]);
            $inv = $stmt->fetch();

            if (!$inv || $inv['stock'] < $cantidad) {
                $error = 'Stock insuficiente para registrar esta salida';
            }
        }

        if (!$error) {
            // Actualiza el movimiento
            $stmt = $pdo->prepare("
                UPDATE movimientos_inventario SET 
                id_material = ?, id_almacen = ?, tipo_movimiento = ? , fecha = ? , cantidad = ? 
                WHERE id_movimiento = ?
            ");
            $cantidad_real = $tipo_movimiento === 'salida' ? -$cantidad : $cantidad;
            $result = $stmt->execute([$id_material, $id_almacen, $tipo_movimiento, $fecha, $cantidad_real, $_POST['id_movimiento']]);

            // Dispara el trigger correspondiente
            $manager = new TriggerManager($pdo);
            $datos   = [
                'id_material' => $id_material,
                'id_almacen'  => $id_almacen,
                'cantidad'    => $cantidad,
                'id_usuario'  => $_SESSION['id_usuario']
            ];

            if ($tipo_movimiento === 'entrada') {
                $manager->ejecutar('inventario.entrada', $datos);
            } elseif ($tipo_movimiento === 'salida') {
                $manager->ejecutar('inventario.salida', $datos);
            } elseif ($tipo_movimiento === 'ajuste') {
                $datos['cantidad'] = $cantidad_real;
                $manager->ejecutar('inventario.ajuste', $datos);
            }

            registrarAccion("Actualizo movimiento $tipo_movimiento — material ID: $id_material");
            $exito = 'Movimiento Actualizado correctamente';
        }
    } else {
        $error = 'Completa todos los campos';
    }
   }
   // Eliminar
   if ($_POST['action'] == 'delete') {
     $stmt = $pdo->prepare("DELETE FROM movimientos_inventario WHERE id_movimiento = ?");
     $result = $stmt->execute([$_POST['id_movimiento']]);
   }
   
  }
}

$materiales = $pdo->query("SELECT id_material, nombre FROM materiales ORDER BY nombre ASC")->fetchAll();
$almacenes  = $pdo->query("SELECT id_almacen, nombre FROM almacenes ORDER BY nombre ASC")->fetchAll();

// Historial de movimientos
$historial_stmt = $pdo->query("
    SELECT mi.id_movimiento, mi.fecha, mi.tipo_movimiento, mi.cantidad,
           m.nombre as material, a.nombre as almacen
    FROM movimientos_inventario mi
    JOIN materiales m ON m.id_material = mi.id_material
    JOIN almacenes a ON a.id_almacen = mi.id_almacen
    ORDER BY mi.id_movimiento DESC
    LIMIT 20
");
$historial = $historial_stmt->fetchAll();
?>

<?php require_once '../../modules/layouts/header.php'; ?>

<div class="p-4">

<nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="../../modules/dashboard/dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Movimientos</li>
  </ol>
</nav>

<h2 class="mb-4">🔄 Movimientos de Inventario</h2>

<a class="btn btn-secondary mb-3" href="index.php">Ver materiales</a>


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
          <h4 class="mb-0">Últimos 20 movimientos</h4>
          <button type="button" class="btn btn-primary btn-sm" id="addRowBtn"><i class="bi bi-plus-lg"></i> Registrar Movimiento</button>
      </div> 
      <div class="card-body table-responsive">
      <table id="tabla-datos" class="table table-striped table-bordered">
        <thead>
          <tr>
              <th>Fecha</th>
              <th>Material</th>
              <th>Almacén</th>
              <th>Tipo</th>
              <th>Cantidad</th>
              <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($historial as $h): ?>
              <tr>
                  <td><?= $h['fecha'] ?></td>
                  <td><?= htmlspecialchars($h['material']) ?></td>
                  <td><?= htmlspecialchars($h['almacen']) ?></td>
                  <td><?= $h['tipo_movimiento'] ?></td>
                  <td><?= $h['cantidad'] ?></td>
                  <td>
                  <button class="btn btn-sm btn-outline-secondary editBtn" data-id="<?= $h['id_movimiento'] ?>">
                     Editar</button>
                  <button class="btn btn-sm btn-outline-danger deleteBtn" data-id="<?= $h['id_movimiento'] ?>">
                     Eliminar</button>
                  </td>
              </tr>
          <?php endforeach; ?>
         </tbody>
      </table>
      </div>
  </div> <!-- end card >
 

<!--  Modal (Handles both Create and Update) -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <form method="POST" id="dataForm">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">Registrar Movimiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="id_movimiento" id="id_movimiento">
              <input type="hidden" name="action" id="action" value="create">
              <div class="mb-3">
                <label for="fecha" class="form-label">Fecha: *</label>
                <input class="form-control" type="date" id="fecha" name="fecha" value="<?= date('Y-m-d') ?>" required>
              </div>
              <div class="mt-3 mb-3">
                <label for="id_material" class="form-label">Material: *</label>
                <select class="form-select" id="id_material" name="id_material" required>
                    <option value="">-- Selecciona --</option>
                    <?php foreach ($materiales as $m): ?>
                        <option value="<?= $m['id_material'] ?>"
                            <?= $m['id_material'] == $id_material_filtro ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label for="id_almacen" class="form-label">Almacén: *</label>
                <select class="form-select" id="id_almacen" name="id_almacen"  required>
                    <option value="">-- Selecciona --</option>
                    <?php foreach ($almacenes as $a): ?>
                        <option value="<?= $a['id_almacen'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label for="tipo_movimiento" class="form-label">Tipo de movimiento: *</label>
                <select  class="form-select" id="tipo_movimiento" name="tipo_movimiento" required>
                    <option value="">-- Selecciona --</option>
                    <option value="entrada">Entrada</option>
                    <option value="salida">Salida</option>
                    <option value="ajuste">Ajuste</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="cantidad" class="form-label">Cantidad: *</label>
                <input class="form-control" type="number" id="cantidad" name="cantidad" step="0.01" min="0.01" required>
              </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type="submit">Registrar</button>
            </div>
        </div>
        </form>
   </div>
</div><!-- end modal -->

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
    
    // Open Modal for Adding row
    $('#addRowBtn').click(function() {
        $('#dataForm')[0].reset();
        $('.modal-title').text('Registrar Movimiento');
        $('#action').val('create');
        $('#userModal').modal('show');
    });
    
    
    // Handle Edit Button Click
    $(document).on('click', '.editBtn', function() {
        var id = $(this).data('id');
        $('#id_movimiento').val(id);
        
        //Get the row data
        var data = table.row($(this).parents('tr')).data();

        // Map data to Modal fields (using IDs of input elements)
        $('#fecha').val(data[0]);

        var idMaterial = $("#id_material option").filter(function() {
            return $(this).text().trim() === data[1];
        }).val();
        
        $('#id_material').val(idMaterial);
        
        var idAlmacen = $("#id_almacen option").filter(function() {
            return $(this).text().trim() === data[2];
        }).val();

        $('#id_almacen').val(idAlmacen);
        $('#tipo_movimiento').val(data[3]);
        $('#cantidad').val(data[4]);
                
        $('.modal-title').text('Editar Movimiento');
        $('#action').val('update');
        $('#userModal').modal('show');

    });
    
    // Handle Delete Button Click
    $(document).on('click', '.deleteBtn', function() {
        var id = $(this).data('id');
        $('#id_movimiento').val(id);
        if(confirm("Estas seguro que deseas eliminar este movimiento?")) {
            $('#action').val('delete');
            $('#dataForm').submit(); 
        }
    });
});
</script>
<?php require_once '../../modules/layouts/footer.php'; ?>