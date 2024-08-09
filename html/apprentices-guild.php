<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

$certificationsResp = GetAllCertifications();

$certifications = $certificationsResp->data;
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
                
                
                $activePageName = "Apprentice's Guild";
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>

                <div class="row row-cols-1 row-cols-md-3 g-4">
                    
                        <?php
                        for ($i=0; $i < count($certifications); $i++) 
                        { 
                            //$news = $homeFeed[$i];
                        ?>
                            <div class="col">
                                <div class="card" style="width: 18rem;">
                                    <img src="/assets/media/<?php echo $certifications[$i]["imagePath_icon"]; ?>" class="card-img-top" alt="<?php echo $certifications[$i]["name"]; ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $certifications[$i]["name"]; ?></h5>
                                        <p class="card-text"><?php echo $certifications[$i]["summary"]; ?></p>
                                        <a href="<?php echo $urlPrefixBeta; ?>/apprentices-guild/certifications.php?id=<?php echo $certifications[$i]["Id"]; ?>" class="btn btn-primary">View Details</a>
                                    </div>
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
