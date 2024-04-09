<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

$gamesResp = GetAllGames();

$games = $gamesResp->Data;
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
                
                
                $activePageName = "Games & Activities";
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>
                <div class="d-flex flex-wrap justify-content-evenly align-items-center mt-3" id="town-squareselectAccountSearchResults" data-users-per-page="21" style="border-style: none;">
                <?php
                foreach ($games as $game)
                { 
                    //$news = $homeFeed[$i];
                ?>
                    
                    <div class="card" style="width: 18rem; margin-bottom:10px;">
                        <img src="/assets/media/<?php echo $game["icon_path"]; ?>" class="card-img-top" alt="<?php echo $game["Name"]; ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $game["Name"]; ?></h5>
                            <p class="card-text"><?php echo $game["Desc"]; ?></p>
                            <a href="<?php echo $urlPrefixBeta; ?>/g/<?php echo $game["Name"]; ?>" class="btn btn-primary">View Game</a>
                        </div>
                    </div>
                <?php
                }
                ?>
                </div>
            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>

</body>

</html>
