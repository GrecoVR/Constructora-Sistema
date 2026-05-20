<?php

session_start();

if (!isset($_SESSION['inicio'])) {
    session_regenerate_id(true);
    $_SESSION['inicio'] = true;
}