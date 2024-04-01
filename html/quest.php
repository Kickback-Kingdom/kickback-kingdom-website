<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

$hasError = false;
$hasSuccess = false;
$successMessage = "";
$errorMessage = "";
$newPost = false;
if (isset($_GET['id']))
{

    $id = $_GET['id'];
    $questResp = GetQuestById($id);
}

if (isset($_GET['locator'])){
        
    $name = $_GET['locator'];
    $questResp = GetQuestByLocator($name);
}

if (isset($_GET['new']))
{
    $name = "New Quest";
    $newPost = true;
    $questResp = InsertNewQuest();
}


//print_r($questResp);
if (!$questResp->Success)
{
    unset($questResp);
}
if (!isset($questResp))
{
    header("Location: adventurers-guild.php");
}

$thisQuest = $questResp->Data;


if ($thisQuest["content_id"] != null)
{
    $contentResp = GetContentDataById($thisQuest["content_id"],"QUEST",$thisQuest["locator"]);
    $pageContent = $contentResp->Data;
}


$questRewardsResp = GetQuestRewardsByQuestId($thisQuest["Id"]);
if (!$questRewardsResp->Success)
{
    $hasError = true;
    $errorMessage = $questRewardsResp->Message;
}

$questRewards = $questRewardsResp->Data;

$itemInfos = [];
foreach ($questRewards as $questRewardsItem) {
    array_push($itemInfos, ConvertIntoItemInformation($questRewardsItem));
}
$itemInformationJSON = json_encode($itemInfos);

$questRewardsByCategory = [];
foreach ($questRewards as $questReward) {
    $questRewardsByCategory[$questReward['category']][] = $questReward;
}



$activeTab = 'active';
$activeTabPage = 'active show';
$unusedTickets = 0;
if ($thisQuest["raffle_id"] != null)
{
    CheckIfTimeForRaffleWinner($thisQuest);
    //$raffleWinnerResp = ChooseRaffleWinner($thisQuest["raffle_id"]);
    //$raffleWinnerResp->Success)
    if (IsLoggedIn())
    {

        $unusedTicketsResp = GetTotalUnusedRaffleTickets($GLOBALS['account']['Id']);
        $unusedTickets = $unusedTicketsResp->Data;
    }
}

if (isset($_POST["submit-raffle"]))
{
    $tokenResponse = UseFormToken();

    if ($tokenResponse->Success) {

        $ticketsToSubmit = intval($_POST["tickets"]);

        if ($ticketsToSubmit <= $unusedTickets)
        {
            $ticketsSubmitted = 0;
            for ($x = 0; $x < $ticketsToSubmit; $x++) {
                $raffleResp = SubmitRaffleTicket($GLOBALS['account']['Id'], $thisQuest["raffle_id"]);
                if ($raffleResp->Success)
                {
                    $ticketsSubmitted++;
                }
            }

            if ($thisQuest["published"]==1)
            {

                DiscordWebHook(GetRandomGreeting().', '.$GLOBALS['account']['Username'].' just submitted a raffle ticket to the '.$thisQuest['name'].' quest.');
            }

            $hasSuccess = true;
            $successMessage = "Successfully submitted ".$ticketsSubmitted." raffle tickets.";
        }
        else
        {

            $hasError = true;
            $errorMessage = "You only have ".$unusedTickets." Ticket(s). Cannot submit ".$ticketsToSubmit." ticket(s)";
        }
    } else {
        $hasError = true;
        $errorMessage = $tokenResponse->Message;
    }
    
    
}

if (isset($_POST['submit-apply']))
{
    $applyResp = ApplyOrRegisterForQuest($GLOBALS['account']['Id'],$thisQuest["Id"]);
    if ($applyResp->Success)
    {
        $hasSuccess = true;
        $successMessage = "Successfully signed up for quest!";
    }
    else
    {

        $hasError = true;
        $errorMessage = "Failed to sign up for quest!";
    }
}

$kk_crypt_key_quest_id = \Kickback\Config\ServiceCredentials::get("crypt_key_quest_id");
$crypt = new IDCrypt($kk_crypt_key_quest_id);
$qId = urlencode($crypt->encrypt($thisQuest["Id"]));
//$qId = urlencode(encode_id($thisQuest["Id"]));
unset($kk_crypt_key_quest_id);

$redirectURL = $urlPrefixBeta."/login.php?redirect=".urlencode("q/".$thisQuest["locator"]).'&wq='.$qId;
//echo $redirectURL;
//echo "<br/>";
//echo decode_id(urldecode($qId));


$questApplicantsResp = GetQuestApplicants($thisQuest["Id"]);
$questApplicants = $questApplicantsResp->Data;

$callToAction = "...";
if ($thisQuest["req_apply"] == 1)
{
    $callToAction = "Apply For Quest";
}
else
{
    $callToAction = "Register For Quest";

}
if ($thisQuest["raffle_id"] != null)
{
    $callToAction = "Enter Raffle";
}
$thisQuestEndDate = new DateTime($thisQuest["end_date"]);
$currentDateTime = new DateTime();

$thisQuestHasEndDate = $thisQuest["end_date"] != null;
$thisQuestPassed = ($thisQuestEndDate < $currentDateTime) && $thisQuestEndDate;
$thisQuestIsRanked = ($thisQuest["tournament_id"] != null);

$showRaffleTab = ($thisQuest["raffle_id"] != null);
$showRewardsTab = (count($questRewards)>0);
$showBracketTab = ($thisQuest["hasBracket"]==1);
$showResultsTab = ($thisQuestIsRanked && $thisQuestPassed && !$showBracketTab);
$showParticipantsTab = (($thisQuestPassed)||($showRaffleTab));
$showApplicantsTab = ($thisQuest["raffle_id"] == null);

$games = null;

if (CanEditQuest($thisQuest))
{
    $allGamesResp = GetAllGames();
    $games = $allGamesResp->Data;
}
//$feedCardDateBasic = date_format($feedCardDate,"M j, Y");
//$feedCardDateDetailed = date_format($feedCardDate,"M j, Y H:i:s");

