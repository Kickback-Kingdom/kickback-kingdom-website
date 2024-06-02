<?php
use Kickback\Controllers\QuestController;
?>
<div class="card mb-3 feed-card <?= $_vFeedCard->cssClassCard; ?>">
    <div class="row g-0">
        <div class="<?= $_vFeedCard->cssClassImageColSize; ?>" style="margin:auto;position: relative;">
            <?php if (!$_vFeedCard->hideType) { ?><span class="feed-stamp feed-stamp-quest <?= ($_vFeedCard->expired?"bg-tertiary":"bg-secondary bg-ranked-1"); ?>"><?= $_vFeedCard->typeText; ?></span><?php } ?>
            <img src="<?= $_vFeedCard->icon->getFullPath();?>"  class="img-fluid img-thumbnail"/>
        </div>
        <div class="<?= $_vFeedCard->cssClassTextColSize; ?> <?= $_vFeedCard->cssClassRight; ?>" >
            <div class="card-body <?= ($_vFeedCard->quoteStyleText?"card-body-vertical-center":""); ?>">
                <a class="feed-title" href="<?= $_vFeedCard->url; ?>">
                    <h5 class="card-title"><?= $_vFeedCard->title; ?></h5>
                </a>
                <?php if ($_vFeedCard->hasCreatedBy) { ?>
                <p class="card-text">
                    <small class="text-body-secondary"><?php if (!$_vFeedCard->createdByShowOnlyDate) { ?><?= $_vFeedCard->createdByPrefix; ?> by <?= $_vFeedCard->getAccountLinks(); ?>
                        <?php } if (!$_vFeedCard->hideDateTime) { ?>
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
                        <?= $_vFeedCard->quote->author; ?> <cite title="Source Date"> ~<?= $_vFeedCard->dateTime->dateTimeFormattedBasic; ?></cite>
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

                        $questRewardsResp = QuestController::getQuestRewardsByQuestId($_vFeedCard->quest);
                        $questRewards = $questRewardsResp->data;
                        
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
                    <span class="quest-tag quest-tag-<?= htmlspecialchars(strtolower(PlayStyleToName($_vFeedCard->style))); ?>" tabindex="0" data-bs-toggle="popover" data-bs-custom-class="custom-popover" 
                        data-bs-trigger="focus" data-bs-placement="right" data-bs-title="<?= htmlspecialchars(PlayStyleToName($_vFeedCard->style)); ?>"
                        data-bs-content="<?= htmlspecialchars(PlayStyleToDesc($_vFeedCard->style)); ?>"><?= htmlspecialchars(PlayStyleToName($_vFeedCard->style)); ?></span>
                        
                <?php } 
                
                if (!$_vFeedCard->hideCTA) { ?>
                    <a class="btn btn-sm float-end <?= ($_vFeedCard->expired?"text-white bg-tertiary":"bg-ranked-1"); ?>" href="<?= $_vFeedCard->getURL();?>"><?= $_vFeedCard->cta; ?> <i
                            class="fa-solid fa-angle-right"></i></a><?php } ?>
                </p>
            </div>
        </div>
    </div>
</div>