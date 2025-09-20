<?php
require_once(__DIR__ . "/../../engine/engine.php");

use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Services\QuestDashboardService;

OnlyPOST();

$contains = POSTContainsFields("sessionToken");
if (!$contains->success) {
    return $contains;
}

$sessionToken = Validate($_POST["sessionToken"]);

$serviceKey = ServiceCredentials::get("kk_service_key");
$loginResp = AccountController::getAccountBySession($serviceKey, $sessionToken);
if (!$loginResp->success) {
    return $loginResp;
}

$account = $loginResp->data;
if (!$account || !$account->isQuestGiver) {
    return new Response(false, "Quest dashboard is available to quest givers only.");
}

$service = new QuestDashboardService();

try {
    $payload = $service->buildApiPayload($account);
} catch (\Throwable $exception) {
    return new Response(false, "Failed to assemble quest dashboard data: " . $exception->getMessage());
}

return new Response(true, "Quest dashboard data loaded.", $payload);
?>
