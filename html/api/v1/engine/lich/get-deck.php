<?php
require(__DIR__ . "/../../engine/engine.php");

OnlyGET();

use Kickback\Backend\Controllers\LichCardController;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Models\Response;

if (!isset($_GET["lootId"])) {
    return new Response(false, "Missing required parameters.");
}

$lootId = new vRecordId('', Validate($_GET["lootId"]));


// Fetch container contents
return LichCardController::getPreConDeckByLootId($lootId);
?>