if ($thisQuest["raffle_id"] != null)
{
    
    $GetRaffleParticipantsResp = GetRaffleParticipants($thisQuest["raffle_id"]);
    $raffleParticipants = $GetRaffleParticipantsResp->Data;

}
?>

<!DOCTYPE html>
<html lang="en">


<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    <style>
        .winner-announcement {
    margin-bottom: 30px;
}

.winner-title {
    font-size: 2.5rem;
    color: #4CAF50;
}

.text-highlight {
    color: #f77f00;
    font-weight: bold;
}

.countdown-timer h2 {
    margin-bottom: 20px;
}

.timer {
    display: flex;
    justify-content: center;
}

.time-segment span, .time-segment small {
    display: block;
    color: #333;
}

.time-segment span {
    font-size: 2rem;
    font-weight: bold;
}

.time-segment small {
    font-size: 1rem;
    color: #555;
}

.raffle-jar img {
    max-width: 100%;
    height: auto;
    margin-top: 2rem;
}

/* Base styles */
.time-segment {
    border-radius: 10px;
    padding: 1rem;
    margin: 0.5rem;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    flex: 1 0 20%; /* Adjust the basis to 20% for each segment */
    text-align: center; /* Center text alignment */
}

.time-segment span, .time-segment small {
    display: block;
    color: #333;
}

.time-segment span {
    font-size: 2rem;
    font-weight: bold;
}

.time-segment small {
    font-size: 1rem;
    color: #555;
}

/* Responsive adjustments */
@media (max-width: 767px) {
    .timer {
        flex-wrap: wrap; /* Allow the timer to wrap on small screens */
    }

    .time-segment {
        /*flex: 1 0 40%; /* Increase the basis to 40% for better spacing */
        margin-bottom: 10px; /* Add more space between the rows */
    }

    /* Adjust font size for smaller screens */
    .time-segment span {
        font-size: 1.5rem;
    }

    .time-segment small {
        font-size: 0.8rem;
    }
}

@media (max-width: 400px) {
    .time-segment {
        /*flex: 1 0 100%; /* Each segment takes full width */
    }

    /* Further adjust font size for very small screens */
    .time-segment span {
        font-size: 1.2rem;
    }
}

