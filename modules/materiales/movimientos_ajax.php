<?php
date_default_timezone_set('America/La_Paz');

require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('registrar_movimientos');

$pdo   = conectar();
$permisos = $_SESSION['permisos'];
// Filtro por material específico si viene desde index
$id_material_filtro = intval($_GET['id_material'] ?? 0);

$materiales = $pdo->query("SELECT id_material, nombre FROM materiales ORDER BY nombre ASC")->fetchAll();
$almacenes  = $pdo->query("SELECT id_almacen, nombre FROM almacenes ORDER BY nombre ASC")->fetchAll();
?>

<?php require_once '../../modules/layouts/header.php'; ?>

<nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="../../modules/dashboard/dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Movimientos</li>
  </ol>
</nav>

<h2 class="mb-4 fw-semibold">🔄 Movimientos de Inventario</h2>

<a class="btn btn-secondary mb-4" href="index.php">Ver materiales</a>
    
  <div class="card shadow">
      <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="mb-0">Movimientos</h4>
          <button type="button" class="btn btn-success" id="addRowBtn"><i class="bi bi-plus-lg"></i> Agregar Movimiento</button>
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
      </table>
      </div>
  </div>

<!--  Modal (Handles both Create and Update) -->
<form method="POST" id="dataForm">
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">Registrar Movimiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

            <input type="hidden" name="id_movimiento" id="id_movimiento">
            <input type="hidden" name="action" id="action" value="create">
            <div class="mt-3 mb-3">
              <label class="form-label">Material: *</label>
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
            <div class="mb-3">
              <label for="fecha" class="form-label">Fecha: *</label><br>
              <input class="form-control" type="date" id="fecha" name="fecha" value="<?= date('Y-m-d') ?>" required>
            </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type="submit">Registrar</button>
            </div>
        </div>
   </div>
</div><!-- end modal -->
</form>

<script>
$(document).ready(function() {
    // Initialize DataTables Server-Side Processing
    var dataTable = $('#tabla-datos').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
        },
        processing: true,
        serverSide: true,
        order: [],
        ajax: {
            url: 'movimientos_acciones_ajax.php',
            type: 'POST'
        },
        columnDefs: [
            {
                targets: -1, // The "Actions" column
                orderable: false, // Disable sorting on this column
            },
        ]
    });

    // Open Modal for Adding row
    $('#addRowBtn').click(function() {
        $('#dataForm')[0].reset();
        $('#userModalLabel').text('Registrar Movimiento');
        $('#action').val('create');
        $('#userModal').modal('show');
    });

    // Handle Form Submission (Create and Update)
    $('#dataForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: "movimientos_acciones_ajax.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "JSON",
            success: function(data) {
                if(data.status === 'success') {
                    $('#userModal').modal('hide');
                    $('#dataForm')[0].reset();
                    dataTable.ajax.reload(); // Reload table data smoothly
                } else {
                     //show toast bootstap
                    alert('Something went wrong. Please try again.');
                }
            }
        });
    });

    // Handle Edit Button Click
    $(document).on('click', '.editBtn', function() {
        var id = $(this).data('id');
        $.ajax({
            url: "movimientos_acciones_ajax.php",
            type: "POST",
            data: { id: id, fetch_single: true },
            dataType: "JSON",
            success: function(data) {
                $('#id_movimiento').val(data.id_movimiento);
                $('#id_material').val(data.id_material);
                $('#id_almacen').val(data.id_almacen);
                $('#tipo_movimiento').val(data.tipo_movimiento);
                $('#cantidad').val(data.cantidad);
                $('#fecha').val(data.fecha);
                
                $('#userModalLabel').text('Editar Movimiento');
                $('#action').val('update');
                $('#userModal').modal('show');
            }
        });
    });

    // Handle Delete Button Click
    $(document).on('click', '.deleteBtn', function() {
        var id = $(this).data('id');
        if(confirm("Estas seguro que deseas eliminar este movimiento?")) {
            $.ajax({
                url: "movimientos_acciones_ajax.php",
                type: "POST",
                data: { id: id, delete_user: true },
                dataType: "JSON",
                success: function(data) {
                    if(data.status === 'success') {
                        dataTable.ajax.reload();
                    }
                }
            });
        }
    });
});
</script>
<?php require_once '../../modules/layouts/footer.php'; ?>