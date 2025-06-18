<?php 

use Kickback\Common\Version;

use Kickback\Backend\Controllers\QuestController;

$feedCardType = "QUEST";
$feedCardTypeText = "QUEST";
if (isset($feedCard["type"]))
{
    $feedCardType = $feedCard["type"];
    $feedCardTypeText = $feedCardType;
}

$feedCardCreatedByPrefix = "Hosted";
$feedCardHasCreatedBy = false;
$feedCardImagePath = "";
$feedCardLearnMoreURL = "";
$feedCardDesc = "";
$feedCardHasTags = false;
$feedCardHasRewards = false;
$feedCardTitle = "";
$feedCardHostName = null;
$feedCardHostName2 = null;
$feedCardDate = new DateTime();
$feedCardHasCreatedBy = true;
$feedCardExpired = false;

if (isset($feedCard["title"]))
    $feedCardTitle = htmlspecialchars($feedCard["title"]);

if (isset($feedCard["image"]))
    $feedCardImagePath = htmlspecialchars($feedCard['image']);

if (isset($feedCard["event_icon_path"]))
    $feedCardImagePath = htmlspecialchars($feedCard['event_icon_path']);

if (isset($feedCard["text"]))
    $feedCardDesc = htmlspecialchars($feedCard["text"]);

if (isset($feedCard["account_1_username"]))
    $feedCardHostName = htmlspecialchars($feedCard["account_1_username"]);

if (isset($feedCard["account_2_username"]))
    $feedCardHostName2 = htmlspecialchars($feedCard['account_2_username']);

if (isset($feedCard["event_date"]))
{
    $feedCard["date"] = $feedCard["event_date"];
}

if (isset($feedCard["date"]))
{
    $feedCardHasDate = $feedCard["date"] != null;
    $feedCardDate = date_create($feedCard["date"]);
}
else
    $feedCardHasDate = false;

