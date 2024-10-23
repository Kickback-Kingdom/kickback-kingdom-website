<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Backend\Controllers\GameController;
use Kickback\Backend\Controllers\AccountController;
use Kickback\Common\Version;

$gameLocator =  urldecode($_GET['locator']);
$gameResp = GameController::getGameByLocator($gameLocator);

if ($gameResp->success) {
    $thisGame = $gameResp->data;
} else {
    $thisGame = null;
}


$accountsResp = $thisGame ? AccountController::getAccountsByGoldCard($thisGame) : null;

if ($accountsResp && $accountsResp->success) {
    $goldCardHolders = $accountsResp->data;
} else {
    $goldCardHolders = [];
}

$accountRankingsResp = $thisGame ? AccountController::getAccountsByGame($thisGame) : null;

if ($accountRankingsResp && $accountRankingsResp->success) {
    $accountRankings = $accountRankingsResp->data;
} else {
    $accountRankings = [];
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
                // Show game name in breadcrumbs if game is available
                if ($thisGame !== null) {
                    $activePageName = $thisGame->name;
                    require("php-components/base-page-breadcrumbs.php");
                }

                // Show "Champion(s) of the kingdom" text if there are gold card holders
                if (count($goldCardHolders) > 0) {
                    $championText = (count($goldCardHolders) === 1) ? "Champion of the kingdom" : "Champions of the kingdom";
                    echo "<h2 class='mt-4 mb-3 text-center'>$championText</h2>";
                }
                ?>
                
                <div class="d-flex flex-wrap justify-content-evenly align-items-center mt-3">
                    <?php
                    // Render player cards for each gold card holder
                    foreach ($goldCardHolders as $goldCardHolder) :
                        $_vPlayerCardAccount = $goldCardHolder;
                        require("php-components/vPlayerCardRenderer.php"); 
                    endforeach;
                    ?>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="display-6 tab-pane-title mt-4">Rankings</div>
                        <div class="card mb-3">
                            <div class="card-body">
                                <table id="datatable-ranks" class="table display">
                                    <thead>
                                        <tr>
                                            <th scope="col">Rank</th>
                                            <th scope="col">Guildsmen</th>
                                            <th scope="col">ELO</th>
                                            <th scope="col">Wins</th>
                                            <th scope="col">Loses</th>
                                            <th scope="col">Matches</th>
                                            <th scope="col">W/L Ratio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        
                                        <?php 
                                        foreach ($accountRankings as $account) : 
                                            $gameStats = $account->game_stats[$thisGame->crand];
                                            ?>
                                                <tr >
                                                <td><?= $gameStats->getRankElement();?></td>
                                                <td><?= $account->getAccountElement();?></td>
                                                <td><?= $gameStats->elo;?></td>
                                                <td><?= $gameStats->total_wins;?></td>
                                                <td><?= $gameStats->total_losses;?></td>
                                                <td><?= $gameStats->ranked_matches;?></td>
                                                <td><?= number_format($gameStats->win_rate * 100, 2); ?>%</td>
                                                </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>

    <script>
        
        $(document).ready( function () {
            $('#datatable-ranks').DataTable({
                "order": [[2, 'desc']],
                "pageLength": 100       // Show 100 entries per page by default
            });
        } );

    </script>
</body>

</html>
