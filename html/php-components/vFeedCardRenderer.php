<?php
use Kickback\Backend\Controllers\QuestController;
?>
<div class="card mb-3 feed-card <?= $_vFeedCard->cssClassCard; ?>">
    <div class="row g-0">
        <div class="<?= $_vFeedCard->cssClassImageColSize; ?>" style="margin:auto;position: relative;">
            <?php if (!$_vFeedCard->hideType) { ?><span class="feed-stamp feed-stamp-quest <?= (!$_vFeedCard->useGoldTrim()?"bg-tertiary":"bg-secondary bg-ranked-1"); ?>"><?= $_vFeedCard->typeText; ?></span><?php } ?>
            <img src="<?= $_vFeedCard->icon->getFullPath();?>"  class="img-fluid img-thumbnail"/>
        </div>
        <div class="<?= $_vFeedCard->cssClassTextColSize; ?> <?= $_vFeedCard->cssClassRight; ?>" >
            <div class="card-body <?= ($_vFeedCard->quoteStyleText?"card-body-vertical-center":""); ?>">
                <a class="feed-title" href="<?= $_vFeedCard->getURL(); ?>">
                    <h5 class="card-title"><?= $_vFeedCard->getTitle(); ?></h5>
                </a>
                <?php if ($_vFeedCard->hasCreatedBy) { ?>
                <p class="card-text">
                    <small class="text-body-secondary"><?php if (!$_vFeedCard->createdByShowOnlyDate) { ?><?= $_vFeedCard->createdByPrefix; ?> by <?= $_vFeedCard->getAccountLinks(); ?>
                        <?php } if ($_vFeedCard->hasDateTime()) { ?>
                            on <?= $_vFeedCard->dateTime->getDateTimeElement(); ?>
                        <?php } else { ?>until completed<?php } ?>
                    </small>
                </p>
                <?php } 
                
                if ($_vFeedCard->quoteStyleText) {
                    ?>

                <figure class="text-center">
                    <blockquote class="blockquote">
                        <p><?= $_vFeedCard->description; ?></p>
                    </blockquote>
                    <figcaption class="blockquote-footer">
                        <?= $_vFeedCard->quote->author; ?> <cite title="Source Date"> ~<?= $_vFeedCard->dateTime->formattedBasic; ?></cite>
                    </figcaption>
                </figure>

                    <?php

                } else {

                

                ?>
                <p><?= $_vFeedCard->description; ?></p>
                <?php
                }

                    if ($_vFeedCard->hasRewards)
                    {

                        $questRewards = QuestController::requestQuestRewardsByQuestId($_vFeedCard->quest);
                        $displayedItemIds = [];

                        for ($j=0; $j < count($questRewards); $j++) { 
                            # code...
                                $questReward = $questRewards[$j];

                                
                                // If the item_id is in the displayedItemIds array, skip this iteration
                                if (in_array($questReward->item->crand, $displayedItemIds)) {
                                    continue;
                                }
                                
                                // Add the item_id to the displayedItemIds array
                                $displayedItemIds[] = $questReward->item->crand;
                            ?>

                        <span tabindex="0" data-bs-toggle="popover" data-bs-custom-class="custom-popover" data-bs-trigger="focus"  data-bs-placement="top"
                            data-bs-title="<?= htmlspecialchars($questReward->item->name); ?>" data-bs-content="<?= htmlspecialchars($questReward->item->description); ?>">
                            <img src="<?= $questReward->item->iconSmall->getFullPath(); ?>" class="loot-badge" />
                        </span>

                        <?php
                        }
                    }
                ?>
                <p class="feed-tags">
                <?php if ($_vFeedCard->hasTags) { ?>
                    <span class="quest-tag quest-tag-<?= strtolower($_vFeedCard->quest->playStyle->getName()); ?>" tabindex="0" data-bs-toggle="popover" data-bs-custom-class="custom-popover" 
                        data-bs-trigger="focus" data-bs-placement="right" data-bs-title="<?= $_vFeedCard->quest->playStyle->getName(); ?>"
                        data-bs-content="<?= htmlspecialchars($_vFeedCard->quest->playStyle->getDescription()); ?>"><?= $_vFeedCard->quest->playStyle->getName(); ?></span>
                        
                <?php } 
                
                if (!$_vFeedCard->hideCTA) { ?>
                    <a class="btn btn-sm float-end <?= (!$_vFeedCard->useGoldTrim()?"text-white bg-tertiary":"bg-ranked-1"); ?>" href="<?= $_vFeedCard->getURL();?>"><?= $_vFeedCard->cta; ?> <i
                            class="fa-solid fa-angle-right"></i></a><?php } ?>
                </p>
            </div>
        </div>
    </div>
</div>
