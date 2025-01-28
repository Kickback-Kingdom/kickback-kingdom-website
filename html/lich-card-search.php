<?php
declare(strict_types=1);

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

// Fetch all Lich Cards
use Kickback\Backend\Controllers\LichCardController;
use Kickback\Common\Version;

$response = LichCardController::getAllLichCards();
$lichCards = $response->success ? $response->data : [];

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
                
                
                $activePageName = "L.I.C.H. Card Search";
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>
                <!-- L.I.C.H. Cards Section -->
                <section class="lich-cards mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="text-center">All L.I.C.H. Cards</h2>
                        <?php if (Kickback\Services\Session::isServantOfTheLich()) { ?>
                        <a href="<?php echo Version::urlBetaPrefix(); ?>/lich-card-edit.php" class="btn btn-success">
                            <i class="fa-solid fa-plus"></i> Create New Card
                        </a>
                        <?php } ?>
                    </div>
                    
                    <?php if (empty($lichCards)): ?>
                        <div class="alert alert-warning text-center">
                            No L.I.C.H. cards found.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($lichCards as $card): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card shadow-sm" style="border-radius: 16px;">
                                            <a href="<?php echo Version::urlBetaPrefix(); ?>/lich/card/<?= urlencode($card->locator); ?>" class="">
                                        <img 
                                            src="<?= htmlspecialchars($card->cardImage->getFullPath()); ?>" 
                                            class="card-img-top" 
                                            alt="<?= htmlspecialchars($card->name); ?>">
                                            </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>

</body>

</html>
