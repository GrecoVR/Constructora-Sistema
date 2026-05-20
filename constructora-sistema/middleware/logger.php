<?php

function registrarLog($pdo, $accion, $descripcion) {

    $sql = "INSERT INTO registros_sistema(
                accion,
                descripcion,
                fecha
            )
            VALUES(?, ?, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$accion, $descripcion]);
}