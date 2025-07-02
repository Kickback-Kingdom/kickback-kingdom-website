<?php

use Kickback\Backend\Controllers\GameController;
use Kickback\Common\Version;
use Kickback\Services\Session;
use Kickback\Backend\Controllers\LobbyChallengeController;

?>
<script>
    function OpenManageLobbyModal() {
        $("#manageLobbyModal").modal("show");
    }
    function OpenCloseLobbyModal() {
        $("#closeLobbyModal").modal("show");
    }
    function OpenLeaveLobbyModal() {
        $("#leaveLobbyModal").modal("show");
    }
    function OpenAcceptLobby() {
        $("#acceptLobbyModal").modal("show");
    }
    function OpenPublishLobby() {
        $("#publishLobbyModal").modal("show");
    }
    function OpenReadyUpLobby() {
        $("#readyUpModal").modal("show");
    }
    function OpenStartLobby() {
        $("#startChallengeModal").modal("show");
    }
    function OpenStartLobby() {
        $("#startChallengeModal").modal("show");
    }
    function OpenCharacterSettings() {
        $("#characterSettingsModal").modal("show");
    }
    function OpenMatchReport() {
        $("#matchReportModal").modal("show");
    }
</script>
<?php
if ($thisLobby->isHost())
{
?>


<!-- START CHALLENGE MODAL -->
<form method="POST">
    <input type="hidden" name="form_token" value="<?= $_SESSION['form_token']; ?>">
    <input type="hidden" name="lobby_ctime" value="<?= $thisLobby->ctime; ?>">
    <input type="hidden" name="lobby_crand" value="<?= $thisLobby->crand; ?>">
    <input type="hidden" name="challenge_ctime" value="<?= $thisLobby->challenge->ctime; ?>">
    <input type="hidden" name="challenge_crand" value="<?= $thisLobby->challenge->crand; ?>">
    <div class="modal fade" id="startChallengeModal" tabindex="-1" aria-labelledby="startChallengeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <!-- Modal Header -->
                <div class="modal-header bg-ranked-1 text-white">
                    <h5 class="modal-title fw-bold" id="startChallengeModalLabel">
                        <i class="fa-solid fa-play"></i> Start Ranked Challenge
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Modal Body -->
                <div class="modal-body bg-light">
                    <div class="mb-4">
                        <p class="mb-1"><strong>Game Name:</strong></p>
                        <p class="text-primary fs-5"><?= htmlspecialchars($thisLobby->game->name); ?></p>
                    </div>
                    <div class="mb-4">
                        <p class="mb-1"><strong>Rules:</strong></p>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($thisLobby->challenge->rules)); ?></p>
                    </div>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i> Once started, the challenge cannot be modified. Please ensure all details are correct.
                    </div>
                </div>
                <!-- Modal Footer -->
                <div class="modal-footer bg-ranked-1">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <input type="submit" class="btn btn-primary" name="start-challenge-submit" value="Start Challenge" />
                </div>
            </div>
        </div>
    </div>
</form>

