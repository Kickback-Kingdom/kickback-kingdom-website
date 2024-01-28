<?php 

$session = require($_SERVER['DOCUMENT_ROOT']."/api/v1/engine/session/verifySession.php");


require("php-components/base-page-pull-active-account-info.php");


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
                
                
                $activePageName = "Guild Halls";
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>
                <div class="row">
                    <div class="col-12">
                        <a href="<?php echo $urlPrefixBeta; ?>/adventurers-guild.php">
                            <div class="card text-bg-dark card-guild">
                                <div class="parallax-container overflow-hidden">
                                    <img class="parallax" src="/assets/media/context/adventure.jpg" alt="Image description">
                                    <div class="shine"></div> <!-- shine effect here -->
                                </div>
                                <div class="card-img-overlay parallax-mouse-capture">
                                    <h5 class="card-title">Adventurers' Guild<i class="fa-solid fa-right-to-bracket float-end"></i></h5>
                                    <p class="card-text"><small>Community</small></p>
                                    <span class="guild-stats"><i class="fa-solid fa-users"></i> 100</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-12 col-lg-6">
                        <a href="<?php echo $urlPrefixBeta; ?>/merchants-guild.php">
                            <div class="card text-bg-dark card-guild">
                                <div class="parallax-container overflow-hidden">
                                    <img class="parallax" src="/assets/media/context/merchant.jpg" alt="Image description">
                                    <div class="shine"></div> <!-- shine effect here -->
                                </div>
                                <div class="card-img-overlay parallax-mouse-capture">
                                    <h5 class="card-title">Merchants' Guild<i class="fa-solid fa-right-to-bracket float-end"></i></h5>
                                    <p class="card-text"><small>Investors</small></p>
                                    <span class="guild-stats"><i class="fa-solid fa-users"></i> 100</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-12 col-lg-6">
                        <a href="<?php echo $urlPrefixBeta; ?>/craftsmens-guild.php">
                            <div class="card text-bg-dark card-guild">
                                <div class="parallax-container overflow-hidden">
                                    <img class="parallax" src="/assets/media/context/craftsmen.jpg" alt="Image description">
                                    <div class="shine"></div> <!-- shine effect here -->
                                </div>
                                <div class="card-img-overlay parallax-mouse-capture">
                                    <h5 class="card-title">Craftsmen's Guild<i class="fa-solid fa-right-to-bracket float-end"></i></h5>
                                    <p class="card-text"><small>Contractors</small></p>
                                    <span class="guild-stats"><i class="fa-solid fa-users"></i> 100</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-12 col-lg-6">
                        <a href="<?php echo $urlPrefixBeta; ?>/apprentices-guild.php">
                            <div class="card text-bg-dark card-guild">
                                <div class="parallax-container overflow-hidden">
                                    <img class="parallax" src="/assets/media/context/apprentice.jpg" alt="Image description">
                                    <div class="shine"></div> <!-- shine effect here -->
                                </div>
                                <div class="card-img-overlay parallax-mouse-capture">
                                    <h5 class="card-title">Apprentices' Guild<i class="fa-solid fa-right-to-bracket float-end"></i></h5>
                                    <p class="card-text"><small>Learners</small></p>
                                    <span class="guild-stats"><i class="fa-solid fa-users"></i> 100</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-12 col-lg-6">
                        <a href="<?php echo $urlPrefixBeta; ?>/stewards-guild.php">
                            <div class="card text-bg-dark card-guild">
                                <div class="parallax-container overflow-hidden">
                                    <img class="parallax" src="/assets/media/context/steward.jpg" alt="Image description">
                                    <div class="shine"></div> <!-- shine effect here -->
                                </div>
                                <div class="card-img-overlay parallax-mouse-capture">
                                    <h5 class="card-title">Stewards' Guild<i class="fa-solid fa-right-to-bracket float-end"></i></h5>
                                    <p class="card-text"><small>Syndicate</small></p>
                                    <span class="guild-stats"><i class="fa-solid fa-users"></i> 100</span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>


                
            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>

</body>

</html>