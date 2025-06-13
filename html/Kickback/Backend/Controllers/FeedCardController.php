<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Services\Database;
use Kickback\Backend\Views\vActivity;
use Kickback\Backend\Views\vFeedCard;
use Kickback\Backend\Views\vFeedRecord;
use Kickback\Backend\Views\vQuote;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vQuest;
use Kickback\Backend\Views\vQuestLine;
use Kickback\Backend\Views\vBlog;
use Kickback\Backend\Views\vBlogPost;

use DateTime;

class FeedCardController
{
    public static function vQuote_to_vFeedCard(vQuote $quote) : vFeedCard
    {
        $feedCard = new vFeedCard();

        $feedCard->type = "QUOTE";
        $feedCard->typeText = "QUOTE";
        $feedCard->description = $quote->text;
        $feedCard->icon = $quote->icon;
        $feedCard->dateTime = new vDateTime();
        $feedCard->dateTime->formattedBasic = $quote->date;
        $feedCard->quoteStyleText = true;
        $feedCard->quote = $quote;
        $feedCard->createdByPrefix = "Said";
        $feedCard->hideCTA = true;
        $feedCard->hideType = true;
        $feedCard->hasCreatedBy = false;
        $feedCard->cssClassImageColSize = "col col-md";
        $feedCard->cssClassTextColSize = "col col-8 col-md-8 col-lg-9";
        $feedCard->title('');

        return $feedCard;
    }

    public static function vQuest_to_vFeedCard(vQuest $quest) : vFeedCard
    {
        $feedCard = new vFeedCard();
        $feedCard->type = "QUEST";
        $feedCard->typeText = "QUEST";
        
        $feedCard->icon = $quest->icon;
        $feedCard->title($quest->title);
        $feedCard->description = $quest->summary;
        $feedCard->quest = $quest;
        $feedCard->hasRewards = true;
        $feedCard->hasTags = true;
        $feedCard->cssClassCard = "quest-card";
        $feedCard->cssClassRight = "quest-card-right";
        $feedCard->dateTime = $quest->nullableEndDate();

        $feedCard->createdByPrefix = "Hosted";
        $feedCard->cta = "View Quest";
        $feedCard->url($quest->url());

        $feedCard->reviewStatus = $quest->reviewStatus;

        return $feedCard;
    }

    public static function vQuestLine_to_vFeedCard(vQuestLine $questLine) : vFeedCard
    {
        $feedCard = new vFeedCard();
        $feedCard->type = "QUEST-LINE";
        $feedCard->typeText = "QUEST LINE";
        $feedCard->cta = "View Quest Line";
        $feedCard->createdByPrefix = "Created";
        $feedCard->url($questLine->url());
        $feedCard->dateTime = $questLine->dateCreated;
        $feedCard->icon = $questLine->icon;
        $feedCard->title($questLine->title);
        $feedCard->description = $questLine->summary;
        $feedCard->reviewStatus = $questLine->reviewStatus;
        $feedCard->questLine = $questLine;

        return $feedCard;
    }

    public static function vBlogPost_to_vFeedCard(vBlogPost $blogPost) : vFeedCard
    {
        $feedCard = new vFeedCard();
        $feedCard->type = "BLOG-POST";
        $feedCard->typeText = "BLOG POST";
        
        $feedCard->icon = $blogPost->icon;
        $feedCard->title($blogPost->title);
        $feedCard->description = $blogPost->summary;
        $feedCard->blogPost = $blogPost;
        $feedCard->dateTime = $blogPost->publishedDateTime;
        $feedCard->createdByPrefix = "Written";

        $feedCard->url($blogPost->url());
        $feedCard->reviewStatus = $blogPost->reviewStatus;

        return $feedCard;
    }

    public static function vBlog_to_vFeedCard(vBlog $blog) : vFeedCard
    {
        $feedCard = new vFeedCard();
        $feedCard->type = "BLOG";
        $feedCard->typeText = "BLOG";


        $feedCard->icon = $blog->icon;
        $feedCard->title($blog->title);
        $feedCard->description = $blog->description;
        $feedCard->blog = $blog;
        $feedCard->dateTime = $blog->lastPostDate;
        $feedCard->createdByPrefix = "Last written";

        $feedCard->url($blog->url());

        if ($blog->lastWriter == null)
        {
            $feedCard->hasCreatedBy = false;
        }

        return $feedCard;
    }

    public static function vFeedRecord_to_vFeedCard(vFeedRecord $news) : vFeedCard
    {
        if ($news->quest != null)
        {
            return self::vQuest_to_vFeedCard($news->quest);
        }

        if ($news->questLine != null)
        {
            return self::vQuestLine_to_vFeedCard($news->questLine);
        }

        if ($news->blogPost != null)
        {
            return self::vBlogPost_to_vFeedCard($news->blogPost);
        }

        if ($news->blog != null)
        {
            return self::vBlog_to_vFeedCard($news->blog);
        }

        throw new \Exception("No card conversion for this feed record found.");
    }