if (!array_key_exists("style",$feedCard))
{
    $feedCard["style"] = 0;
}
$feedCardCreatedByShowOnlyDate = false;
$feedCardCTA = "Learn More";
$feedCardHideCTA = false;
$feedCardHideType = false;
$feedCardQuoteStyleText = false;
$feedCardImageColSize = "col col-auto col-md";
$feedCardTextColSize = "col col-12 col-md-8 col-lg-9";
$feedCardClass = "";
$feedCardClassRight = "";
switch ($feedCardType) {
    case 'QUEST':
        $feedCardClass = "quest-card";
        $feedCardClassRight = "quest-card-right";
        $feedCardHasCreatedBy = true;
        $feedCardCreatedByPrefix = "Hosted";
        $feedCardLearnMoreURL = Version::urlBetaPrefix()."/q/".htmlspecialchars($feedCard['locator']);
        $feedCardHasTags = true;
        $feedCardHasRewards = true;
        $now = new DateTime(); // Current date and time
        $feedCardCTA = "View Quest";
        $feedCardExpired = ($feedCardDate < $now);
        if ($feedCard["published"] == false) {
            $feedCardTitle = "[DRAFT] ".$feedCardTitle." [DRAFT]"; 
        }
        break;

    case 'QUEST-LINE':
        $feedCardLearnMoreURL = Version::urlBetaPrefix()."/quest-line/".htmlspecialchars($feedCard['locator']);

        $feedCardTypeText = "QUEST LINE";
        $feedCardCTA = "View Quest Line";
        $feedCardCreatedByPrefix = "Created";
        if ($feedCard["published"] == false) {
            $feedCardTitle = "[DRAFT] ".$feedCardTitle." [DRAFT]"; 
        }
        break;

    case 'BLOG':
        $feedCardHasCreatedBy = true;
        $feedCardCreatedByPrefix = "Last written";
        $feedCardLearnMoreURL = Version::urlBetaPrefix()."/blog/".htmlspecialchars($feedCard['locator']);
        if ($feedCard["account_1_username"] == null)
        {
            $feedCardHasCreatedBy = false;
        }
        $feedCardCTA = "Open Blog";


        break;

    case 'BLOG-POST':
        $feedCardTypeText = "BLOG POST";
        $feedCardHasCreatedBy = true;
        $feedCardCreatedByPrefix = "Written";
        $feedCardLearnMoreURL = Version::urlBetaPrefix()."/blog/".htmlspecialchars($feedCard['locator']);

        $feedCardCTA = "View Post";
        if ($feedCard["published"] == false) {
            $feedCardTitle = "[DRAFT] ".$feedCardTitle." [DRAFT]"; 
        }

        break;

    case 'QUOTE':
        $feedCardCreatedByPrefix = "Said";
        $feedCardHideCTA = true;
        $feedCardHideType = true;
        $feedCardQuoteStyleText = true;
        $feedCardHasCreatedBy = false;
        $feedCardImageColSize = "col col-md";
        $feedCardTextColSize = "col col-8 col-md-8 col-lg-9";
        break;

    case 'QUEST-PARTICIPANT':
        $feedCardCTA = "View Quest";
        $feedCardLearnMoreURL = Version::urlBetaPrefix().htmlspecialchars($feedCard['event_url']);
        $feedCardTypeText = "PARTICIPATION";
        $feedCardCreatedByShowOnlyDate = true;
        $feedCardTitle = $profile->username.' '.$feedCard["event_verb"]." ".$feedCard["event_name"];
        if ($feedCard["event_verb"] == "BAILED ON")
        {

            $feedCardDesc = FlavorTextController::getBailedFlavorText($profile->username, $feedCard["event_name"], $profile->username.$feedCard["event_name"].$feedCard["event_date"]);
        }
        else{
            $feedCardDesc = FlavorTextController::getParticipationFlavorText($profile->username, $feedCard["event_name"], $profile->username.$feedCard["event_name"].$feedCard["event_date"]);

        }

        break;
    case 'GAME-RECORD':
        $feedCardHideCTA = true;
        $feedCardLearnMoreURL = Version::urlBetaPrefix().htmlspecialchars($feedCard['event_url']);
        $feedCardTypeText = "RANKED MATCH";
        $feedCardCreatedByShowOnlyDate = true;
        $feedCardTitle = $profile->username.' '.$feedCard["event_verb"]." a ranked match of ".$feedCard["event_name"];
        $feedCardDesc = '<strong>Team:</strong> '.$feedCard["event_team"]. " <strong>Character:</strong> ".$feedCard["event_character"]. " <strong>Random Character:</strong> ".($feedCard["event_character_was_random"] ? "Yes":"No");


        if ($feedCard["event_verb"] == "WON")
        {
            $feedCardDesc = FlavorTextController::getWonMatchFlavorText($profile->username, $feedCard["event_name"], $profile->username.$feedCard["event_name"].$feedCard["event_date"]);
        }
        else{
            //GetLostMatchFlavorText
            $feedCardDesc = FlavorTextController::getLostMatchFlavorText($profile->username, $feedCard["event_name"], $profile->username.$feedCard["event_name"].$feedCard["event_date"]);
        }

        break;

    case 'SPENT-PRESTIGE-TOKEN':
        $feedCardHideCTA = true;
        $feedCardLearnMoreURL = Version::urlBetaPrefix().htmlspecialchars($feedCard['event_url']);
        $feedCardTypeText = $feedCard["event_verb"];
        $feedCardCreatedByShowOnlyDate = true;
        $feedCardTitle = $profile->username.' '.$feedCard["event_verb"]." ".$feedCard["event_name"];
        $tempAccount = [];
        $tempAccount["Id"] = $feedCard["event_name_id"];
        if (!empty($feedCardImagePath))
            $tempAccount["avatar_media"] = $feedCardImagePath;
        $feedCardImagePath = GetAccountProfilePicture($tempAccount);
        if ($feedCard["event_verb"] == "COMMENDED")
        {
            $feedCardDesc = FlavorTextController::getCommendedSomeoneFlavorText($profile->username, $feedCard["event_name"], $profile->username.$feedCard["event_name"].$feedCard["event_date"]);
        }
        else{
            
            $feedCardDesc = FlavorTextController::getDenouncedSomeoneFlavorText($profile->username, $feedCard["event_name"], $profile->username.$feedCard["event_name"].$feedCard["event_date"]);
        }
        break;

    case 'RECEIVED-PRESTIGE':
        $feedCardHideCTA = true;
        $feedCardLearnMoreURL = Version::urlBetaPrefix().htmlspecialchars($feedCard['event_url']);
        $feedCardTypeText = $feedCard["event_verb"];
        $feedCardCreatedByShowOnlyDate = true;
        $feedCardTitle = $feedCard["event_name"].' '.$feedCard["event_verb"]." ".$profile->username;
        
        $tempAccount = [];
        $tempAccount["Id"] = $feedCard["event_name_id"];
        if (!empty($feedCardImagePath))
            $tempAccount["avatar_media"] = $feedCardImagePath;
        $feedCardImagePath = GetAccountProfilePicture($tempAccount);
        if ($feedCard["event_verb"] == "COMMENDED")
        {
            $feedCardDesc = FlavorTextController::getCommendedSomeoneFlavorText($feedCard["event_name"], $profile->username, $feedCard["event_name"].$profile->username.$feedCard["event_date"]);
        }
        else{
            
            $feedCardDesc = FlavorTextController::getDenouncedSomeoneFlavorText($feedCard["event_name"], $profile->username, $feedCard["event_name"].$profile->username.$feedCard["event_date"]);
        }
        break;

    case 'QUEST-HOSTED':
        $feedCardCTA = "View Quest";
        $feedCardLearnMoreURL = Version::urlBetaPrefix().htmlspecialchars($feedCard['event_url']);

        $feedCardTypeText = $feedCard["event_verb"];
        $feedCardCreatedByShowOnlyDate = true;
        $feedCardTitle = $profile->username.' '.$feedCard["event_verb"]." ".$feedCard["event_name"];
        $feedCardDesc = GetHostedQuestFlavorText($profile->username,$feedCard["event_name"],$feedCard["event_name"].$profile->username.$feedCard["event_date"]);
        break;

    case 'BADGE':
        $feedCardHideCTA = true;
        $feedCardLearnMoreURL = Version::urlBetaPrefix().htmlspecialchars($feedCard['event_url']);
        $feedCardTypeText = "NEW BADGE";
        $feedCardCreatedByShowOnlyDate = true;
        if ($feedCard["event_verb"] == "NOMINATED")
        {
            $feedCard["event_verb"] = "was NOMINATED for";
        }
        $feedCardTitle = $profile->username.' '.$feedCard["event_verb"]." the ".$feedCard["event_name"]." badge!";
        $feedCardDesc = GetEarnedBadgeFlavorText($profile->username,$feedCard["event_name"],$feedCard["event_name"].$profile->username.$feedCard["event_date"]);
        break;

    case 'TOURNAMENT':

        $feedCardCTA = "View Tournament";
        $feedCardLearnMoreURL = Version::urlBetaPrefix().htmlspecialchars($feedCard['event_url']);
        $feedCardTypeText = $feedCard["event_verb"]." TOURNAMENT";
        $feedCardCreatedByShowOnlyDate = true;
        $feedCardTitle = $profile->username.' '.$feedCard["event_verb"]." in the ".$feedCard["event_name"]." quest!";
        if ($feedCard["event_verb"] == "WON")
        {
            $feedCardDesc = GetWinTournamentFlavorText($profile->username,$feedCard["event_name"],$feedCard["event_name"].$profile->username.$feedCard["event_date"]);
        }
        else
        {
            $feedCardDesc = GetLostTournamentFlavorText($profile->username,$feedCard["event_name"],$feedCard["event_name"].$profile->username.$feedCard["event_date"]);
        }

        break;

    case 'WROTE-BLOG-POST':
//GetWroteBlogPostFlavorText
        $feedCardHideCTA = true;
        $feedCardLearnMoreURL = Version::urlBetaPrefix().htmlspecialchars($feedCard['event_url']);
        $feedCardTypeText = "NEW POST";
        $feedCardCreatedByShowOnlyDate = true;
        $feedCardTitle = $profile->username.' just '.$feedCard["event_verb"]." \"".$feedCard["event_name"]."\"";
        $feedCardDesc = GetWroteBlogPostFlavorText($profile->username,$feedCard["event_name"],$feedCard["event_name"].$profile->username.$feedCard["event_date"]);
        break;
}

