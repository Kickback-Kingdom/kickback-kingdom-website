<?php
require(__DIR__ . "/../../engine/engine.php");
require_once(\Kickback\SCRIPT_ROOT . "/Kickback/Backend/Controllers/SecretSantaController.php");

use Kickback\Backend\Controllers\SecretSantaController;

OnlyGET();

return SecretSantaController::listOwnerEvents();
?>
