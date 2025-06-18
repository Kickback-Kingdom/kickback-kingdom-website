<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require(\Kickback\SCRIPT_ROOT . "/php-components/base-page-pull-active-account-info.php");


use Kickback\Services\Session;
use Kickback\Backend\Controllers\LobbyController;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vLobby;
use Kickback\Backend\Views\vChallengePlayer;

$lobbyCode = $_GET["l"];

$lobbyId = vRecordId::fromEncrypted($lobbyCode);

$lobbyResp = LobbyController::getLobby($lobbyId);
$thisLobby = $lobbyResp->data;
if ($thisLobby == null)
{
    Session::redirect("/challenges.php");
}
$thisLobby->challenge->downloadPlayers();
//$thisLobby->challenge->players;
$teamPlayers = $thisLobby->challenge->getGroupedPlayers();

?>

<!DOCTYPE html>
<html lang="en">


<?php require(\Kickback\SCRIPT_ROOT . "/php-components/base-page-head.php"); ?>

    <body class="bg-body-secondary container p-0">
    
    <?php 
    
    require(\Kickback\SCRIPT_ROOT . "/php-components/base-page-components.php"); 
    
    require(\Kickback\SCRIPT_ROOT . "/php-components/manage-lobby.php"); 
    require(\Kickback\SCRIPT_ROOT . "/php-components/ad-carousel.php"); 
    
    ?>

    <!--MAIN CONTENT-->
        <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
            <div class="row">
                <div class="col-12 col-xl-9">
                    
                    <?php 
                    $activePageName = "Custom Ranked Lobby";
                    require("php-components/base-page-breadcrumbs.php"); 
                    ?>
                    <div class="row">
                        <div class="col-12">
                            <?php if ($hasError) {?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Oh snap!</strong> <?php echo $errorMessage; ?>
                            </div>
                            <?php } ?>
                            <?php if ($hasSuccess) {?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong>Congrats!</strong> <?php echo $successMessage; ?>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                    <?php $lobbyStatus = $thisLobby->getLobbyStatus(); ?>
                    <div class="d-flex align-items-center justify-content-center p-3 rounded <?= $lobbyStatus['class']; ?> mb-2">
                        <i class="fas fa-spinner fa-spin me-3" style="font-size: 2rem;" aria-hidden="true"></i>
                        <span><?= $lobbyStatus['message']; ?></span>
                    </div>
                    <?= JSON_ENCODE($challengeConsensus); ?>
                    


                    <div class="card shadow-lg mb-4">
                        <div class="card-body">
                            <!-- Top Row: Game Icon, Match Name, Game Mode, and Rules -->
                            <div class="row">
                                <!-- Game Image Section -->
                                <div class="col-4 col-md-3  text-center">
                                    <img src="<?= $thisLobby->game->icon->getFullPath(); ?>" alt="<?= $thisLobby->game->name; ?> Image" class="img-fluid rounded">
                                </div>
                                
                                <!-- Game Name for Mobile -->
                                <div class="col-8 d-md-none align-self-center">
                                    <h4 class="text-start mb-0"><?= htmlspecialchars($thisLobby->game->name); ?></h4>
                                </div>

                                <!-- Match Details Section -->
                                <div class="col-md-9 col-sm-10 col-xs-10 d-flex flex-column justify-content-center pt-3 pt-md-0">
                                    <!-- Game Name for Desktop -->
                                    <h4 class="d-none d-md-block mb-1 text-center text-md-start"><?= htmlspecialchars($thisLobby->game->name); ?></h4>
                                    
                                    <!-- Game Mode and Rules -->
                                    <p class="mb-1"><strong>Host:</strong> <?= $thisLobby->host->getAccountElement(); ?></p>
                                    <p class="mb-1"><strong>Game Mode:</strong> <?= htmlspecialchars($thisLobby->challenge->gamemode); ?> <i class="fa-solid fa-circle-question" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="House rules predefined by the lobby host."></i></p>
                                    <p class="mb-1"><strong>Rules:</strong> <?= htmlspecialchars($thisLobby->challenge->rules); ?></p>

                                    <!-- Action Buttons: Join, Edit Rules, Invite -->
                                    <div class="d-flex justify-content-center justify-content-md-start gap-2 mt-2">
                                        
                                        <?php if (!$thisLobby->challenge->hasJoined) : ?>
                                            <?= $thisLobby->getAcceptButtonElement(); ?>
                                        <?php else : ?>
                                            <?php if ($thisLobby->isHost()) : ?>
                                                <?php if ($thisLobby->hostCanClose()) : ?>
                                                    <button class="btn btn-danger btn-sm" onclick="OpenCloseLobbyModal();">Close Lobby</button>
                                                <?php endif; ?>

                                                <?php if ($thisLobby->canEditRules()) : ?>
                                                    <button class="btn btn-primary btn-sm" onclick="OpenManageLobbyModal();">Edit Rules</button>
                                                <?php endif; ?>

                                                <?php if ($thisLobby->canPublishChallenge()) : ?>
                                                    <button class="btn bg-ranked-1 btn-sm" onclick="OpenPublishLobby();">Publish Challenge</button>
                                                <?php endif; ?>

                                                <?php if ($thisLobby->canStartChallenge()) : ?>
                                                    <button class="btn bg-ranked-1 btn-sm" onclick="OpenStartLobby();">Start Challenge</button>
                                                <?php endif; ?>
                                            <?php else : ?>
                                                <?php if ($thisLobby->canLeave()) : ?>
                                                    <button class="btn btn-danger btn-sm" onclick="OpenLeaveLobbyModal();">Leave</button>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <?php if ($thisLobby->reviewStatus->published) : ?>
                                                <!-- Uncomment to enable invite functionality -->
                                                <!-- <button class="btn btn-primary btn-sm" onclick="OpenSelectAccountModal(null, 'InviteAccountToLobby')">Invite</button> -->
                                            <?php endif; ?>

                                            <?php if ($thisLobby->canReadyUp()) : ?>
                                                <button class="btn bg-ranked-1 btn-sm" onclick="OpenReadyUpLobby();">Ready Up</button>
                                            <?php endif; ?>

                                            <?php if ($thisLobby->canSelectCharacter()) : ?>
                                                <button class="btn btn-primary btn-sm" onclick="OpenCharacterSettings();">Select Character</button>
                                            <?php endif; ?>
                                            
                                            <?php if ($thisLobby->challenge->started) : ?>
                                                <button class="btn bg-ranked-1 btn-sm" onclick="OpenMatchReport();">Report Results</button>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="row">
                        <div class="col-12">
                            <nav>
                                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                    <button class="nav-link active" id="nav-team-tab" data-bs-toggle="tab" data-bs-target="#nav-team" type="button" role="tab" aria-controls="nav-team" aria-selected="true"><i class="fa-duotone fa-solid fa-swords"></i></button>
                                    <button class="nav-link" id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home" aria-selected="true"><i class="fa-solid fa-users"></i></button>
                                    <button class="nav-link" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile" type="button" role="tab" aria-controls="nav-profile" aria-selected="false"><i class="fa-solid fa-clock-rotate-left"></i></button>
                                </div>
                            </nav>
                            <div class="tab-content" id="nav-tabContent">
                                <div class="tab-pane fade show active" id="nav-team" role="tabpanel" aria-labelledby="nav-team-tab" tabindex="0">
                                    <h3>Team Setup</h3>
                                    <div class="row gy-4">
                                        <?php foreach ($teamPlayers as $teamName => $players): ?>
                                            <div class="col-md-6">
                                                <!-- Team Card -->
                                                <div class="card h-100">
                                                    <div class="card-header bg-tertiary text-white text-center">
                                                        <h5><?= htmlspecialchars($teamName) ?></h5> <!-- Team Name -->
                                                    </div>
                                                    <div class="card-body">
                                                        
                                                    <?php $teamPowerLevel = vChallengePlayer::getTeamPowerLevel($players); ?>


                                                        <div class="mb-3 text-center">
                                                            <h4 class="mb-0">Team Power Level: <span class="text-success"><?= $teamPowerLevel ?></span></h4>
                                                            <small class="text-muted">Calculated from team ELO ratings</small>
                                                        </div>
                                                        
                                                        <div class="d-flex flex-column gap-3">
                                                            <!-- Player Loop -->
                                                            <?php foreach ($players as $player): ?>
                                                                <?php
                                                                    $leagueDetails = $player->getLeagueDetails();
                                                                    $leagueName = $leagueDetails['name'];
                                                                    $leagueFlavor = $leagueDetails['flavor'];
                                                                    $borderColor = 'border-'.($player->ready ? 'success' : 'danger');
                                                                    $bgColor = 'bg-'.($player->ready ? 'success' : 'danger').'-subtle';
                                                                    if ($player->isRanked1())
                                                                    {
                                                                        $bgColor = "bg-ranked-1";
                                                                    }
                                                                ?>
                                                                <div class="d-flex align-items-center border p-2 rounded border-2 position-relative <?= $borderColor ?> <?= $bgColor ?>">
                                                                    <!-- Player Profile Picture -->
                                                                    <img src="<?= htmlspecialchars($player->account->getProfilePictureURL()) ?>" 
                                                                        alt="<?= htmlspecialchars($player->account->username) ?> Profile" 
                                                                        class="rounded-circle  border border-2 border-dark-subtle"" 
                                                                        style="width: 60px; height: 60px; object-fit: cover;">
                                                                    
                                                                    <!-- Player Details -->
                                                                    <div class="ms-3 flex-grow-1">
                                                                        <h6 class="mb-1">
                                                                            <?= $player->account->getAccountElement() ?> 
                                                                            <small class="text-muted">the <?= htmlspecialchars($player->account->getAccountTitle()) ?></small>
                                                                        </h6> <!-- Username with smaller title -->
                                                                        <small class="player-card-ranks text-muted">in 
                                                                            <span tabindex="0" 
                                                                                data-bs-toggle="popover" 
                                                                                data-bs-custom-class="custom-popover" 
                                                                                data-bs-trigger="focus" 
                                                                                data-bs-placement="top" 
                                                                                data-bs-title="<?= $leagueName ?>" 
                                                                                data-bs-content="<?= $leagueFlavor ?>">
                                                                                <span class="league" style="cursor: pointer;"><?= $leagueName ?></span> League
                                                                            </span>
                                                                            <?= $player->getRankElement() ?><?= $player->getEloElement() ?>


                                                                        </small> <!-- Game Title -->
                                                                        <div class="small">
                                                                            <strong>Pick:</strong> <?= htmlspecialchars($player->character) ?>
                                                                            <?php if ($player->pickedRandom): ?>
                                                                                <span class="badge bg-warning">Random</span>
                                                                            <?php endif; ?>
                                                                        </div>

                                                                    </div>

                                                                    <!-- Player Status -->
                                                                    <span class="badge bg-<?= $player->ready ? 'success' : 'danger' ?> position-absolute bottom-0 end-0" style="border-radius: var(--bs-border-radius) 0px 0px 0px;">
                                                                        <?= $player->ready ? 'Ready' : 'Not Ready' ?>
                                                                    </span>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                </div>
                                <div class="tab-pane fade" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab" tabindex="0">
                                    
                                <h3>Lobby</h3>
                                <?php
                                    $selectUserFormId = "lobby-participants";
                                    $selectUsersFormPageSize = 21;
                                    $selectUsersFilter = json_encode(["IsLobbyParticipant" => 1, "IsLobbyParticipantCTime" => $thisLobby->challenge->ctime, "IsLobbyParticipantCRand" => $thisLobby->challenge->crand]);
                                    require("php-components/select-user.php");

                                        ?>
                                </div>
                                <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab" tabindex="0">
                                    
                                <h3>Match History</h3>
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

        SearchForAccount("lobby-participants", 1, null, {"IsLobbyParticipant": 1, "IsLobbyParticipantCTime": "<?= $thisLobby->challenge->ctime ?>", "IsLobbyParticipantCRand": <?= $thisLobby->challenge->crand ?>});


        function InviteAccountToLobby(accountId)
        {
            console.log(accountId);
        }

        
            <?php if ($thisLobby->challenge->started && !$challengeConsensus->iVoted) : ?>
                
                $(document).ready(function() {
                    OpenMatchReport();
                });
            <?php endif; ?>
        </script>
    </body>

</html>
