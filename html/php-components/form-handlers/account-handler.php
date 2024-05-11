<?php

if (isset($_POST["submit-equipment"]))
{
    
    //$showPopUpSuccess = true;
    //$PopUpTitle = "Recieved Data";
    //$PopUpMessage = json_encode($_POST);

    
    $response = UpsertAccountEquipment($_POST);

    if ($response->Success)
    {
        
        $hasSuccess = true;
        $successMessage= "Updated equipment successfully.";
    }
}

?>