<?php

require_once 'config/database.php';

$password = password_hash('123456', PASSWORD_DEFAULT);

$sql = "INSERT INTO usuarios_sistema(
            id_empleado,
            nombre_usuario,
            password_hash,
            estado
        )
        VALUES(
            1,
            'admin',
            ?,
            'activo'
        )";

$stmt = $pdo->prepare($sql);

$stmt->execute([$password]);

echo "Administrador creado";