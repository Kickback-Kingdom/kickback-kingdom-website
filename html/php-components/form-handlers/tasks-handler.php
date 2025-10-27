<?php
declare(strict_types=1);

use Kickback\Services\Session;
use Kickback\Backend\Controllers\TaskController;
use Kickback\Backend\Views\vRecordId;

if (isset($_POST["submit_claim_task"])) {
    $account = Session::getCurrentAccount();

    $taskCTime = $_POST["claim_task_ctime"] ?? null;
    $taskCRand = $_POST["claim_task_crand"] ?? null;

    if (empty($taskCTime) || empty($taskCRand)) {
        $showPopUpError = true;
        $PopUpTitle = "Validation Error";
        $PopUpMessage = "Failed to claim reward. Invalid task reference.";
    } else {
        $taskId = new vRecordId($taskCTime, (int)$taskCRand);
        $claimResp = TaskController::ClaimTaskReward($account, $taskId);

        if (!$claimResp->success) {
            $showPopUpError = true;
            $PopUpTitle = "Claim Error";
            $PopUpMessage = $claimResp->message;
        } else {
            //$showPopUpSuccess = true;
            //$PopUpTitle = "Task Complete!";
            //$PopUpMessage = $claimResp->message;
        }
    }
}
