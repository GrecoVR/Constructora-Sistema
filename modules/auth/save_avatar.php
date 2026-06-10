<?php
require_once '../../config/session.php';
require_once '../../middleware/auth.php';

$avatares_validos = ['🏗️', '🏢', '⚙️', '📐', '🪖', '📊'];

$avatar = $_POST['avatar'] ?? '';

if (in_array($avatar, $avatares_validos)) {
    $_SESSION['avatar'] = $avatar;
    registrarAccion(LOG_CAMBIAR_AVATAR . ' — avatar: ' . $avatar);
}

// Redirigir a la página anterior
$referer = $_SERVER['HTTP_REFERER'] ?? '../../modules/dashboard/dashboard.php';
header('Location: ' . $referer);
exit;