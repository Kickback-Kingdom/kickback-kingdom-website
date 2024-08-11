<?php
if (Kickback\Services\Session::isLoggedIn())
{
    if (Kickback\Services\Session::isAdmin())
    {
        
        if (isset($_POST["process-purchase"]))
        {
            $tokenResponse = Kickback\Common\Utility\FormToken::useFormToken();

            if ($tokenResponse->success) {
    
            $pid = $_POST["purchase_id"];
            $purchaseToProcess = PullMerchantGuildPurchaseInformation($pid);
            $currentStatementTiedToPTP = BuildStatement($purchaseToProcess['account_id'], $purchaseToProcess['execution_date'], false);
            $preProcessData = PreProcessPurchase($purchaseToProcess, $currentStatementTiedToPTP);
    
    
            $sharesToBeGiven = $preProcessData["shareCertificatesToBeGivien"];
    
            //for each share to be given
            //$giveLootResp = GiveMerchantGuildShare($purchaseToProcess['account_id'],$purchaseToProcess['execution_date']);
            $processResp = ProcessMerchantSharePurchase($pid, $sharesToBeGiven);
    
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
                $statement_date = $_POST["statement-date"];
                $processResp = ProcessMonthlyStatements($statement_date);
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
