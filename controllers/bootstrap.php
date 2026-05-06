<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/model/Database.php';
require_once BASE_PATH . '/model/UserModel.php';
require_once BASE_PATH . '/model/ReservationModel.php';
require_once BASE_PATH . '/model/InventoryModel.php';

require_once BASE_PATH . '/controllers/BaseController.php';
require_once BASE_PATH . '/controllers/AuthController.php';
require_once BASE_PATH . '/controllers/PageController.php';
require_once BASE_PATH . '/controllers/UsuarioController.php';
require_once BASE_PATH . '/controllers/ReservaController.php';
require_once BASE_PATH . '/controllers/InventarioController.php';
