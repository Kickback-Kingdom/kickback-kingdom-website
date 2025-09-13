<?php
require_once(__DIR__ . '/../engine.php');

use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Controllers\ScheduleController;
use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Backend\Models\Response;

OnlyGET();

$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year  = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

$questGiverId = null;
if (isset($_GET['sessionToken'])) {
    $sessionToken = Validate($_GET['sessionToken']);
    $kk_service_key = ServiceCredentials::get('kk_service_key');
    $loginResp = AccountController::getAccountBySession($kk_service_key, $sessionToken);
    if (!$loginResp->success) {
        return $loginResp;
    }
    $account = $loginResp->data;
    $questGiverId = $account->crand;
}

$events = ScheduleController::getCalendarEvents($month, $year, $questGiverId);

return new Response(true, 'Calendar events loaded.', $events);
?>
