<?php

// Diferencia en días entre hoy y una fecha
function diasRestantes(string $fecha): int {
    $hoy    = new DateTime(date('Y-m-d'));
    $fin    = new DateTime($fecha);
    $diff   = $hoy->diff($fin);
    return $diff->invert ? -$diff->days : $diff->days;
}

// Devuelve texto legible de estado de fecha
function estadoFecha(string $fecha): string {
    $dias = diasRestantes($fecha);

    if ($dias < 0) {
        return "<span style='color:red'>Vencido hace " . abs($dias) . " días</span>";
    } elseif ($dias === 0) {
        return "<span style='color:orange'>Vence hoy</span>";
    } elseif ($dias <= 7) {
        return "<span style='color:orange'>Vence en $dias días</span>";
    } else {
        return "<span style='color:green'>Vence en $dias días</span>";
    }
}

// Formatea fecha Y-m-d a d/m/Y
function formatoFechaCorta(string $fecha): string {
    if (!$fecha) return '—';
    $dt = DateTime::createFromFormat('Y-m-d', $fecha);
    return $dt ? $dt->format('d/m/Y') : $fecha;
}

// Verifica si una fecha ya pasó
function estaVencida(string $fecha): bool {
    if (!$fecha) return false;
    return strtotime($fecha) < strtotime(date('Y-m-d'));
}

// Verifica si vence en los próximos N días
function venceProximo(string $fecha, int $dias = 30): bool {
    $restantes = diasRestantes($fecha);
    return $restantes >= 0 && $restantes <= $dias;
}