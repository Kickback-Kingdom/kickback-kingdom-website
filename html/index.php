<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Controllers\NewsController;
use Kickback\Controllers\FeedCardController;
use Kickback\Controllers\QuoteController;

$homeFeedResp = NewsController::getNewsFeed(1, 20);
$homeFeed = $homeFeedResp->data;
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

                $randomQuote = QuoteController::getRandomQuote();

                $_vFeedCard = FeedCardController::vQuote_to_vFeedCard($randomQuote);
                require("php-components/vFeedCardRenderer.php");


                $activePageName = "Feed";
                require("php-components/base-page-breadcrumbs.php");

                ?>


                <?php


                for ($i=0; $i < count($homeFeed); $i++)
                {
                    $news = $homeFeed[$i];                    
                    $_vFeedCard = FeedCardController::vNews_to_vFeedCard($news);
                    require("php-components/vFeedCardRenderer.php");
                }
                ?>
            </div>

            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>


    <?php require("php-components/base-page-javascript.php"); ?>

</body>

</html>
