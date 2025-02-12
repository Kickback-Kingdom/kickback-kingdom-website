<?php 
use Kickback\Backend\Models\NotificationType;
        
            if (Kickback\Services\Session::isLoggedIn() && !is_null($activeAccountInfo->notifications))
            {
                
                for ($i=0; $i < count($activeAccountInfo->notifications); $i++) { 
                    # code...
                    $not = $activeAccountInfo->notifications[$i];

                    ?>
                    <div class="toast show mb-1" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="bg-primary text-bg-primary toast-header">
                            <strong class="me-auto">
                            <?php

                                echo $not->getTitle();

                            ?>    
                            </strong>
                            <small><?php echo $not->date->timeElapsedString(); ?></small>
                            <?php

                            switch ($not->type) {
                                case NotificationType::QUEST_REVIEW:
                                case NotificationType::QUEST_IN_PROGRESS:
                                case NotificationType::THANKS_FOR_HOSTING:
                                case NotificationType::QUEST_REVIEWED:
                                case NotificationType::PRESTIGE:
                                    break;

                                default:
                                    echo '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>';
                                    break;
                            }

                            ?>
                        </div>
                        <div class="toast-body">
                            <?php
                                echo $not->getText();
                            ?>

                        </div>
                        <?php 
                            switch ($not->type) {
                                case NotificationType::QUEST_REVIEW:
                                    ?>
                                        <div class="toast-body"><button class="bg-ranked-1 btn btn-sm" onclick="LoadQuestReviewModal(<?php echo $i ?>);"><i class="fa-solid fa-gift"></i> Collect Rewards</button></div>
                                    <?php
                                    break;
                                case NotificationType::THANKS_FOR_HOSTING:
                                    ?>
                                        <form method="POST">
                                            <input type="hidden" name="quest-notifications-thanks-for-hosting-quest-id" value="<?= $not->quest->crand; ?>"/>
                                            <div class="toast-body">
                                                <button type="submit" name="submit-notifications-thanks-for-hosting" class="bg-ranked-1 btn btn-sm"><i class="fa-solid fa-gift"></i> Collect Rewards</button>
                                            </div>
                                        </form>
                                    <?php
                                    break;
                                
                                case NotificationType::QUEST_REVIEWED:
                                    ?> 
                                        <!--<div class="toast-body"><a class="bg-ranked-1 btn btn-sm" href="#">View</a></div>-->
                                    <?php
                                    break;
                                case NotificationType::PRESTIGE:
                                    ?> 
                                        <div class="toast-body"><button class="bg-ranked-1 btn btn-sm" onclick="LoadNotificationViewPrestige(<?php echo $i ?>);"><i class="fa-sharp fa-solid fa-message-quote"></i> View Message</button></div>
                                        <?php
                                    break;
                                    
                                default:
                                    # code...
                                    break;
                            }
                        ?>
                    </div>

                    <?php
                } // for ($i=0; $i < count($activeAccountInfo->notifications); $i++)
            } // if (Kickback\Services\Session::isLoggedIn() && !is_null($activeAccountInfo->notifications))

//LoadNotificationViewPrestige
        ?>