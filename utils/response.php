<?php

// Redirige con un mensaje de éxito o error guardado en sesión
function redirigir(string $url, string $tipo, string $mensaje): void {
    $_SESSION['flash_tipo']    = $tipo;    // 'exito' o 'error'
    $_SESSION['flash_mensaje'] = $mensaje;
    header("Location: $url");
    exit;
}

// Muestra el mensaje flash si existe y lo borra
function mostrarFlash(): string {
    if (!isset($_SESSION['flash_mensaje'])) return '';

    $tipo    = $_SESSION['flash_tipo'] ?? 'exito';
    $mensaje = $_SESSION['flash_mensaje'];
    $color   = $tipo === 'exito' ? 'green' : 'red';

    unset($_SESSION['flash_tipo'], $_SESSION['flash_mensaje']);

    return "<p style='color:$color'>$mensaje</p>";
}

// Sanitiza texto para mostrar en HTML
function limpiar(string $texto): string {
    return htmlspecialchars(trim($texto), ENT_QUOTES, 'UTF-8');
}

// Formatea monto en bolivianos
function formatoBS(float $monto): string {
    return 'Bs ' . number_format($monto, 2);
}

// Formatea fecha de Y-m-d a d/m/Y
function formatoFecha(string $fecha): string {
    if (!$fecha) return '—';
    $dt = DateTime::createFromFormat('Y-m-d', $fecha);
    return $dt ? $dt->format('d/m/Y') : $fecha;
}

// Verifica si una fecha ya pasó
function fechaVencida(string $fecha): bool {
    if (!$fecha) return false;
    return strtotime($fecha) < strtotime(date('Y-m-d'));
}