<!--MANAGE LOBBY-->
<form method="POST">
    <input type="hidden" name="form_token" value="<?= $_SESSION['form_token']; ?>">
    <input type="hidden" name="lobby_ctime" value="<?= $thisLobby->ctime; ?>">
    <input type="hidden" name="lobby_crand" value="<?= $thisLobby->crand; ?>">
    <input type="hidden" name="challenge_ctime" value="<?= $thisLobby->challenge->ctime; ?>">
    <input type="hidden" name="challenge_crand" value="<?= $thisLobby->challenge->crand; ?>">
    <div class="modal fade" id="manageLobbyModal" tabindex="-1" aria-labelledby="manageLobbyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" >Edit Ranked Challenge</h5>        
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="manage-lobby-gamemode" class="form-label">Game Mode</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-gamepad"></i></span>
                            <select class="form-select" name="manage-lobby-gamemode" id="manage-lobby-gamemode" aria-label="Default select example" required>
                                <option value="*" selected>Custom Game Mode</option>
                            </select>
                        </div>
                        <div class="form-text" id="basic-addon4">Want to recommend a new official game mode for <?= $thisLobby->game->name;?>? Click <a href="<?= Version::urlBetaPrefix(); ?>/games.php?request-new-game=1">HERE</a></div>
                    </div>
                    
                    <div class="mb-3" id="lobbyRulesField">
                        <label for="lobbyRules" class="form-label">Custom Rules</label>
                        <textarea type="text" class="form-control" id="lobbyRules" placeholder="Enter custom rules" autocomplete="new-rules" name="manage-lobby-rules" rows="3"><?= htmlspecialchars($thisLobby->challenge->rules); ?></textarea>
                    </div>
                </div> 
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cancel</button>
                    <input type="submit" class="btn bg-ranked-1" name="manage-lobby-submit" value="Save"/>
                </div>
            </div>
        </div>
    </div>
</form>


<!--Close LOBBY-->
<form method="POST">
    <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
    <input type="hidden" name="lobby_ctime" value="<?= $thisLobby->ctime; ?>">
    <input type="hidden" name="lobby_crand" value="<?= $thisLobby->crand; ?>">
    <input type="hidden" name="challenge_ctime" value="<?= $thisLobby->challenge->ctime; ?>">
    <input type="hidden" name="challenge_crand" value="<?= $thisLobby->challenge->crand; ?>">
    <div class="modal fade" id="closeLobbyModal" tabindex="-1" aria-labelledby="closeLobbyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" >Are you sure?</h5>        
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This cannot be undone.</p>
                </div> 
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cancel</button>
                    <input type="submit" class="btn bg-ranked-1" name="close-lobby-submit" value="Close Lobby"/>
                </div>
            </div>
        </div>
    </div>
</form>



<!--publish LOBBY-->
<form method="POST">
    <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
    <input type="hidden" name="lobby_ctime" value="<?= $thisLobby->ctime; ?>">
    <input type="hidden" name="lobby_crand" value="<?= $thisLobby->crand; ?>">
    <input type="hidden" name="challenge_ctime" value="<?= $thisLobby->challenge->ctime; ?>">
    <input type="hidden" name="challenge_crand" value="<?= $thisLobby->challenge->crand; ?>">
    <div class="modal fade" id="publishLobbyModal" tabindex="-1" aria-labelledby="publishLobbyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header bg-ranked-1 text-dark">
                    <h5 class="modal-title fw-bold" id="publishLobbyModalLabel">
                    <i class="fa-solid fa-trophy"></i> Publish Challenge Confirmation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Modal Body -->
                <div class="modal-body bg-light">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i> <strong>Important:</strong> Once you publish this challenge, the <strong>rules</strong> and <strong>game mode</strong> will be locked and cannot be changed.
                    </div>
                    <p class="mb-2">Please review the challenge details carefully before publishing:</p>
                    <ul class="list-group mb-3">
                        <li class="list-group-item">
                            <strong>Game Mode:</strong> <span class="text-primary"><?= htmlspecialchars($thisLobby->challenge->gamemode); ?></span>
                        </li>
                        <li class="list-group-item">
                            <strong>Rules:</strong> <span class="text-secondary"><?= nl2br(htmlspecialchars($thisLobby->challenge->rules)); ?></span>
                        </li>
                    </ul>
                    <p>Are you sure you are ready to publish this challenge?</p>
                </div>
                <!-- Modal Footer -->
                <div class="modal-footer bg-ranked-1">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                         Cancel
                    </button>
                    <input type="submit" class="btn btn-primary" name="publish-lobby-submit" value="Publish Challenge" />
                </div>
            </div>
        </div>
    </div>
</form>



