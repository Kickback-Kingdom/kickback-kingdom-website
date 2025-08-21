<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");

use Kickback\Services\Database;
use Kickback\Backend\Controllers\RankedSystemController;

$conn = Database::getConnection();
$rankedSystem = new RankedSystemController($conn);
$rankedSystem->processRankedMatches();
?>
