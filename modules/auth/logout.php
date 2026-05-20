<?php
require_once '../../config/session.php';
require_once '../../middleware/logger.php';
registrarAccion('Salió del sistema');
session_destroy();
header('Location: login.php');
exit;