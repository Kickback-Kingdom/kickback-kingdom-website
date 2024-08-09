<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require(\Kickback\SCRIPT_ROOT . "/php-components/base-page-pull-active-account-info.php");
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

    

    <!--MAIN CONTENT-->
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                
                
                <?php 
                
                
                $activePageName = "Ranked Challenges";
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>

                
                <div class="row">
                    <div class="col-12">
                    <div class="display-6 tab-pane-title mt-4">Ranked Lobbies <button class="btn btn-primary float-end" onclick="OpenHostLobbyModal();">Host Lobby</button><a class="btn btn-primary float-end mx-1" href="#" onclick="window.location.reload()"><i class="fa-solid fa-arrow-rotate-right"></i></a></div>
                        <div class="card mb-3">
                            <div class="card-body">
                                <table id="datatable-lobbies" class="table display">
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Game</th>
                                            <th scope="col">Mode</th>
                                            <th scope="col">Host</th>
                                            <th scope="col">Players</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php require(\Kickback\SCRIPT_ROOT . "/php-components/base-page-discord.php"); ?>
        </div>
    </main>

    
    <?php require(\Kickback\SCRIPT_ROOT . "/php-components/base-page-javascript.php"); ?>
    <script>
        
        $(document).ready( function () {
            $('#datatable-lobbies').DataTable({
                "order": [[0, 'desc']]  // Sort by the 5th column (0-indexed) in ascending order
            });
        } );

    </script>
</body>

</html>
