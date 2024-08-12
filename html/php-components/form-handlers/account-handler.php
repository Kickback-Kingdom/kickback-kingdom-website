<?php
declare(strict_types=1);
use Kickback\Backend\Controllers\AccountController;

if (isset($_POST["submit-equipment"]))
{
    
    //$showPopUpSuccess = true;
    //$PopUpTitle = "Recieved Data";
    //$PopUpMessage = json_encode($_POST);

    
    $response = AccountController::upsertAccountEquipment($_POST);

    if ($response->success)
    {
        
        $hasSuccess = true;
        $successMessage= "Updated equipment successfully.";
    }
}

?>