.winner-title {
    margin-bottom: 20px; /* Adds space between the title and the player card */
    font-size: 2.5rem;
}
.animate-raffle-jar {
    animation: pulse;
    animation-duration: 1s;
}
.animate-winner {
    animation: tada;
    animation-duration: 1s;
}

    </style>
    <?php 
    
    require("php-components/base-page-components.php"); 
    
    
    ?>

    <!--TOP BANNER-->
    <div class="d-none d-md-block w-100 ratio" style="--bs-aspect-ratio: 26%; margin-top: 56px">

        <img src="/assets/media/<?php echo $thisQuest["imagePath"]; ?>" class="" />

    </div>
    <div class="d-block d-md-none w-100 ratio" style="margin-top: 56px; --bs-aspect-ratio: 46.3%;">

        <img src="/assets/media/<?php echo $thisQuest["imagePath_mobile"]; ?>" />

    </div>

    <!--MAIN CONTENT-->
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
    
        <?php if ($hasError || $hasSuccess) {?>
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
        <?php } ?>
        <div class="row">
            <div class="col-12 col-xl-9">
                
                
                <?php 
                
                
                $activePageName = $thisQuest['name'];
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>

                <div class="row">
                    <div class="col-12">
                        <button id="btn-action" class="btn float-end bg-ranked-1" type="button" data-bs-toggle="modal" data-bs-target="#actionModal" data-bs-original-title="" title="">...</button>
                        <?php 
                            if (!$thisQuestPassed && $thisQuest["published"] == true)
                            {
                                if (IsLoggedIn())
                                {
                                    ?>
                                    <script>document.getElementById("btn-action").innerText = "<?php echo $callToAction; ?>";</script>
                                
                                    <?php
                                    
                                    require("php-components/quest-signup.php");
                                }
                                else
                                {
                                    ?>


                                    <script>
                                    document.getElementById("btn-action").innerText = "<?php echo $callToAction; ?>";
                                    document.getElementById("btn-action").addEventListener("click", function() 
                                    {  
                                        SetShowActionModal();
                                        window.location.href = "<?php echo $redirectURL; ?>";
                                    });
                                    </script>
                                    <?php
                                }
                            }
                            else
                            {
                                ?>
                                <script>
                                    document.getElementById("btn-action").style.display="none";
                                </script>
                                <?php
                            }
                            

                        ?>
                        
                        <h5 class="quest-hosted-by">Hosted by 
                            <a class="username" href="<?php echo $urlPrefixBeta; ?>/u/<?php echo $thisQuest['host_name'];?>"><?php echo $thisQuest['host_name'];?></a>
                            <?php if ($thisQuest['host_name_2'] != null) { ?> and <a class="username" href="<?php echo $urlPrefixBeta; ?>/u/<?php echo $thisQuest['host_name_2']; ?>"><?php echo $thisQuest['host_name_2'];?></a><?php } ?>
                            <?php if ($thisQuestHasEndDate) { ?>at <span  id="quest_time" class="date" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?php echo date_format(date_create($thisQuest["end_date"]),"M j, Y H:i:s"); ?> UTC"><?php echo date_format(date_create($thisQuest["end_date"]),"M j, Y H:i:s"); ?> UTC</span><?php } else { ?>until completed<?php } ?>
                        </h5>
                        
                
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                    
                        <?php if (CanEditQuest($thisQuest)) { ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card mb-3">
            
                                    <div class="card-header bg-ranked-1">
                                        <h5 class="mb-0">Welcome back, Quest Giver <?php echo $_SESSION["account"]["Username"]; ?>. What would you like to do?</h5>
                                    </div>
                                    <div class="card-body">
                                        <button type="button" class="btn btn-primary" onclick="OpenModalEditQuestRewards()">Edit Rewards</button>
                                        <button type="button" class="btn btn-primary" onclick="OpenModalEditQuestImages()">Edit Banner & Icon</button>
                                        <button type="button" class="btn btn-primary" onclick="OpenModalEditQuestOptions()">Quest Options</button>
                                        <button type="button" class="btn btn-primary" onclick="OpenModalPublishQuest()">Publish Quest</button>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="modal modal-lg fade" id="modalEditQuestRewards" tabindex="-1" aria-labelledby="modalEditQuestRewardsLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5">Edit Quest Rewards</h1>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn bg-ranked-1" onclick="">Apply changes</button>
                                </div>
                                </div>
                            </div>
                        </div>
                        <form method="POST">
                            <input type="hidden" value="<?php echo $thisQuest["Id"]; ?>" name="edit-quest-id" />
                            <input type="hidden" value="<?php echo $thisQuest["image_id"]; ?>" name="edit-quest-images-desktop-banner-id" id="edit-quest-images-desktop-banner-id"/>
                            <input type="hidden" value="<?php echo $thisQuest["image_id_mobile"]; ?>" name="edit-quest-images-mobile-banner-id" id="edit-quest-images-mobile-banner-id" />
                            <input type="hidden" value="<?php echo $thisQuest["image_id_icon"]; ?>" name="edit-quest-images-icon-id" id="edit-quest-images-icon-id"/>
                            <div class="modal modal-lg fade" id="modalEditQuestImages" tabindex="-1" aria-labelledby="modalEditQuestImagesLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5">Edit Quest Images</h1>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <!--DESKTOP TOP BANNER-->
                                            <h3 class="display-6">Desktop Banner<button type="button" class="btn btn-primary float-end" onclick="OpenSelectMediaModal('modalEditQuestImages','edit-quest-images-desktop-banner-img','edit-quest-images-desktop-banner-id')">Select Media</button></h3>
                                            <div class="w-100 ratio" style="--bs-aspect-ratio: 26%;">

                                                <img src="/assets/media/<?php echo $thisQuest["imagePath"]; ?>" class="" id="edit-quest-images-desktop-banner-img"/>

                                            </div>
                                            <!--MOBILE TOP BANNER-->
                                            <h3 class="display-6">Mobile Banner<button type="button" class="btn btn-primary float-end" onclick="OpenSelectMediaModal('modalEditQuestImages','edit-quest-images-mobile-banner-img','edit-quest-images-mobile-banner-id')">Select Media</button></h3>
                                            <div class="w-100 ratio" style="--bs-aspect-ratio: 46.3%;">

                                                <img src="/assets/media/<?php echo $thisQuest["imagePath_mobile"]; ?>"  id="edit-quest-images-mobile-banner-img"/>

                                            </div>
                                            <!--Quest Icon-->
                                            <h3 class="display-6">Quest Icon<button type="button" class="btn btn-primary float-end" onclick="OpenSelectMediaModal('modalEditQuestImages','edit-quest-images-icon-img','edit-quest-images-icon-id')">Select Media</button></h3>
                                            <div class="col-md-6" >

                                                <img class="img-thumbnail" src="/assets/media/<?php echo $thisQuest["imagePath_icon"]; ?>"  id="edit-quest-images-icon-img"/>

                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                                            <input type="submit" name="edit-quest-images-submit" class="btn bg-ranked-1" value="Apply Changes" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <form method="POST">
                            <input type="hidden" value="<?php echo $thisQuest["Id"]; ?>" name="edit-quest-id" />
                            <input type="hidden" value="<?php echo $thisQuest["host_id_2"]; ?>" name="edit-quest-options-host-2-id" id="edit-quest-options-host-2-id"/>
                            <div class="modal modal-lg fade" id="modalEditQuestOptions" tabindex="-1" aria-labelledby="modalEditQuestOptionsLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5">Edit Quest Options</h1>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-3">
                                                <div class="col-12">
                                                    <div class="form-group">
                                                        <label for="edit-quest-options-title" class="form-label">Title:</label>
                                                        <input type="text" id="edit-quest-options-title" name="edit-quest-options-title" class="form-control" value="<?php echo $thisQuest["name"]; ?>">
                                                    </div>
                                                </div>
                                                
                                            </div>
                                            <div class="row mb-3">
                                                <label for="edit-quest-options-locator" class="form-label">URL:</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">https://kickback-kingdom.com/q/</span>
                                                    <input type="text" id="edit-quest-options-locator" name="edit-quest-options-locator" class="form-control" value="<?php echo $thisQuest["locator"]; ?>">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-lg-6 mb-3">
                                                    <label for="edit-quest-options-locator" class="form-label">Co-Host:</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fa-solid fa-user-plus"></i></span>
                                                        <input type="text" disabled class="form-control" value="<?php echo $thisQuest["host_name_2"]; ?>" />
                                                        <button type="button" class="btn btn-primary" onclick="OpenSelectAccountModal('modalEditQuestOptions')">Select</button>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 mb-3">
                                                    <div class="form-group">
                                                        <label for="edit-quest-options-questline"  class="form-label">Questline:</label>
                                                        
                                                        <div class="input-group">
                                                            <select class="form-select" name="edit-quest-options-questline" id="edit-quest-options-questline" aria-label="Default select example">
                                                                <option value="" selected>Does this quest belong to a questline?</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-text" id="basic-addon4">Need to create a questiline? Click <a href="<?php echo $urlPrefixBeta; ?>/adventurers-guild.php?new-quest-line=1">HERE</a></div>

                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-12">
                                                    <div class="form-group">
                                                        <label for="edit-quest-options-summary" class="form-label">Summary:</label>
                                                        <textarea class="form-control" id="edit-quest-options-summary" name="edit-quest-options-summary" rows="5"><?php echo $thisQuest["summary"]; ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            

                                            <div class="row mb-3">
                                                <div class="col-12">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <h5 class="card-title">Quest Date & Time Information</h5>
                                                            
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="form-check form-switch">
                                                                        <input class="form-check-input" type="checkbox" role="switch" id="edit-quest-options-has-a-date" name="edit-quest-options-has-a-date" <?php echo $thisQuest["end_date"] == null?"":"checked"; ?> onchange="OnDateTimeChangedForQuestOptions();">
                                                                        <label class="form-check-label" for="edit-quest-options-has-a-date">Quest has a date</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row" id="date-time-row">
                                                                <input type="hidden" name="edit-quest-options-datetime" id="edit-quest-options-datetime" value="<?php echo $thisQuest["end_date"]; ?>">

                                                                <div class="col-md-6 mb-3">
                                                                    <div class="form-group">
                                                                        <label for="edit-quest-options-datetime-date" class="form-label">Date:</label>
                                                                        <input type="date" id="edit-quest-options-datetime-date" name="edit-quest-options-datetime-date" value="<?php echo $thisQuestEndDate->format('Y-m-d'); ?>" onchange="OnDateTimeChangedForQuestOptions();" class="form-control">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <div class="form-group">
                                                                        <label for="edit-quest-options-datetime-time"  class="form-label">Time:</label>
                                                                        <input type="time" id="edit-quest-options-datetime-time" name="edit-quest-options-datetime-time" value="" data-utc-time="<?php echo $thisQuestEndDate->format('H:i'); ?>" onchange="OnDateTimeChangedForQuestOptions();" class="form-control">
                                                                    </div>
                                                                </div>

                                                                <script>
                                                                    toggleDateTimeVisibility();
                                                                    
                                                                    function OnDateTimeChangedForQuestOptions() {
                                                                        const dateInput = document.getElementById('edit-quest-options-datetime-date');
                                                                        const timeInput = document.getElementById('edit-quest-options-datetime-time');
                                                                        const combinedDatetimeInput = document.getElementById('edit-quest-options-datetime');

                                                                        // Combine date and time to create a local datetime string
                                                                        const localDateTime = `${dateInput.value}T${timeInput.value}:00`;

                                                                        // Convert to a Date object
                                                                        const localDateObj = new Date(localDateTime);

                                                                        // Format as UTC datetime string in "YYYY-MM-DD HH:MM:SS" format
                                                                        const utcDate = localDateObj.toISOString().slice(0, 10);
                                                                        const utcTime = String(localDateObj.getUTCHours()).padStart(2, '0') + ":" + 
                                                                                        String(localDateObj.getUTCMinutes()).padStart(2, '0') + ":" + 
                                                                                        String(localDateObj.getUTCSeconds()).padStart(2, '0');
                                                                        
                                                                        const utcDateTime = `${utcDate} ${utcTime}`;

                                                                        // Set the hidden input's value to the UTC datetime
                                                                        combinedDatetimeInput.value = utcDateTime;

                                                                        toggleDateTimeVisibility();
                                                                    }

                                                                    function GetSelectedQuestStyle() {
                                                                        return document.getElementById('edit-quest-options-style').value;
                                                                    }

                                                                    function OnQuestStyleChanged() {
                                                                            // First, hide all the descriptions
                                                                        for (let i = 0; i < 4; i++) {
                                                                            document.getElementById(`quest-style-desc-${i}`).style.display = 'none';
                                                                            var opt = document.getElementById(`quest-style-options-${i}`);
                                                                            if (opt != null)
                                                                                opt.style.display = 'none';
                                                                        }

                                                                        // Now, get the value of the selected option
                                                                        const selectedValue = GetSelectedQuestStyle();

                                                                        // If a valid option is selected, show the corresponding description
                                                                        if (selectedValue !== '') {
                                                                            document.getElementById(`quest-style-desc-${selectedValue}`).style.display = 'block';
                                                                            var opt = document.getElementById(`quest-style-options-${selectedValue}`);
                                                                            if (opt != null)
                                                                            opt.style.display = 'block';
                                                                        }

                                                                        OnQuestOptionChanged();
                                                                    }

                                                                    function OnQuestOptionChanged() {
                                                                        var bracketOptions = document.getElementById("quest-style-bracket-options");
                                                                        bracketOptions.style.display = "none";

                                                                        var selectedQuestStyle = GetSelectedQuestStyle();

                                                                        let radioElement = document.getElementById('edit-quest-options-0-bracket');
                                                                        if (radioElement.checked && selectedQuestStyle == '1') {
                                                                            bracketOptions.style.display = "block";
                                                                        }
                                                                    }

                                                                    function toggleDateTimeVisibility() {
                                                                        const hasDateCheckbox = document.getElementById('edit-quest-options-has-a-date');
                                                                        const dateTimeRow = document.getElementById('date-time-row');

                                                                        if (hasDateCheckbox.checked) {
                                                                            // If the checkbox is checked, show the date-time row
                                                                            dateTimeRow.style.display = '';
                                                                        } else {
                                                                            // If the checkbox is not checked, hide the date-time row
                                                                            dateTimeRow.style.display = 'none';
                                                                        }
                                                                    }

                                                                </script>

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                

                                                <div class="col-md-12 mb-3">
                                                    <div class="form-group">
                                                        <label for="edit-quest-options-style"  class="form-label">Quest Style:</label>
                                                        
                                                        <select id="edit-quest-options-style" name="edit-quest-options-style" class="form-select" aria-label="Default select example" onchange="OnQuestStyleChanged()">
                                                            <option value="0" <?php echo ($thisQuest["play_style"]==0?"selected":""); ?>>Casual</option>
                                                            <option value="1" <?php echo ($thisQuest["play_style"]==1?"selected":""); ?>>Ranked</option>
                                                            <option value="2" <?php echo ($thisQuest["play_style"]==2?"selected":""); ?>>Hardcore</option>
                                                            <option value="3" <?php echo ($thisQuest["play_style"]==3?"selected":""); ?>>Roleplay</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="alert alert-success" id="quest-style-desc-0" role="alert">
                                                        <h4 class="alert-heading">Casual Quest Style</h4>
                                                        <p><?php echo PlayStyleToDesc(0); ?></p>
                                                    </div>
                                                    <div class="alert alert-primary" id="quest-style-desc-1" role="alert">
                                                        <h4 class="alert-heading">Ranked Quest Style</h4>
                                                        <p><?php echo PlayStyleToDesc(1); ?></p>
                                                    </div>
                                                    <div class="alert alert-dark" id="quest-style-desc-2" role="alert">
                                                        <h4 class="alert-heading">Hardcore Quest Style</h4>
                                                        <p><?php echo PlayStyleToDesc(2); ?></p>
                                                    </div>
                                                    <div class="alert alert-warning"  id="quest-style-desc-3" role="alert">
                                                        <h4 class="alert-heading">Roleplay Quest Style</h4>
                                                        <p><?php echo PlayStyleToDesc(3); ?></p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-3" id="quest-style-options-0">
                                                <div class="col-12">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <h5 class="card-title">Casual Options</h5>
                                                            
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="radio" name="edit-quest-options-casual" value="custom" id="edit-quest-options-0-custom" <?php echo ($thisQuest["raffle_id"] == null?"checked":""); ?>>
                                                                        <label class="form-check-label" for="edit-quest-options-0-custom">
                                                                            Custom Event (Must be described in the quest information content)
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="radio" name="edit-quest-options-casual" value="raffle" id="edit-quest-options-0-raffle" <?php echo ($thisQuest["raffle_id"] == null?"":"checked"); ?>>
                                                                        <label class="form-check-label" for="edit-quest-options-0-raffle" >
                                                                            Raffle Event
                                                                        </label>
                                                                    </div>
                                                                    
                                                                </div>
                                                            </div>
                                                            
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-3" id="quest-style-options-1">
                                                <div class="col-12">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <h5 class="card-title">Ranked Options</h5>
                                                            
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="radio" name="edit-quest-options-ranked" value="custom" id="edit-quest-options-0-custom" <?php echo ($thisQuest["hasBracket"]==1?"":"checked"); ?> onchange="OnQuestOptionChanged();">
                                                                        <label class="form-check-label" for="edit-quest-options-0-custom">
                                                                            Custom Ranked Match (Must be explained in the quest information content)
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="radio" name="edit-quest-options-ranked" value="bracket" id="edit-quest-options-0-bracket" <?php echo ($thisQuest["hasBracket"]==1?"checked":""); ?> onchange="OnQuestOptionChanged();">
                                                                        <label class="form-check-label" for="edit-quest-options-0-bracket">
                                                                            Bracket Elimination Tournament
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-group mt-2">
                                                                        <div class="input-group">
                                                                            <span class="input-group-text"><i class="fa-solid fa-gamepad"></i></span>
                                                                            <select class="form-select" name="edit-quest-options-ranked-game" id="edit-quest-options-ranked-game" aria-label="Default select example">
                                                                                <option value="" selected>What game is being played?</option>
                                                                                <?php 
                                                                                    if ($games !== null) {
                                                                                        foreach($games as $game) {
                                                                                            if ($game['CanRank'] == 1) { // Only display games that can be ranked
                                                                                                echo '<option value="' . $game['Id'] . '">' . $game['Name'] . '</option>';
                                                                                            }
                                                                                        }
                                                                                    }

                                                                                ?>
                                                                            </select>
                                                                        </div>
                                                                        <div class="form-text" id="basic-addon4">Want to recommend a new game for Kickback Kingdom? Click <a href="<?php echo $urlPrefixBeta; ?>/games.php?request-new-game=1">HERE</a></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3" id="quest-style-bracket-options">
                                                <div class="col-12">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <h5 class="card-title">Bracket Options</h5>
                                                            
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="radio" name="quest-style-bracket-options-elimination" value="single" id="quest-style-bracket-options-elimination-single" disabled>
                                                                        <label class="form-check-label" for="quest-style-bracket-options-elimination-single">
                                                                            Single Elimination
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="radio" name="quest-style-bracket-options-elimination" value="double" id="quest-style-bracket-options-elimination-double" checked>
                                                                        <label class="form-check-label" for="quest-style-bracket-options-elimination-double">
                                                                            Double Elimination
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-check form-switch">
                                                                        <input class="form-check-input" type="checkbox" role="switch" id="quest-style-bracket-options-consolation" name="quest-style-bracket-options-consolation" checked disabled>
                                                                        <label class="form-check-label" for="quest-style-bracket-options-consolation">Consolation Round</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-12">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <h5 class="card-title">Registration Options</h5>
                                                            
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="form-check form-switch">
                                                                        <input class="form-check-input" type="checkbox" role="switch" id="edit-quest-options-require-approval" name="edit-quest-options-require-approval" disabled>
                                                                        <label class="form-check-label" for="edit-quest-options-require-approval">Require Approval By Host</label>
                                                                    </div>
                                                                    <div class="form-check form-switch">
                                                                        <input class="form-check-input" type="checkbox" role="switch" id="edit-quest-options-require-registration-form" name="edit-quest-options-require-registration-form" disabled>
                                                                        <label class="form-check-label" for="edit-quest-options-require-registration-form">Require Registration Form</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                                            <input type="submit" class="btn bg-ranked-1" name="edit-quest-options-submit" value="Apply changes" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="modal modal-xl fade" id="modalQuestPublish" tabindex="-1" aria-labelledby="modalQuestPublishLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5">Publish Quest</h1>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <p>Please make sure you save all of your changes before publishing your quest! Below are a list of items that need to be met before publishing a quest on Kickback Kingdom: </p>
                                        </div>
                                        
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-lg-12 col-xl-9">
                                            <?php 
                                                $feedCard = $thisQuest;
                                                $feedCard["type"] = "QUEST";
                                                $feedCard["title"] = $thisQuest["name"];
                                                $feedCard["image"] = $thisQuest["imagePath_icon"];
                                                $feedCard["published"] = true;
                                                $feedCard["account_1_username"] = $thisQuest["host_name"];
                                                $feedCard["account_2_username"] = $thisQuest["host_name_2"];
                                                $feedCard["text"] =  $thisQuest["summary"];
                                                require("php-components/feed-card.php");
                                            ?>
                                        </div>
                                        <div class="col-lg-12 col-xl-3">
                                            <?php if(QuestNameIsValid($feedCard["title"])) { ?>
                                                <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid Title</p>
                                            <?php } else { ?>
                                                <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Title is too short or invalid</p>
                                            <?php } ?>

                                            <?php if(QuestSummaryIsValid($feedCard["text"])) { ?>
                                                <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid Summary</p>
                                            <?php } else { ?>
                                                <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Summary is too short</p>
                                            <?php } ?>

                                            <?php if((is_null($thisQuest["content_id"])) || QuestPageContentIsValid($pageContent["data"])) { ?>
                                                <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid Content</p>
                                            <?php } else { ?>
                                                <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Content is too short</p>
                                            <?php } ?>

                                            <?php if(QuestLocatorIsValid($thisQuest["locator"])) { ?>
                                                <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid URL Locator</p>
                                            <?php } else { ?>
                                                <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Please use a valid url locator</p>
                                            <?php } ?>

                                            <?php if(QuestImagesAreValid($thisQuest)) { ?>
                                                <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid Images</p>
                                            <?php } else { ?>
                                                <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Please select a valid icon and banners</p>
                                            <?php } ?>
                                            <?php if(QuestRewardsAreValid($questRewards)) { ?>
                                                <p class="text-success"><i class="fa-solid fa-square-check"></i> Valid Rewards</p>
                                            <?php } else { ?>
                                                <p class="text-danger"><i class="fa-solid fa-square-xmark"></i> Please add rewards</p>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <form method="POST">
                                        <input type="hidden" name="quest-id" value="<?php echo $thisQuest["Id"]; ?>" />
                                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                                        <input type="submit" name="submit-quest-publish" class="btn bg-ranked-1" onclick="" <?php if(!QuestIsValidForPublish($thisQuest,$pageContent, $questRewards)) { ?>disabled<?php } ?> value="Submit Quest For Review" />
                                    </form>
                                </div>
                                </div>
                            </div>
                        </div>

                        <script>
                            function OpenModalEditQuestRewards()
                            {
                                $("#modalEditQuestRewards").modal("show");
                            }

                            function OpenModalEditQuestOptions()
                            {

                                OnQuestStyleChanged();
                                $("#modalEditQuestOptions").modal("show");
                            }

                            function OpenModalEditQuestImages()
                            {
                                $("#modalEditQuestImages").modal("show");

                            }

                            function OpenModalPublishQuest()
                            {

                                $("#modalQuestPublish").modal("show");
                            }
                        </script>

                        <?php } ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <nav>
                            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                <?php if ($showRaffleTab) { ?><button class="nav-link <?php echo $activeTab; $activeTab = ''; ?>" id="nav-raffle-tab" data-bs-toggle="tab" data-bs-target="#nav-raffle" type="button" role="tab" aria-controls="nav-raffle" aria-selected="true"><i class="fa-solid fa-ticket"></i></button><?php } ?>
                                <button class="nav-link <?php echo $activeTab; $activeTab = ''; ?>" id="nav-info-tab" data-bs-toggle="tab" data-bs-target="#nav-info" type="button" role="tab" aria-controls="nav-info" aria-selected="true"><i class="fa-solid fa-newspaper"></i></button>
                                <?php if ($showRewardsTab) { ?><button class="nav-link <?php echo $activeTab; $activeTab = ''; ?>" id="nav-rewards-tab" data-bs-toggle="tab" data-bs-target="#nav-rewards" type="button" role="tab" aria-controls="nav-rewards" aria-selected="false"><i class="fa-solid fa-gift"></i></button><?php } ?>
                                <?php if ($showResultsTab) { ?><button class="nav-link <?php echo $activeTab; $activeTab = ''; ?>" id="nav-results-tab" data-bs-toggle="tab" data-bs-target="#nav-results" type="button" role="tab" aria-controls="nav-results" aria-selected="false"><i class="fa-solid fa-ranking-star"></i></button><?php } ?>
                                <?php if ($showBracketTab) { ?><button class="nav-link <?php echo $activeTab; $activeTab = ''; ?>" id="nav-bracket-tab" data-bs-toggle="tab" data-bs-target="#nav-bracket" type="button" role="tab" aria-controls="nav-bracket" aria-selected="false" onclick="LoadBracket();"><i class="fa-solid fa-ranking-star"></i></button><?php } ?>
                                <?php if ($showParticipantsTab) { ?><button class="nav-link <?php echo $activeTab; $activeTab = ''; ?>" id="nav-participants-tab" data-bs-toggle="tab" data-bs-target="#nav-participants" type="button" role="tab" aria-controls="nav-participants" aria-selected="false"><i class="fa-solid fa-person-circle-check"></i></button><?php } ?>
                                <?php if ($showApplicantsTab) { ?><button class="nav-link <?php echo $activeTab; $activeTab = ''; ?>" id="nav-registrants-tab" data-bs-toggle="tab" data-bs-target="#nav-registrants" type="button" role="tab" aria-controls="nav-registrants" aria-selected="false"><i class="fa-solid fa-user-pen"></i></button><?php } ?>
                            </div>
                        </nav>
                        <div class="tab-content" id="nav-tabContent">
                            <?php if ($showRaffleTab) { ?>
                            <div class="tab-pane fade <?php echo $activeTabPage; $activeTabPage = ''; ?>" id="nav-raffle" role="tabpanel" aria-labelledby="nav-raffle-tab" tabindex="0">
                            <?php 
                                $raffleWinnerResp = GetRaffleWinner($thisQuest["raffle_id"]);
                                $raffleWinner = $raffleWinnerResp->Data[0];
                            ?>
                            <div class="tab-pane fade show <?php echo $activeTabPage; $activeTabPage = ''; ?>" id="nav-raffle" role="tabpanel" aria-labelledby="nav-raffle-tab" tabindex="0">
                                <div class="container py-5 text-center">
                                    <!-- Winner Announcement -->
                                    <?php if ($raffleWinner["account_id"] != null) { ?>
                                        
                                    <script>
                                    setTimeout(() => {
                                        StartConfetti();
                                    }, 500);
                                    
                                    setTimeout(() => {
                                        setInterval(AnimateWinner, 1000);
                                    }, 500);
                                    </script>
                                        <div class="winner-announcement">
                                            
                                            <div class="winner-announcement">
                                                <h1 class="winner-title animated fadeIn">Winner:</h1>
                                                <!-- Player Card HTML here -->
                                                <div class="d-flex flex-wrap justify-content-evenly align-items-center mt-3" id="winnerPlayerCardContainer">
                                                <?php 
                                                    $playerCardAccount = GetAccountById($raffleWinner["account_id"])->Data;
                                                    require("php-components/player-card.php"); 
                                                ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } else { ?>
                                        <img class="img-fluid for-light" src="/assets/images/logo-kk.png" alt="looginpage">
                                        
                                        <!-- Countdown Timer -->
                                        <div class="countdown-timer my-4">
                                            <div  id="countdown" class="timer d-flex flex-wrap justify-content-center my-3">
                                                <div class="bg-ranked-1 time-segment col-6 col-md-3"><span id="days"></span><small>Days</small></div>
                                                <div class="bg-ranked-1 time-segment col-6 col-md-3"><span id="hours"></span><small>Hours</small></div>
                                                <div class="bg-ranked-1 time-segment col-6 col-md-3"><span id="minutes"></span><small>Minutes</small></div>
                                                <div class="bg-ranked-1 time-segment col-6 col-md-3"><span id="seconds"></span><small>Seconds</small></div>
                                            </div>
                                        </div>
                                    <?php } ?>


                                    <!-- Raffle Jar Image -->
                                    <div class="raffle-jar my-4">
                                        
                                        <?php
                                            // Assume $raffleParticipants is an array of participants
                                            $numParticipants = count($raffleParticipants);
                                            $jarImageNumber = 16; // Default to the empty jar

                                            // Define the number of participants that each jar image represents
                                            $participantsPerJarImage = ceil(20 / 5); // Adjust 100 to your expected max number of participants

                                            // Calculate which jar image should be shown based on the number of participants
                                            if ($numParticipants > 0) {
                                                // Calculate the image number based on participants
                                                $jarImageNumber += ceil($numParticipants / $participantsPerJarImage);
                                                
                                                // Ensure that the jar number does not exceed the max image number (20 in this case)
                                                $jarImageNumber = min($jarImageNumber, 20);
                                            }

                                            // Now you have the correct jar image number to use in your img src
                                            $jarImageSrc = "/assets/media/raffle/{$jarImageNumber}.png";
                                        ?>

                                        <img id="raffleJarImg" src="<?php echo $jarImageSrc; ?>" alt="Raffle Jar" class="img-fluid mx-auto d-block">

                                    </div>
                                </div>
                            </div>

                            </div>
                            <?php } ?>
                            <div class="tab-pane fade <?php echo $activeTabPage; $activeTabPage = ''; ?>" id="nav-info" role="tabpanel" aria-labelledby="nav-info-tab" tabindex="0">
                            <div class="display-6 tab-pane-title">Quest Information</div>    
                            <?php 
                            
                            if ($thisQuest["content_id"] == null)
                            {
                                echo $thisQuest['desc']; 
                            }
                            else
                            {
                                $canEditContent = CanEditQuest($thisQuest);
                                $contentViewerEditorTitle = "Quest Information Manager";
                                require("php-components/content-viewer.php");
                            }
                            
                            ?>
                            </div>
                            <?php if ($showRewardsTab) { ?>
                            <div class="tab-pane fade <?php echo $activeTabPage; $activeTabPage = ''; ?>" id="nav-rewards" role="tabpanel" aria-labelledby="nav-rewards-tab" tabindex="0">
                            <div class="display-6 tab-pane-title">Quest Rewards</div>        
                            <div class="row">
                                    <div class="col-md-12">

                                        <?php


                                                // Now loop through the grouped array
                                        foreach ($questRewardsByCategory as $category => $questRewards) {
                                            ?>
                                        <div class="card mb-2">
                                            <div class="card-header">
                                                <h2><?php echo htmlspecialchars($category);?></h2>
                                            </div>
                                            <div class="card-body">
                                                <!-- side-bar colleps block stat-->
                                                <div class="inventory-grid">
                                                    <?php
                                                    
                                                    // Show category title

                                                    foreach ($questRewards as $questReward) {
                                                        ?>
                                                    <div class="inventory-item" onclick="ShowInventoryItemModal(<?php echo htmlspecialchars($questReward["Id"])?>);"  data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?php echo htmlspecialchars($questReward["name"])?>">
                                                        <img src="/assets/media/<?php echo htmlspecialchars($questReward["BigImgPath"])?>" alt="Item Name 1">
                                                        <!--<div class="item-count">x1</div>-->
                                                    </div>
                                                    
                                                    <?php
                                                    }

                                                    ?>
                                                </div>

                                            </div>
                                        </div>
                                        <?php
                                                }

                                                ?>



                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                            <?php if ($showResultsTab) { ?>
                            <div class="tab-pane fade <?php echo $activeTabPage; $activeTabPage = ''; ?>" id="nav-results" role="tabpanel" aria-labelledby="nav-results-tab" tabindex="0">
                            <div class="display-6 tab-pane-title">Quest Results</div>    
                            </div>
                            <?php } ?>
                            <?php if ($showBracketTab) { ?>
                            <div class="tab-pane fade <?php echo $activeTabPage; $activeTabPage = ''; ?>" id="nav-bracket" role="tabpanel" aria-labelledby="nav-bracket-tab" tabindex="0">
                            <div class="display-6 tab-pane-title">Tournament Bracket</div>        
                            <h1 id="bracketLoading" class="text-center">Bracket is loading...</h1>
                                <script>
                                    function LoadBracket() {
                                        
                                        HideRefreshButton();
                                        ShowLoading();
                                        DeleteBracketIframe();
                                        var iframeElement = document.getElementById("backet-iframe");
                                        if (iframeElement)
                                        {

                                        }
                                        else{

                                            setTimeout(CreateBracketIframe, 1);
                                        }
                                    }

                                    function CreateBracketIframe()
                                    {
                                        ShowRefreshButton();
                                        HideLoading();
                                        console.log("Loading iframe");
                                        // Create the iframe element
                                        var iframe = document.createElement('iframe');
                                        iframe.src = '<?php echo $urlPrefixBeta; ?>/bracket.php?locator=<?php echo $thisQuest["locator"];?>'; // Replace with the desired iframe URL
                                        iframe.style = "min-width: 200px; width: 100%; min-height: 700px; height:100%";
                                        iframe.innerText = "Hello world";
                                        iframe.id = "backet-iframe";
                                        // Insert the iframe into the HTML document
                                        document.getElementById("nav-bracket").appendChild(iframe);

                                    }

                                    function HideLoading()
                                    {
                                        document.getElementById('bracketLoading').style = "display:none;";
                                    }

                                    function ShowLoading()
                                    {
                                        document.getElementById('bracketLoading').style = "display:block;";
                                    }

                                    function HideRefreshButton()
                                    {
                                        //document.getElementById('bracket-refresh').style = "display:none;";
                                    }

                                    function ShowRefreshButton()
                                    {
                                        //document.getElementById('bracket-refresh').style = "display:block;";
                                    }

                                    function DeleteBracketIframe()
                                    {
                                        var element = document.getElementById("backet-iframe");
                                        if (element != null)
                                        {
                                        element.remove();

                                        }
                                    }

                                    function RefreshBracketIframe() {
                                        DeleteBracketIframe();
                                        LoadBracket();
                                    }


                                </script>
                            </div>
                            <?php } ?>
                            <?php if ($showParticipantsTab) { ?>
                            <div class="tab-pane fade <?php echo $activeTabPage; $activeTabPage = ''; ?>" id="nav-participants" role="tabpanel" aria-labelledby="nav-participants-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Quest Participants</div>    
                                <div class="d-flex flex-wrap justify-content-evenly align-items-center">
                                
                                <?php 

                                if ($thisQuest["raffle_id"] != null)
                                {
                                    
                                    foreach ($raffleParticipants as &$participant) {
                                        
                                        $playerCardAccount = $participant;
                                        require("php-components/player-card.php"); 
                                    }
                                    
                                }
                                else
                                {
                                    foreach ($questApplicants as $questApplicant) 
                                    {
                                        $drawParticipant = true;
                                        if ($questApplicant["participated"] == 0)
                                        {
                                            $drawParticipant = false;
                                        }
                                        if ($drawParticipant == 1)
                                        {
                                            $playerCardAccount = $questApplicant;
                                            require("php-components/player-card.php"); 

                                        }
                                    }
                                }

                                ?>
                                </div>
                            </div>
                            <?php } ?>
                            <?php if ($showApplicantsTab) { ?>
                            <div class="tab-pane fade <?php echo $activeTabPage; $activeTabPage = ''; ?>" id="nav-registrants" role="tabpanel" aria-labelledby="nav-registrants-tab" tabindex="0">
                                <div class="display-6 tab-pane-title">Quest Applicants</div>        
                                <div class="d-flex flex-wrap justify-content-evenly align-items-center">
                                    <?php
                                        foreach ($questApplicants as $questApplicant) {
                                            $playerCardAccount = $questApplicant;
                                            echo "<!--Player Card ".$playerCardAccount["Username"]."-->";
                                            require("php-components/player-card.php"); 
                                        }
                                    ?>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    <!--Test-->
    <?php require("php-components/base-page-javascript.php"); ?>
    <?php require("php-components/content-viewer-javascript.php"); ?>
    <!--Test 2-->
    <script>
        
var questDate = new Date('<?php echo date_format(date_create($thisQuest["end_date"]),"M j, Y H:i:s"); ?> UTC');
document.getElementById('quest_time').innerText = questDate.toLocaleDateString(undefined, {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric'
}) + ' ' + questDate.toLocaleTimeString();


<?php 
if ($showRaffleTab)
{

?>
// Countdown js
const second = 1000,
    minute = second * 60,
    hour = minute * 60,
    day = hour * 24;

var wasWatching = false;

var countDown = questDate.getTime(),
    x = setInterval(function() {

        var now = new Date().getTime(),
            distance = countDown - now;
            
        if (distance <= 0) {
            clearInterval(x);
            //document.getElementById('countdown').style = "display: none !important;";//"<div class='ended-message'>Raffle has ended</div>";
            if (wasWatching) {
            //window.location.reload();
            window.location.replace(window.location.pathname + window.location.search);
            }
            return;
        }
        else
        {

            wasWatching = true;

            document.getElementById('days').innerText = Math.floor(distance / (day)),
            document.getElementById('hours').innerText = Math.floor((distance % (day)) / (hour)),
            document.getElementById('minutes').innerText = Math.floor((distance % (hour)) / (minute)),
            document.getElementById('seconds').innerText = Math.floor((distance % (minute)) / second);
        }

    }, second);


        function PulseJar() {
            $('#raffleJarImg').toggleClass("animate-raffle-jar");
        }

        function AnimateWinner() {
            $("#winnerPlayerCardContainer").toggleClass("animate-winner");
        }

        // Call PulseJar every 2 seconds
        setInterval(PulseJar, 1000);

<?php 

}

?>
    </script>
</body>

</html>
