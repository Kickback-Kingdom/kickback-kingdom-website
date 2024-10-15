<?php

use Kickback\Backend\Controllers\GameController;
use Kickback\Common\Version;
use Kickback\Services\Session;

$games = null;

$allGamesResp = GameController::getGames();
$games = $allGamesResp->data;

?>
<script>
    function OpenHostLobbyModal() {
        $("#hostLobbyModal").modal("show");
    }
  function togglePasswordVisibility() {
    var isPrivate = $('#privateSwitch').is(':checked');
    if (isPrivate) {
      $('#passwordField').show();
    } else {
      $('#passwordField').hide();
    }
  }
</script>
<?php
if (Session::isLoggedIn())
{
?>
<!--HOST LOBBY-->
<form method="POST">
    <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
    <div class="modal fade" id="hostLobbyModal" tabindex="-1" aria-labelledby="hostLobbyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" >Host Lobby</h5>        
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label for="lobbyName" class="form-label">Lobby Name</label>
                <input type="text" class="form-control" id="lobbyName" placeholder="Enter lobby name" value="<?= Session::getCurrentAccount()->username."'s Lobby"; ?>" autocomplete="off" name="host-lobby-name">
            </div>
            <div class="form-group mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-gamepad"></i></span>
                    <select class="form-select" name="host-lobby-game" id="host-lobby-game" aria-label="Default select example" required>
                        <option value="" selected>What game is being played?</option>
                        <?php 
                            if ($games !== null) {
                                foreach($games as $game) {
                                    if ($game->canRank) {
                                        echo '<option value="' . $game->crand . '">' . $game->name . '</option>';
                                    }
                                }
                            }
                        ?>
                    </select>
                </div>
                <div class="form-text" id="basic-addon4">Want to recommend a new game for Kickback Kingdom? Click <a href="<?= Version::urlBetaPrefix(); ?>/games.php?request-new-game=1">HERE</a></div>
            </div>
            <div class="mb-3">
                <label for="privateSwitch" class="form-check-label">Private Lobby</label>
                    <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="privateSwitch" onchange="togglePasswordVisibility()">
                </div>
            </div>
            <div class="mb-3" id="passwordField" style="display: none;">
                <label for="lobbyPassword" class="form-label">Password (only for private lobby)</label>
                <input type="password" class="form-control" id="lobbyPassword" placeholder="Enter password" autocomplete="new-password" name="host-lobby-password">
            </div>
        </div> 
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cancel</button>
            <input type="submit" class="btn bg-ranked-1" name="host-lobby-submit" value="Host"/>
        </div>
        </div>
    </div>
    </div>
</form>
<?php
}
?>