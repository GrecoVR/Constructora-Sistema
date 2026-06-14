<?php
require_once '../../config/session.php';
require_once '../../middleware/auth_cliente.php';

$avatares_validos = ['🏢','👔','🏗️','📋','🌟','🏛️'];
$avatar = $_POST['avatar'] ?? '';

if (in_array($avatar, $avatares_validos)) {
    $_SESSION['avatar_cliente'] = $avatar;
}

$referer = $_SERVER['HTTP_REFERER'] ?? '../../client/index.php';
header('Location: ' . $referer);
exit;
?>