$feedCardDateBasic = date_format($feedCardDate,"M j, Y");
$feedCardDateDetailed = date_format($feedCardDate,"M j, Y H:i:s");

?>

<div class="card mb-3 feed-card <?php echo $feedCardClass; ?>">
    <div class="row g-0">
        <div class="<?php echo $feedCardImageColSize; ?>" style="margin:auto;position: relative;">
            <?php if (!$feedCardHideType) { ?><span class="feed-stamp feed-stamp-quest <?php echo ($feedCardExpired?"bg-primary":"bg-secondary bg-ranked-1"); ?>"><?php echo $feedCardTypeText; ?></span><?php } ?>
            <img src="/assets/media/<?php echo htmlspecialchars($feedCardImagePath);?>"  class="img-fluid img-thumbnail"/>
        </div>
        <div class="<?php echo $feedCardTextColSize; ?> <?php echo $feedCardClassRight; ?>" >
            <div class="card-body <?php echo ($feedCardQuoteStyleText?"card-body-vertical-center":""); ?>">
                <a class="feed-title" href="<?php echo $feedCardLearnMoreURL; ?>">
                    <h5 class="card-title"><?php echo $feedCardTitle; ?></h5>
                </a>
                <?php if ($feedCardHasCreatedBy) { ?>
                <p class="card-text">
                    <small class="text-body-secondary"><?php if (!$feedCardCreatedByShowOnlyDate) { ?><?php echo $feedCardCreatedByPrefix; ?> by <a href="<?php echo Version::urlBetaPrefix(); ?>/u/<?php echo urlencode($feedCardHostName); ?>" class="username"><?php echo $feedCardHostName; ?></a>
                    <?php if ($feedCardHostName2 != null) { ?> and <a href="<?php echo Version::urlBetaPrefix(); ?>/u/<?php echo urlencode($feedCardHostName2); ?>" class="username"><?php echo $feedCardHostName2;?></a><?php } ?>
                    <?php } if ($feedCardHasDate) { ?>on <span class="date" data-bs-toggle="tooltip" data-bs-placement="bottom"
                        data-bs-title="<?php echo $feedCardDateDetailed; ?> UTC"><?php echo $feedCardDateBasic; ?></span><?php } else { ?>until completed<?php } ?>
                    </small>
                </p>
                <?php } 
                
                if ($feedCardQuoteStyleText) {
                    ?>

                <figure class="text-center">
                    <blockquote class="blockquote">
                        <p><?php echo $feedCardDesc; ?></p>
                    </blockquote>
                    <figcaption class="blockquote-footer">
                        <?php echo $feedCardHostName; ?> <cite title="Source Date"> ~<?php echo $feedCard["quoteDate"]; ?></cite>
                    </figcaption>
                </figure>

                    <?php

                } else {

                

                ?>
                <p><?php echo $feedCardDesc; ?></p>
                <?php
                }

                    if ($feedCardHasRewards)
                    {

                    $questRewards = QuestController::queryQuestRewardsByQuestId($feedCard["Id"]);
                    $displayedItemIds = [];

                    for ($j=0; $j < count($questRewards); $j++) { 
                        # code...
                            $questReward = $questRewards[$j];

                            
                            // If the item_id is in the displayedItemIds array, skip this iteration
                            if (in_array($questReward['Id'], $displayedItemIds)) {
                                continue;
                            }
                            
                            // Add the item_id to the displayedItemIds array
                            $displayedItemIds[] = $questReward['Id'];
                        ?>

                    <span tabindex="0" data-bs-toggle="popover" data-bs-custom-class="custom-popover" data-bs-trigger="focus"  data-bs-placement="top"
                        data-bs-title="<?php echo htmlspecialchars($questReward['name']); ?>" data-bs-content="<?php echo htmlspecialchars($questReward['desc']); ?>">
                        <img src="/assets/media/<?php echo htmlspecialchars($questReward['SmallImgPath']); ?>" class="loot-badge" />
                    </span>

                    <?php
                    }
                }
                ?>
                <p class="feed-tags">
                <?php if ($feedCardHasTags) { ?>
                    <span class="quest-tag quest-tag-<?php echo htmlspecialchars(strtolower(PlayStyleToName($feedCard["style"]))); ?>" tabindex="0" data-bs-toggle="popover" data-bs-custom-class="custom-popover" 
                        data-bs-trigger="focus" data-bs-placement="right" data-bs-title="<?php echo htmlspecialchars(PlayStyleToName($feedCard["style"])); ?>"
                        data-bs-content="<?php echo htmlspecialchars(PlayStyleToDesc($feedCard["style"])); ?>"><?php echo htmlspecialchars(PlayStyleToName($feedCard["style"])); ?></span>
                        
                <?php } 
                
                if (!$feedCardHideCTA) { ?>
                    <a class="btn btn-sm float-end <?php echo ($feedCardExpired?"text-white bg-primary":"bg-ranked-1"); ?>" href="<?php echo $feedCardLearnMoreURL;?>"><?php echo $feedCardCTA; ?> <i
                            class="fa-solid fa-angle-right"></i></a><?php } ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?php 

unset($feedCardHasCreatedBy);
unset($feedCardImagePath);
unset($feedCardTypeText);
unset($feedCardType);
unset($feedCardCreatedByPrefix);
unset($feedCardLearnMoreURL);
unset($feedCardDesc);
unset($feedCardHasTags);
unset($feedCardHasRewards);

?>
