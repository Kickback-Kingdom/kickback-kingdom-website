<?php


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
                <input type="text" class="form-control" id="lobbyName" placeholder="Enter lobby name" value="<?= Kickback\Services\Session::getCurrentAccount()->username."'s Lobby"; ?>" autocomplete="off" name="hostLobbyName">
            </div>
            <div class="mb-3">
                <label for="privateSwitch" class="form-check-label">Private Lobby</label>
                    <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="privateSwitch" onchange="togglePasswordVisibility()">
                </div>
            </div>
            <div class="mb-3" id="passwordField" style="display: none;">
                <label for="lobbyPassword" class="form-label">Password (only for private lobby)</label>
                <input type="password" class="form-control" id="lobbyPassword" placeholder="Enter password" autocomplete="new-password" name="hostLobbyPassword">
            </div>
        </div> 
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cancel</button>
            <input type="submit" class="btn bg-ranked-1" name="hostLobbySubmit" value="Host"/>
        </div>
        </div>
    </div>
    </div>
</form>