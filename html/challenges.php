<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require(\Kickback\SCRIPT_ROOT . "/php-components/base-page-pull-active-account-info.php");


use Kickback\Services\Session;
use Kickback\Backend\Controllers\LobbyController;

$lobbiesResp = LobbyController::getLobbies();
$lobbies = $lobbiesResp->data;

?>
<!DOCTYPE html>
<html lang="en">

<?php require(\Kickback\SCRIPT_ROOT . "/php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    
    <?php 
    require(\Kickback\SCRIPT_ROOT . "/php-components/base-page-components.php"); 
    require(\Kickback\SCRIPT_ROOT . "/php-components/host-lobby.php"); 
    require(\Kickback\SCRIPT_ROOT . "/php-components/ad-carousel.php"); 
    ?>

    <!-- MAIN CONTENT -->
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                
                <?php 
                $activePageName = "Ranked Challenges";
                require("php-components/base-page-breadcrumbs.php"); 
                ?>

                <div class="row">
                    <div class="col-12">
                        <div class="display-6 tab-pane-title mt-4">Find Ranked Lobbies 
                            <?php if (Session::isLoggedIn()) { ?>
                            <button class="btn btn-primary float-end" onclick="OpenHostLobbyModal();">Host Lobby</button>
                            <?php } else { ?>
                                <a class="btn btn-primary float-end mx-1" href="/login.php?redirect=/challenges.php">Host Lobby</a>
                            <?php } ?>
                            <a class="btn btn-primary float-end mx-1" href="#" onclick="window.location.reload()"><i class="fa-solid fa-arrow-rotate-right"></i></a>
                        </div>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="datatable-lobbies" class="dataTable no-footer nowrap table table-striped" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th scope="col"></th>
                                                <th scope="col">Game</th>
                                                <th scope="col">Host</th>
                                                <th scope="col">Players</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $lobbyIndex = 0;
                                            foreach ($lobbies as $lobby) : 
                                                $lobbyIndex++;
                                            ?>
                                                <tr>
                                                    <td><?= $lobby->getJoinButtonElement(); ?></td>
                                                    <td><?= $lobby->game->name; ?></td>
                                                    <td><?= $lobby->host->getAccountElement(); ?></td>
                                                    <td><?= $lobby->challenge->getPlayerCount(); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php require(\Kickback\SCRIPT_ROOT . "/php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    <?php require(\Kickback\SCRIPT_ROOT . "/php-components/base-page-javascript.php"); ?>
    <script>
        $(document).ready(function() {
            $('#datatable-lobbies').DataTable({
                "order": [[1, 'desc']],
                "responsive": true,
                "scrollX": false,
            });
        });
    </script>
</body>

</html>
