<?php
/**
 * LOGOUT DO ADMINISTRADOR
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/autoload.php';
require_once SRC_PATH . '/Infrastructure/Auth/SessionAuth.php';

use App\Infrastructure\Auth\SessionAuth;
SessionAuth::logout();
header('Location: login.php');
exit;
