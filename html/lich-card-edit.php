<?php
declare(strict_types=1);

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");


use Kickback\Backend\Controllers\LichCardController;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vLichCard;


if (!Kickback\Services\Session::isServantOfTheLich())
{
    header('Location: /index.php');
    exit();
}


if (!isset($thisLichCardData))
{

    if (isset($_GET["locator"]))
    {
        // Retrieve the Lich Card by locator
        $response = LichCardController::getLichCardByLocator($_GET["locator"]);

        if ($response->success) {
            $thisLichCardData = $response->data;
        } else {
            // Handle error (e.g., display an error message)
            $thisLichCardData = new vLichCard(); // Default to an empty Lich Card
        }
    }
    else{
        $thisLichCardData = new vLichCard();
    }
}

$lichSetsResp = LichCardController::getAllLichSets();
$lichSets = $lichSetsResp->data;

if ($thisLichCardData->set->crand == -1)
{
    $thisLichCardData->set = $lichSets[0];
}

$lichSubTypesResp = LichCardController::getAllSubtypes();
$lichSubTypes = $lichSubTypesResp->data; //[{"crand":1112084571,"ctime":"2025-01-15 20:43:08.000000","name":"Relic"}]


// Extract the names into a new array
$lichSubTypeNames = array_map(function ($subType) {
    return $subType['name'];
}, $lichSubTypes);
?>

<!DOCTYPE html>
<html lang="en">


<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    
    <?php 
    
    require("php-components/base-page-components.php"); 
    
    require("php-components/ad-carousel.php"); 
    
    ?>

    

    <!--MAIN CONTENT-->
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                
                
                <?php 
                
                
                $activePageName = "L.I.C.H. Card Editor";
                require("php-components/base-page-breadcrumbs.php"); 
                require("php-components/LICH/card-renderer.php"); 
                
                ?>
                
            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>

</body>

</html>