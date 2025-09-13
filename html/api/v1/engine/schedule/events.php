<?php
require_once(__DIR__ . '/../engine.php');

use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Controllers\ScheduleController;
use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Backend\Models\Response;

OnlyPOST();

$month = isset($_POST['month']) ? intval($_POST['month']) : intval(date('m'));
$year  = isset($_POST['year']) ? intval($_POST['year']) : intval(date('Y'));
$includeAll = isset($_POST['includeAll']) ? intval($_POST['includeAll']) === 1 : false;

$questGiverId = null;
if (isset($_POST['sessionToken'])) {
    $sessionToken = Validate($_POST['sessionToken']);
    $kk_service_key = ServiceCredentials::get('kk_service_key');
    $loginResp = AccountController::getAccountBySession($kk_service_key, $sessionToken);
    if (!$loginResp->success) {
        return $loginResp;
    }
    $account = $loginResp->data;
    $questGiverId = $account->crand;
}

$events = ScheduleController::getCalendarEvents($month, $year, $questGiverId, $includeAll);

return new Response(true, 'Calendar events loaded.', $events);
?>
