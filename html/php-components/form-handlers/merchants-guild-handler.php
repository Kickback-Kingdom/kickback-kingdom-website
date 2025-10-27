<?php
use Kickback\Backend\Controllers\MerchantGuildController;
use Kickback\Backend\Views\vRecordId;

if (Kickback\Services\Session::isLoggedIn())
{
    if (Kickback\Services\Session::isAdmin())
    {
        
        if (isset($_POST["process-purchase"]))
        {
            $tokenResponse = Kickback\Common\Utility\FormToken::useFormToken();

            if ($tokenResponse->success) {
    
            $pid = $_POST["purchase_id"];
            $purchaseToProcess = MerchantGuildController::PullMerchantGuildPurchaseInformation(new vRecordId('', (int)$pid))->data;
            $currentStatementTiedToPTP = MerchantGuildController::BuildStatement($purchaseToProcess->sharePurchase->account, $purchaseToProcess->sharePurchase->PurchaseDate, false);
            $preProcessData = MerchantGuildController::PreProcessPurchase($purchaseToProcess, $currentStatementTiedToPTP);
    
            $sharesToBeGiven = $preProcessData->shareCertificatesToBeGiven;

            $processResp = MerchantGuildController::ProcessMerchantSharePurchase(
                new vRecordId('', (int)$pid),
                (int)$sharesToBeGiven
            );
            
            if ($processResp->success)
            {
    
                $showPopUpSuccess = true;
                $PopUpMessage = $processResp->message;
                $PopUpTitle = "Purchase Processed Successfully";
            }
            else{
                $showPopUpError = true;
                $PopUpMessage = $processResp->message;
                $PopUpTitle = "Purchase Processed Failed";
    
            }
    
    
            unset($purchaseToProcess);
            unset($currentStatementTiedToPTP);
            unset($preProcessData);
            }
            else 
            {
                $hasError = true;
                $errorMessage = $tokenResponse->message;
            }
        }
    
        if (isset($_POST["process-statements"]))
        {
            
            $tokenResponse = Kickback\Common\Utility\FormToken::useFormToken();

            if ($tokenResponse->success) {

                $statementDate = Kickback\Backend\Views\vDateTime::fromDB($_POST["statement-date"]);

                $processResp = MerchantGuildController::ProcessMonthlyStatements($statementDate);
                if ($processResp->success)
                {
                    $showPopUpSuccess = true;
                    $PopUpMessage = $processResp->message;
                    $PopUpTitle = "Statement Processed Successfully";
    
                }
                else{
                    
                $showPopUpError = true;
                $PopUpMessage = $processResp->message;
                $PopUpTitle = "Purchase Processed Failed";
                }
            }
            else 
            {
                $hasError = true;
                $errorMessage = $tokenResponse->message;
            }
        }
    }
}


?>