<?php
} else {
    ?>

<!--Leave LOBBY-->
<form method="POST">
    <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
    <input type="hidden" name="lobby_ctime" value="<?= $thisLobby->ctime; ?>">
    <input type="hidden" name="lobby_crand" value="<?= $thisLobby->crand; ?>">
    <input type="hidden" name="challenge_ctime" value="<?= $thisLobby->challenge->ctime; ?>">
    <input type="hidden" name="challenge_crand" value="<?= $thisLobby->challenge->crand; ?>">
    <div class="modal fade" id="leaveLobbyModal" tabindex="-1" aria-labelledby="leaveLobbyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" >Are you sure?</h5>        
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This cannot be undone.</p>
                </div> 
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cancel</button>
                    <input type="submit" class="btn bg-ranked-1" name="leave-lobby-submit" value="Leave Lobby"/>
                </div>
            </div>
        </div>
    </div>
</form>
    <?php
}

if (Session::isLoggedIn())
{
    ?>

<!-- Ready Up Modal -->
<form method="POST">
    <input type="hidden" name="form_token" value="<?= $_SESSION['form_token']; ?>">
    <input type="hidden" name="lobby_ctime" value="<?= $thisLobby->ctime; ?>">
    <input type="hidden" name="lobby_crand" value="<?= $thisLobby->crand; ?>">
    <input type="hidden" name="challenge_ctime" value="<?= $thisLobby->challenge->ctime; ?>">
    <input type="hidden" name="challenge_crand" value="<?= $thisLobby->challenge->crand; ?>">
    <div class="modal fade" id="readyUpModal" tabindex="-1" aria-labelledby="readyUpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <!-- Modal Header -->
                <div class="modal-header bg-ranked-1 text-white">
                    <h5 class="modal-title fw-bold" id="readyUpModalLabel">
                    <i class="fa-regular fa-thumbs-up"></i> Ready Up Confirmation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Modal Body -->
                <div class="modal-body bg-light">
                    <div class="alert alert-info">
                    <i class="fa-solid fa-circle-info"></i> <strong>Notice:</strong> Once you click "Ready Up," this action cannot be undone. Make sure you are fully prepared before proceeding.
                    </div>
                    <p>By readying up, you confirm that:</p>
                    <ul class="list-group mb-3">
                        <li class="list-group-item">
                        <i class="fa-regular fa-square-check"></i> You are ready to participate in the challenge.
                        </li>
                        <li class="list-group-item">
                        <i class="fa-regular fa-square-check"></i> You understand that this action is final and you cannot unready.
                        </li>
                        <li class="list-group-item">
                        <i class="fa-regular fa-square-check"></i> You agree to follow the rules and game mode specified for this challenge.
                        </li>
                    </ul>
                    <p>Are you sure you want to ready up?</p>
                </div>
                <!-- Modal Footer -->
                <div class="modal-footer bg-ranked-1">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                     Cancel
                    </button>
                    <input type="submit" class="btn btn-primary" name="ready-up-submit" value="Ready Up" />
                </div>
            </div>
        </div>
    </div>
</form>

<?php 

$availableCharacters = GameController::getDistinctCharacters($thisLobby->game)->data;
$myChallengePlayer = $thisLobby->challenge->getMyPlayer();

// Check if $myChallengePlayer is null before accessing its properties
$characterExistsAlready = false;
$pickedRandom = false;
$pickedCharacter = '';
$myTeam = '';

if ($myChallengePlayer) {
    $characterExistsAlready = in_array($myChallengePlayer->character, $availableCharacters);
    $pickedRandom = $myChallengePlayer->pickedRandom;
    $pickedCharacter = $myChallengePlayer->character;
    $myTeam = htmlspecialchars($myChallengePlayer->teamName);
} else {
    // Optionally log or handle the case where $myChallengePlayer is null
    error_log('No challenge player found for the current user.');
}

?>

<form method="POST">
    <input type="hidden" name="form_token" value="<?= $_SESSION['form_token']; ?>">
    <input type="hidden" name="lobby_ctime" value="<?= $thisLobby->ctime; ?>">
    <input type="hidden" name="lobby_crand" value="<?= $thisLobby->crand; ?>">
    <input type="hidden" name="challenge_ctime" value="<?= $thisLobby->challenge->ctime; ?>">
    <input type="hidden" name="challenge_crand" value="<?= $thisLobby->challenge->crand; ?>">
    <div class="modal fade" id="characterSettingsModal" tabindex="-1" aria-labelledby="characterSettingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <!-- Modal Header -->
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title fw-bold" id="characterSettingsModalLabel">
                        <i class="fa-solid fa-user"></i> Character and Match Settings
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Modal Body -->
                <div class="modal-body bg-light">
                    <!-- Random Character Toggle -->
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="randomCharacterToggle" name="player-settings-character-random" onchange="toggleCharacterSelection(this)" <?= ($pickedRandom ? "checked" : ""); ?>>
                        <label class="form-check-label" for="randomCharacterToggle">Pick Random Character</label>
                    </div>
                    <!-- Character Selector -->
                    <div id="characterSelector" class="mb-4"  style="<?= ($pickedRandom ? 'display: none;' : ''); ?>">
                        <label for="characterDropdown" class="form-label"><strong>Select Character:</strong></label>
                        <select class="form-select" id="characterDropdown" name="player-settings-character" aria-label="Select a character">
                            <option value="">Choose...</option>
                            <?php foreach ($availableCharacters as $character): ?>
                            <option value="<?= htmlspecialchars($character) ?>" <?= ($character == $pickedCharacter ? "selected": ""); ?>><?= htmlspecialchars($character) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mt-3">
                            <label for="customCharacterName" class="form-label"><strong>Or Add Custom Character:</strong></label>
                            <input type="text" class="form-control" id="customCharacterName" name="player-settings-character-custom" placeholder="Enter custom character name" value="<?= (!$characterExistsAlready ? $pickedCharacter : ""); ?>">
                            <div id="customCharacterFeedback" class="form-text text-muted">Custom character name will be added if provided.</div>
                        </div>
                    </div>
                </div>
                <!-- Modal Footer -->
                <div class="modal-footer bg-info">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <input type="submit" class="btn btn-primary" name="player-settings-submit" value="Save Settings" />
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    // Disable dropdown and custom field if random is checked
    /*function toggleCharacterSelection(toggle) {
        const dropdown = document.getElementById('characterDropdown');
        const customField = document.getElementById('customCharacterName');
        dropdown.disabled = toggle.checked;
        customField.disabled = toggle.checked;
    }*/

    function toggleCharacterSelection(toggle) {
        const characterSelector = document.getElementById('characterSelector');
        if (toggle.checked) {
            characterSelector.style.display = 'none';
        } else {
            characterSelector.style.display = 'block';
        }
    }

    // Real-time feedback for custom name input
    document.getElementById('customCharacterName').addEventListener('input', function() {
        const feedback = document.getElementById('customCharacterFeedback');
        feedback.textContent = this.value.trim() 
            ? 'Custom character name will be added if valid.'
            : 'Custom character name will be ignored if left empty.';
    });

    
</script>


<?php
    $challengeConsensusResp = LobbyChallengeController::getChallengeConsensus($thisLobby->challenge);
    $challengeConsensus = $challengeConsensusResp->success ? $challengeConsensusResp->data : null;
?>
<!-- MATCH REPORT FORM -->
<form method="POST">
    <input type="hidden" name="form_token" value="<?= $_SESSION['form_token']; ?>">
    <input type="hidden" name="lobby_ctime" value="<?= $thisLobby->ctime; ?>">
    <input type="hidden" name="lobby_crand" value="<?= $thisLobby->crand; ?>">
    <input type="hidden" name="challenge_ctime" value="<?= $thisLobby->challenge->ctime; ?>">
    <input type="hidden" name="challenge_crand" value="<?= $thisLobby->challenge->crand; ?>">
    <div class="modal fade" id="matchReportModal" tabindex="-1" aria-labelledby="matchReportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <!-- Modal Header -->
                <div class="modal-header bg-ranked-1 text-white">
                    <h5 class="modal-title fw-bold" id="matchReportModalLabel">
                        <i class="fa-solid fa-clipboard-list"></i> Match Report
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Modal Body -->
                <div class="modal-body bg-light">
                    <!-- Quick Result Buttons -->
                    <div class="text-center">
                        <button id="btnWin" type="button" class="btn btn-lg btn-outline-success me-3 px-4 py-3 result-button" onclick="setResult('win', '<?= $myTeam ?>')">
                            <i class="fa-solid fa-trophy me-2"></i> <strong>I Won</strong>
                        </button>
                        <button id="btnLose" type="button" class="btn btn-lg btn-outline-danger px-4 py-3 result-button" onclick="setResult('loss', '<?= $myTeam ?>')">
                            <i class="fa-solid fa-thumbs-down me-2"></i> <strong>I Lost</strong>
                        </button>
                    </div>


                    <!-- Hidden Input for Result -->
                    <input type="hidden" id="matchResult" name="match_result" value="">

                    <!-- Consensus Information -->
                    <?php if ($challengeConsensus && $challengeConsensus->hasWinningTeamConsensus()): ?>
                        <div class="alert alert-info mt-4">
                            <strong>Consensus:</strong><br>
                            Winning Team: <?= htmlspecialchars($challengeConsensus->winningTeam) ?> (<?= $challengeConsensus->winningTeamPercentage ?>%)
                            <br>Vote Void: <?= $challengeConsensus->hasVoteVoidConsensus() ? $challengeConsensus->voteVoidPercentage . '%' : '0%' ?>
                        </div>
                    <?php endif; ?>


                    <!-- Additional Details (Optional) -->
                    <div id="additionalDetails" style="display: none;" class="mt-4">
                        <div class="mb-4">
                            <label for="winningTeam" class="form-label"><strong>Winning Team:</strong></label>
                            <select class="form-select" id="winningTeam" name="winning_team" onchange="updateButtonStyles('<?= $myTeam ?>')">
                                <option value="">Select...</option>
                                <?php foreach ($teamPlayers as $teamName => $players): ?>
                                    <option value="<?= htmlspecialchars($teamName) ?>" <?= $challengeConsensus && $challengeConsensus->winningTeam === $teamName ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($teamName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($challengeConsensus): ?>
                                <small class="text-muted">Consensus: <?= htmlspecialchars($challengeConsensus->winningTeam ?? 'None') ?> (<?= $challengeConsensus->winningTeamPercentage ?>%)</small>
                            <?php endif; ?>
                        </div>

                        <!-- Per-Player Details -->
                        <?php if ($thisLobby->game->allowsCharacterSelection): ?>
                            <div class="accordion" id="teamAccordion">
                                <?php foreach ($teamPlayers as $teamName => $players): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading-<?= htmlspecialchars($teamName) ?>">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= htmlspecialchars($teamName) ?>" aria-expanded="false" aria-controls="collapse-<?= htmlspecialchars($teamName) ?>">
                                                Team: <?= htmlspecialchars($teamName) ?>
                                            </button>
                                        </h2>
                                        <div id="collapse-<?= htmlspecialchars($teamName) ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?= htmlspecialchars($teamName) ?>" data-bs-parent="#teamAccordion">
                                            <div class="accordion-body">
                                                <?php foreach ($players as $player): ?>
                                                    <div class="mb-3 border-bottom pb-3">
                                                        <!-- Profile Picture and Player Name -->
                                                        <div class="d-flex align-items-center mb-3">
                                                            <img src="<?= htmlspecialchars($player->account->profilePictureURL()) ?>" alt="<?= htmlspecialchars($player->account->username) ?> Profile" class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                            <?= $player->account->getAccountElement(); ?>
                                                        </div>
                                                        <!-- Player Details -->
                                                        <?php 
                                                        $playerConsensus = $challengeConsensus->playerConsensusDetails[$player->account->crand] ?? null; 
                                                        ?>
                                                        <label for="random_pick_<?= htmlspecialchars($player->account->crand) ?>" class="form-label mt-3">Picked Random Character:</label>
                                                        <select 
                                                            class="form-select" 
                                                            id="random_pick_<?= htmlspecialchars($player->account->crand) ?>" 
                                                            name="player_random[<?= htmlspecialchars($teamName) ?>][<?= htmlspecialchars($player->account->crand) ?>]">
                                                            <option value="">Not Sure</option>
                                                            <option value="1" <?= $playerConsensus && $playerConsensus->pickedRandom === true ? 'selected' : '' ?>>Yes</option>
                                                            <option value="0" <?= $playerConsensus && $playerConsensus->pickedRandom === false ? 'selected' : '' ?>>No</option>
                                                        </select>
                                                        <?php if ($playerConsensus && $playerConsensus->pickedRandomHasConsensus()): ?>
                                                            <small class="text-muted d-block">Consensus: <?= $playerConsensus->pickedRandom === true ? 'Yes' : 'No' ?> (<?= $playerConsensus->pickedRandomPercentage ?>%)</small>
                                                        <?php endif; ?>

                                                        <label for="character_<?= htmlspecialchars($player->account->crand) ?>" class="form-label mt-3">Character:</label>
                                                        <select class="form-select" id="character_<?= htmlspecialchars($player->account->crand) ?>" name="player_characters[<?= htmlspecialchars($teamName) ?>][<?= htmlspecialchars($player->account->crand) ?>]">
                                                            <option value="">Choose...</option>
                                                            <?php foreach ($availableCharacters as $character): ?>
                                                                <option value="<?= htmlspecialchars($character) ?>" <?= $playerConsensus && $playerConsensus->character === $character ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($character) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <?php if ($playerConsensus && $playerConsensus->characterHasConsensus()): ?>
                                                            <small class="text-muted d-block">Consensus: <?= htmlspecialchars($playerConsensus->character ?? 'None') ?> (<?= $playerConsensus->characterPercentage ?>%)</small>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
                <!-- Modal Footer -->
                <div class="modal-footer bg-ranked-1">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <input type="submit" class="btn btn-primary" name="submit_match_report" value="Submit Report" />
                </div>
            </div>
        </div>
    </div>
</form>


<script>
    function setResult(result, myTeam = null) {
    const matchResultInput = document.getElementById('matchResult');
    const additionalDetails = document.getElementById('additionalDetails');
    const winningTeamSelect = document.getElementById('winningTeam');

    // Update the match result value
    matchResultInput.value = result;

    // Show additional details section
    additionalDetails.style.display = 'block';

    if (result === 'loss') {
        // Auto-select the opposing team
        let selectedTeam = '';
        const teamOptions = Array.from(winningTeamSelect.options);

        for (const option of teamOptions) {
            if (option.value && option.value !== myTeam) {
                selectedTeam = option.value;
                break;
            }
        }

        winningTeamSelect.value = selectedTeam;
        console.log(`Loss: Automatically selected the opposing team: ${selectedTeam}`);
    } else if (result === 'win' && myTeam) {
        // Auto-select the user's team if "I Won"
        winningTeamSelect.value = myTeam;
        console.log(`Win: Automatically selected my team: ${myTeam}`);
    } else {
        // Reset the dropdown
        winningTeamSelect.value = '';
        console.log('Reset: No team selected.');
    }

    updateButtonStyles(myTeam);
}

function updateButtonStyles(myTeam) {
    const winningTeamSelect = document.getElementById('winningTeam');
    const selectedTeam = winningTeamSelect.value;

    const btnWin = document.getElementById('btnWin');
    const btnLose = document.getElementById('btnLose');

    // Reset both buttons to outline styles first
    btnWin.classList.remove('btn-success');
    btnWin.classList.add('btn-outline-success');
    btnLose.classList.remove('btn-danger');
    btnLose.classList.add('btn-outline-danger');

    // Update styles based on the selected team
    if (selectedTeam === myTeam) {
        // User's team is the winner
        btnWin.classList.remove('btn-outline-success');
        btnWin.classList.add('btn-success');
    } else if (selectedTeam !== "" && selectedTeam !== myTeam) {
        // Opposing team is the winner
        btnLose.classList.remove('btn-outline-danger');
        btnLose.classList.add('btn-danger');
    }
}



</script>


<!--accept LOBBY-->
<form method="POST">
    <input type="hidden" name="form_token" value="<?= $_SESSION['form_token']; ?>">
    <input type="hidden" name="lobby_ctime" value="<?= $thisLobby->ctime; ?>">
    <input type="hidden" name="lobby_crand" value="<?= $thisLobby->crand; ?>">
    <input type="hidden" name="challenge_ctime" value="<?= $thisLobby->challenge->ctime; ?>">
    <input type="hidden" name="challenge_crand" value="<?= $thisLobby->challenge->crand; ?>">
    <div class="modal fade" id="acceptLobbyModal" tabindex="-1" aria-labelledby="acceptLobbyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <!-- Modal Header -->
                <div class="modal-header bg-ranked-1 text-white">
                    <h5 class="modal-title fw-bold" id="acceptLobbyModalLabel">
                    <i class="fa-solid fa-trophy"></i> Ranked Challenge Terms
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Modal Body -->
                <div class="modal-body bg-light">
                    <!-- Game Mode and Rules Section -->
                    <div class="mb-4 p-3 border rounded bg-white shadow-sm">
                        <p class="mb-1"><strong>Game Mode:</strong></p>
                        <p class="text-primary fs-5"><?= htmlspecialchars($thisLobby->challenge->gamemode); ?></p>
                        <hr>
                        <p class="mb-1"><strong>Rules:</strong></p>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($thisLobby->challenge->rules)); ?></p>
                    </div>

                    <!-- Terms and Conditions -->
                    <p class="text-muted">By accepting these terms, you agree to the following:</p>
                    <p class="d-inline-flex gap-1">
                        <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                        <i class="fa-regular fa-rectangle-list"></i>  View Challenge Terms
                        </button>
                    </p>
                    <div class="collapse" id="collapseExample">
                        <ul class="list-group list-group-flush mb-4">
                            <li class="list-group-item">
                                <strong>Auto-Forfeit:</strong> Failure to report scores within the allotted time after the match ends will result in an automatic forfeiture.
                            </li>
                            <li class="list-group-item">
                                <strong>Dispute Resolution:</strong> If challenge results are disputed, the <strong>Arbiter of Truth</strong> will review the evidence and make the final decision. This decision is binding and cannot be appealed.
                            </li>
                            <li class="list-group-item">
                                <strong>Voiding:</strong> The lobby may vote for a void to negate the results of the match. Majority participants must agree for this to take effect and the match is subject to review.
                            </li>
                            <li class="list-group-item">
                                <strong>Leaving the Lobby:</strong> You may leave the lobby at any time <em>before</em> readying up. If you ready up for the current match, you must complete the match. You can leave the lobby again between matches.
                            </li>
                        </ul>
                    </div>
                </div>
                <!-- Modal Footer -->
                <div class="modal-footer bg-ranked-1">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <input type="submit" class="btn btn-primary" name="accept-lobby-submit" value="Accept Terms" />
                </div>
            </div>
        </div>
    </div>
</form>


<?php
}

?>
