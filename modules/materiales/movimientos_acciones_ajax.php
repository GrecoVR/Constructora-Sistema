<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../config/database.php';
require_once '../../triggers/TriggerManager.php';

$pdo   = conectar();

// 1. READ (DataTables Server-Side Fetch)
if (isset($_POST['draw'])) {
    $draw = intval($_POST['draw']);
    $start = intval($_POST['start']);
    $length = intval($_POST['length']);
    $search_value = $_POST['search']['value'];

    $columns = array('fecha', 'id_material', 'id_almacen', 'tipo_movimiento', 'cantidad');
    // Base query
    $query = "SELECT mi.id_movimiento, mi.fecha, mi.tipo_movimiento, mi.cantidad,
           m.nombre as material, a.nombre as almacen
    FROM movimientos_inventario mi
    JOIN materiales m ON m.id_material = mi.id_material
    JOIN almacenes a ON a.id_almacen = mi.id_almacen
    WHERE 1=1";
    
    $params = [];

    // Search functionality
    if (!empty($search_value)) {
        $query .= " AND (mi.fecha = :search OR mi.tipo_movimiento LIKE :search OR m.nombre LIKE :search OR a.nombre LIKE :search OR mi.cantidad = :search)";
        $params[':search'] = "%$search_value%";
    }

    // Total records without filtering
    $total_stmt = $pdo->query("SELECT COUNT(*) FROM movimientos_inventario");
    $totalRecords = $total_stmt->fetchColumn();

    // Total records with filtering
    $filter_stmt = $pdo->prepare($query);
    $filter_stmt->execute($params);
    $totalRecordwithFilter = $filter_stmt->rowCount();
    
    // Check if sorting parameters were sent
    if (isset($_POST['order'])) {
        $columnIndex = $_POST['order'][0]['column']; // Column index
        $columnName = 'mi.'.$columns[$columnIndex];  // Get actual field name
        $columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
        
        $query .= " ORDER BY " . $columnName . " " . $columnSortOrder;
    } else {
        // Default sort if no user action
        $query .= " ORDER BY mi.id_movimiento DESC";
    }
    
    // Pagination and Fetching Data
    $query .= " LIMIT :start, :length";
    $stmt = $pdo->prepare($query);
    
    // Bind pagination values dynamically
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    $stmt->execute();
    
    $data = [];
    while ($row = $stmt->fetch()) {
        $action_buttons = '
            <button class="btn btn-sm btn-outline-secondary border-0 fw-semibold editBtn" data-id="'.$row['id_movimiento'].'">
            <i class="bi bi-pencil-square"></i> Editar</button>
            <button class="btn btn-sm btn-outline-danger border-0 fw-semibold deleteBtn" data-id="'.$row['id_movimiento'].'">
            <i class="bi bi-trash-fill"></i> Eliminar</button>
        ';
        $data[] = [
            //$row['id_movimiento'],
            $row['fecha'],
            $row['material'],
            $row['almacen'],
            $row['tipo_movimiento'],
            $row['cantidad'],
            $action_buttons
        ];
    }

    $response = [
        "draw" => $draw,
        "iTotalRecords" => $totalRecords,
        "iTotalDisplayRecords" => $totalRecordwithFilter,
        "aaData" => $data
    ];

    echo json_encode($response);
    exit;
}

// 2. CREATE & UPDATE
if (isset($_POST['action'])) {
    $error = '';
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

            if (!$inv || floatval($inv['stock']) < $cantidad) {
                $error = 'Stock insuficiente para registrar esta salida';
                echo json_encode(['status' => $error]);
                exit;
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
            $result = $stmt->execute([$id_material, $id_almacen, $tipo_movimiento, $fecha, $cantidad_real]);

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
            echo json_encode(['status' => $result ? 'success' : $error]);
            exit;
        }
      } else{
        $error = 'Completa todos los campos';
        echo json_encode(['status' => $error]);
        exit;
      }
    }
    
    //actualizar
    
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
                echo json_encode(['status' => $error]);
                exit;
            }
            
        }

        if (!$error) {
            // Registra el movimiento
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

            registrarAccion("Registró movimiento $tipo_movimiento — material ID: $id_material");
            echo json_encode(['status' => $result ? 'success' : $error]);
            exit;
        }
      } else{
        $error = 'Completa todos los campos';
        echo json_encode(['status' => $error]);
        exit;
      }
    }
}

// 3. FETCH SINGLE USER (For Editing)
if (isset($_POST['fetch_single'])) {
    $stmt = $pdo->prepare("SELECT * FROM movimientos_inventario WHERE id_movimiento = ?");
    $stmt->execute([$_POST['id']]);
    echo json_encode($stmt->fetch());
    exit;
}

// 4. DELETE
if (isset($_POST['delete_user'])) {
    $stmt = $pdo->prepare("DELETE FROM movimientos_inventario WHERE id_movimiento = ?");
    $result = $stmt->execute([$_POST['id']]);
    echo json_encode(['status' => $result ? 'success' : 'error']);
    exit;
}
?>