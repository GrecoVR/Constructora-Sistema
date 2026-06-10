<?php
require_once '../../config/session.php';
require_once '../../middleware/logger.php';

registrarAccion(LOG_LOGOUT);
session_destroy();
header('Location: login.php');
exit;