    public static function vActivity_to_vFeedCard(vActivity $activity) : vFeedCard
    {
        $feedCard = new vFeedCard();
        $feedCard->activity = $activity;
        $feedCard->type = $activity->type;
        $feedCard->typeText = $activity->type;
        $feedCard->dateTime = $activity->dateTime;
        $feedCard->icon = $activity->getMedia();
        $feedCard->url('/'.$activity->url);
        switch ($feedCard->type) {
            case 'QUEST-PARTICIPANT':
                $feedCard->cta = "View Quest";
                $feedCard->typeText = "PARTICIPATION";
                $feedCard->createdByShowOnlyDate = true;
                $feedCard->title($activity->account->username.' '.$activity->verb.' '.$activity->name);

                if ($activity->verb == "BAILED ON")
                {

                    $feedCard->description = FlavorTextController::getBailedFlavorText($activity->account->username, $activity->name, $activity->account->username.$activity->name.$activity->dateTime->valueString);
                }
                else{
                    $feedCard->description = FlavorTextController::getParticipationFlavorText($activity->account->username, $activity->name, $activity->account->username.$activity->name.$activity->dateTime->valueString);

                }

                break;

            case 'GAME-RECORD':
                $feedCard->hideCTA = true;
                $feedCard->typeText = "RANKED MATCH";
                $feedCard->createdByShowOnlyDate = true;

                $feedCard->title($activity->account->username.' '.$activity->verb." a ranked match of ".$activity->name);


                if ($activity->verb == "WON")
                {
                    $feedCard->description = FlavorTextController::getWonMatchFlavorText($activity->account->username, $activity->name, $activity->account->username.$activity->name.$activity->dateTime->valueString);
                }
                else{
                    //GetLostMatchFlavorText
                    $feedCard->description = FlavorTextController::getLostMatchFlavorText($activity->account->username, $activity->name, $activity->account->username.$activity->name.$activity->dateTime->valueString);
                }
                break;

            case 'SPENT-PRESTIGE-TOKEN':
                $feedCard->hideCTA = true;
                $feedCard->typeText = $activity->verb;
                $feedCard->createdByShowOnlyDate = true;
                $feedCard->title($activity->account->username . ' ' . $activity->verb . " " . $activity->name);
                
                if ($activity->verb == "COMMENDED") {
                    $feedCard->description = FlavorTextController::getCommendedSomeoneFlavorText($activity->account->username, $activity->name, $activity->account->username . $activity->name . $activity->dateTime->valueString);
                } else {
                    $feedCard->description = FlavorTextController::getDenouncedSomeoneFlavorText($activity->account->username, $activity->name, $activity->account->username . $activity->name . $activity->dateTime->valueString);
                }
                break;

            case 'RECEIVED-PRESTIGE':
                $feedCard->hideCTA = true;
                $feedCard->typeText = $activity->verb;
                $feedCard->createdByShowOnlyDate = true;
                $feedCard->title($activity->name . ' ' . $activity->verb . " " . $activity->account->username);
                
                if ($activity->verb == "COMMENDED") {
                    $feedCard->description = FlavorTextController::getCommendedSomeoneFlavorText($activity->name, $activity->account->username, $activity->name . $activity->account->username . $activity->dateTime->valueString);
                } else {
                    $feedCard->description = FlavorTextController::getDenouncedSomeoneFlavorText($activity->name, $activity->account->username, $activity->name . $activity->account->username . $activity->dateTime->valueString);
                }
                break;

            case 'QUEST-HOSTED':
                $feedCard->cta = "View Quest";
                $feedCard->typeText = $activity->verb;
                $feedCard->createdByShowOnlyDate = true;
                $feedCard->title($activity->account->username . ' ' . $activity->verb . " " . $activity->name);
                $feedCard->description = FlavorTextController::getHostedQuestFlavorText($activity->account->username, $activity->name, $activity->name . $activity->account->username . $activity->dateTime->valueString);
                break;

            case 'BADGE':
                $feedCard->hideCTA = true;
                $feedCard->typeText = "NEW BADGE";
                $feedCard->createdByShowOnlyDate = true;
                if ($activity->verb == "NOMINATED") {
                    $activity->verb = "was NOMINATED for";
                }
                $feedCard->title($activity->account->username . ' ' . $activity->verb . " the " . $activity->name . " badge!");
                $feedCard->description = FlavorTextController::getEarnedBadgeFlavorText($activity->account->username, $activity->name, $activity->name . $activity->account->username . $activity->dateTime->valueString);
                break;

            case 'TOURNAMENT':
                $feedCard->cta = "View Tournament";
                $feedCard->typeText = $activity->verb . " TOURNAMENT";
                $feedCard->createdByShowOnlyDate = true;
                $feedCard->title($activity->account->username . ' ' . $activity->verb . " in the " . $activity->name . " quest!");
                if ($activity->verb == "WON") {
                    $feedCard->description = FlavorTextController::getWinTournamentFlavorText($activity->account->username, $activity->name, $activity->name . $activity->account->username . $activity->dateTime->valueString);
                } else {
                    $feedCard->description = FlavorTextController::getLostTournamentFlavorText($activity->account->username, $activity->name, $activity->name . $activity->account->username . $activity->dateTime->valueString);
                }
                break;

            case 'WROTE-BLOG-POST':
                $feedCard->hideCTA = true;
                $feedCard->typeText = "NEW POST";
                $feedCard->createdByShowOnlyDate = true;
                $feedCard->title($activity->account->username . ' just ' . $activity->verb . " \"" . $activity->name . "\"");
                $feedCard->description = FlavorTextController::getWroteBlogPostFlavorText($activity->account->username, $activity->name, $activity->name . $activity->account->username . $activity->dateTime->valueString);
                break;
        }

        return $feedCard;
    }
}
?>
