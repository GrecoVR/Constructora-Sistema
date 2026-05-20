<?php
function requierePermiso(string $permiso): void {
    if (!in_array($permiso, $_SESSION['permisos'] ?? [])) {
        http_response_code(403);
        die("<h2>Acceso denegado</h2><p>No tienes permiso para ver esta página.</p>");
    }
}