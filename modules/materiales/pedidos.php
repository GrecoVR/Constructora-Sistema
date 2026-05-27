<?php
date_default_timezone_set('America/La_Paz');

require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('gestionar_pedidos');
registrarAccion('Vio pedidos');

$pdo   = conectar();
$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_proveedor = intval($_POST['id_proveedor'] ?? 0);
    $id_almacen   = intval($_POST['id_almacen'] ?? 0);
    $fecha_pedido = $_POST['fecha_pedido'] ?? date('Y-m-d');
    $materiales   = $_POST['materiales'] ?? [];
    $cantidades   = $_POST['cantidades'] ?? [];
    $precios      = $_POST['precios'] ?? [];

    if ($id_proveedor && $id_almacen && !empty($materiales)) {
        // Crea el pedido
        $stmt = $pdo->prepare("
            INSERT INTO pedidos (id_proveedor, id_almacen, fecha_pedido, estado)
            VALUES (?, ?, ?, 'pendiente')
        ");
        $stmt->execute([$id_proveedor, $id_almacen, $fecha_pedido]);
        $id_pedido = $pdo->lastInsertId();

        // Inserta el detalle
        $stmt2 = $pdo->prepare("
            INSERT INTO detalle_pedido (id_pedido, id_material, cantidad, precio_unitario)
            VALUES (?, ?, ?, ?)
        ");
        foreach ($materiales as $i => $id_material) {
            if ($id_material && $cantidades[$i] > 0) {
                $stmt2->execute([
                    $id_pedido,
                    intval($id_material),
                    floatval($cantidades[$i]),
                    floatval($precios[$i])
                ]);
            }
        }

        registrarAccion("Creó pedido ID: $id_pedido al proveedor ID: $id_proveedor");
        $exito = "Pedido #$id_pedido creado correctamente";
    } else {
        $error = 'Completa todos los campos y agrega al menos un material';
    }
}

$proveedores = $pdo->query("SELECT id_proveedor, nombre FROM proveedores ORDER BY nombre ASC")->fetchAll();
$almacenes   = $pdo->query("SELECT id_almacen, nombre FROM almacenes ORDER BY nombre ASC")->fetchAll();
$materiales_lista = $pdo->query("SELECT id_material, nombre, precio_unitario_base FROM materiales ORDER BY nombre ASC")->fetchAll();

// Lista de pedidos existentes
$pedidos = $pdo->query("
    SELECT p.id_pedido, p.fecha_pedido, p.estado,
           pr.nombre as proveedor, a.nombre as almacen,
           COUNT(dp.id_material) as total_items
    FROM pedidos p
    JOIN proveedores pr ON pr.id_proveedor = p.id_proveedor
    JOIN almacenes a ON a.id_almacen = p.id_almacen
    LEFT JOIN detalle_pedido dp ON dp.id_pedido = p.id_pedido
    GROUP BY p.id_pedido, p.fecha_pedido, p.estado, pr.nombre, a.nombre
    ORDER BY p.fecha_pedido DESC
    LIMIT 20
")->fetchAll();
?>

<?php require_once '../../modules/layouts/header.php'; ?>

<div class="p-4">

<nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="../../modules/dashboard/dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Pedidos</li>
  </ol>
</nav>

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

<h2>🛒 Pedidos a Proveedores</h2>

<form method="POST" id="dataForm">
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">Nuevo Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="id_pedido" id="id_pedido">
              <input type="hidden" name="action" id="action" value="create">
            <div class="mb-3">
            <label class="form-label" for="id_proveedor" >Proveedor: *</label>
            <select  class="form-select" id="id_proveedor" name="id_proveedor" required>
                <option value="">-- Selecciona --</option>
                <?php foreach ($proveedores as $p): ?>
                    <option value="<?= $p['id_proveedor'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            </div>
            <div class="mb-3">
            <label class="form-label" for="id_almacen">Almacén destino: *</label>
            <select class="form-select" id="id_almacen" name="id_almacen" required>
                <option value="">-- Selecciona --</option>
                <?php foreach ($almacenes as $a): ?>
                    <option value="<?= $a['id_almacen'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            </div>
            <div>
            <label class="form-label" for="fecha_pedido">Fecha del pedido: *</label>
            <input class="form-control" type="date" id="fecha_pedido" name="fecha_pedido" value="<?= date('Y-m-d') ?>"><br><br>
            </div>
            
            <div class="d-flex gap-2 justify-content-between mb-3">
            <h5>Materiales del pedido</h5>
            <button id="addFieldBtn" class="btn btn-sm btn-success" type="button"><i class="bi bi-plus-lg"></i>Agregar Material</button>
            </div>
            <div class="d-flex flex-column gap-2 mb-3">
                <div class="d-grid gap-2 fw-semibold" style="grid-template-columns: repeat(3, 1fr);">
                    <div>Material</div>
                    <div>Cantidad</div>
                    <div>Precio unitario (Bs)</div>
                </div>
                <div id="materialsContainer" class="d-grid gap-2 mb-2" style="grid-template-columns: repeat(3, 1fr);">
                <?php for ($i = 0; $i < 5; $i++): ?>
                    <select class="form-select" id="<?= 'material_'.$i ?>" name="materiales[]">
                        <option value="">-- Opcional --</option>
                        <?php foreach ($materiales_lista as $m): ?>
                            <option value="<?= $m['id_material'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input class="form-control" id="<?= 'cantidad_'.$i ?>" type="number" name="cantidades[]" step="0.01" min="0" value="0">
                    <input class="form-control" id="<?= 'precio_'.$i ?>" type="number" name="precios[]" step="0.01" min="0" value="0">
                <?php endfor; ?>
                </div>
            </div>
          </div><!-- end modal body -->
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Guardar</button>
          </div>
       </div> 
    </div>
</div><!-- end modal -->
</form>


  <div class="card shadow mt-2">
      <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="mb-0">🧱 Pedidos Recientes</h4>
          <button type="button" class="btn btn-primary btn-sm" id="addRowBtn"><i class="bi bi-plus-lg"></i> Nuevo Pedido</button>
      </div> 
      <div class="card-body table-responsive">
      <table id="tabla-datos" class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>ID</th>
          <th>Fecha</th>
          <th>Proveedor</th>
          <th>Almacén</th>
          <th>Items</th>
          <th>Estado</th>
          <th>Acciones</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($pedidos as $p): ?>
        <tr>
            <td><?= $p['id_pedido'] ?></td>
            <td><?= $p['fecha_pedido'] ?></td>
            <td><?= htmlspecialchars($p['proveedor']) ?></td>
            <td><?= htmlspecialchars($p['almacen']) ?></td>
            <td><?= $p['total_items'] ?></td>
            <td><?= $p['estado'] ?></td>
            <td>
            <?php if ($p['estado'] == 'pendiente'): ?>
            <button class="btn btn-sm btn-outline-secondary editBtn" data-id="<?= $h['id_pedido'] ?>">
               Editar</button>
            <button class="btn btn-sm btn-outline-danger deleteBtn" data-id="<?= $h['id_pedido'] ?>">
               Eliminar</button>
            <?php endif; ?> 
            </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      </table>
      </div>
 </div>
</div>
<script>
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
    
    
  // Use a counter
  let fieldCount = 4;

  $("#addFieldBtn").click(function(e) {
    e.preventDefault(); // Prevent default button behavior    
      fieldCount++;
      // Append a new inputs
      $("#materialsContainer").append(
        '<select class="form-select" id="material_'+ fieldCount +'" name="materiales[]" ></select>' +
        '<input class="form-control" id="cantidad_' + fieldCount + '" type="number" name="cantidades[]" step="0.01" min="0" value="0">' +
         '<input class="form-control" id="precio_'+ fieldCount + '" type="number" name="precios[]" step="0.01" min="0" value="0">'
      );
      $('#material_'+ 0 +' option').clone().appendTo('#material_'+ fieldCount);
  });

  // Handle removing fields (using event delegation for dynamic elements)
  $("#fieldContainer").on("click", ".removeBtn", function() {
    $(this).parent(".field-group").remove();
    fieldCount--;
  });
  
  // Open Modal for Adding row
  $('#addRowBtn').click(function() {
      $('#dataForm')[0].reset();
      $('.modal-title').text('Nuevo Pedido');
      $('#action').val('create');
      $('#userModal').modal('show');
  });
    
</script>
<?php require_once '../../modules/layouts/footer.php'; ?>