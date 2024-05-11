<?php

if (IsAdmin())
{
    
    if (isset($_POST["process-purchase"]))
    {
        $tokenResponse = Kickback\Utilities\FormToken::useFormToken();
    
        if ($tokenResponse->Success) {

        $pid = $_POST["purchase_id"];
        $purchaseToProcess = PullMerchantGuildPurchaseInformation($pid);
        $currentStatementTiedToPTP = BuildStatement($purchaseToProcess['account_id'], $purchaseToProcess['execution_date'], false);
        $preProcessData = PreProcessPurchase($purchaseToProcess, $currentStatementTiedToPTP);


        $sharesToBeGiven = $preProcessData["shareCertificatesToBeGivien"];

        //for each share to be given
        //$giveLootResp = GiveMerchantGuildShare($purchaseToProcess['account_id'],$purchaseToProcess['execution_date']);
        $processResp = ProcessMerchantSharePurchase($pid, $sharesToBeGiven);

        if ($processResp->Success)
        {

            $showPopUpSuccess = true;
            $PopUpMessage = $processResp->Message;
            $PopUpTitle = "Purchase Processed Successfully";
        }
        else{
            $showPopUpError = true;
            $PopUpMessage = $processResp->Message;
            $PopUpTitle = "Purchase Processed Failed";

        }


        unset($purchaseToProcess);
        unset($currentStatementTiedToPTP);
        unset($preProcessData);
        }
        else 
        {
            $hasError = true;
            $errorMessage = $tokenResponse->Message;
        }
    }

    if (isset($_POST["process-statements"]))
    {
        
        $tokenResponse = Kickback\Utilities\FormToken::useFormToken();

        if ($tokenResponse->Success) {
            $statement_date = $_POST["statement-date"];
            $processResp = ProcessMonthlyStatements($statement_date);
            if ($processResp->Success)
            {
                $showPopUpSuccess = true;
                $PopUpMessage = $processResp->Message;
                $PopUpTitle = "Statement Processed Successfully";

            }
            else{
                
            $showPopUpError = true;
            $PopUpMessage = $processResp->Message;
            $PopUpTitle = "Purchase Processed Failed";
            }
        }
        else 
        {
            $hasError = true;
            $errorMessage = $tokenResponse->Message;
        }
    }
}

?>