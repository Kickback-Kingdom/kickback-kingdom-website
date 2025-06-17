<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Backend\Controllers\LichCardController;

$thisLichSet = null;
// Check if the locator is set in the GET request
if (isset($_GET["locator"])) {
    $locator = $_GET["locator"];
    
    // Fetch the Lich Set by its locator
    $response = LichCardController::getLichSetByLocator($locator);
    

    // Lich Set details
    $thisLichSet = $response->data; // This is a vLichSet object
    $thisLichSet->populateEverything();
} 

if ($thisLichSet == null) {
    // Redirect to homepage if no locator is provided
    header('Location: /lich');
    exit();
}

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
                
                
                $activePageName = $thisLichSet->name;
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>
                
                    <!-- Content Section -->
                    <div class="mt-4">
                        <?php 
                        if ($thisLichSet->hasPageContent()) {
                            $_vCanEditContent = $thisLichSet->canEdit();
                            $_vContentViewerEditorTitle = "L.I.C.H. Set Information Manager";
                            $_vPageContent = $thisLichSet->getPageContent();
                            require("php-components/content-viewer.php");
                        }
                        ?>
                    </div>

            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>
    <?php 
    if ($thisLichSet->hasPageContent())
    {
        $_vPageContent = $thisLichSet->getPageContent();
        require("php-components/content-viewer-javascript.php"); 
    }
    ?>
</body>

</html>
