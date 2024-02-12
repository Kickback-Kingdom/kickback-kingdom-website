<?php 

$session = require($_SERVER['DOCUMENT_ROOT']."/api/v1/engine/session/verifySession.php");


require("php-components/base-page-pull-active-account-info.php");


$homeFeedResp = GetNewsFeed();
$homeFeed = $homeFeedResp->Data;
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
                
                $randomQuote = getRandomQuote();

                $feedCard["type"] = "QUOTE";
                $feedCard["text"] = $randomQuote["text"];
                $feedCard['image'] = $randomQuote["image"];
                $feedCard['account_1_username'] = $randomQuote["author"];
                $feedCard["quoteDate"] = $randomQuote["date"];
                
                require ("php-components/feed-card.php");
                

                $activePageName = "Feed";
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>


                <?php


                for ($i=0; $i < count($homeFeed); $i++) 
                { 
                    $news = $homeFeed[$i];
                    $feedCard = $news;
                    require ("php-components/feed-card.php");
                }
                ?>
            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>

</body>

</html>