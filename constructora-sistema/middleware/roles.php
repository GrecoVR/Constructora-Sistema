<?php

function verificarRol($rolNecesario) {

    if (!isset($_SESSION['rol'])) {
        die('No autorizado');
    }

    if ($_SESSION['rol'] !== $rolNecesario) {
        die('Acceso denegado');
    }
}