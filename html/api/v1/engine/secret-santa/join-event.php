<?php
require(__DIR__ . "/../../engine/engine.php");
require_once(\Kickback\SCRIPT_ROOT . "/Kickback/Backend/Controllers/SecretSantaController.php");
use Kickback\Services\Session;

use Kickback\Backend\Controllers\SecretSantaController;

OnlyPOST();

$containsResp = POSTContainsFields('invite_token', 'display_name', 'email');
if (!$containsResp->success) {
    return $containsResp;
}

$inviteToken = Validate($_POST['invite_token']);
$displayName = Validate($_POST['display_name']);
$email = Validate($_POST['email']);
$exclusionCtime = isset($_POST['exclusion_group_ctime']) ? ValidateCTime($_POST['exclusion_group_ctime']) : null;
$exclusionCrand = isset($_POST['exclusion_group_crand']) ? intval($_POST['exclusion_group_crand']) : null;
$interest = isset($_POST['interest']) ? Validate($_POST['interest']) : null;
$account = null;
Session::readCurrentAccountInto($account);
$accountId = $account?->crand;

return SecretSantaController::joinEvent($inviteToken, $displayName, $email, $exclusionCtime, $exclusionCrand, $accountId, $interest);
?>
