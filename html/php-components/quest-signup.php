<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: (__DIR__ . "/..")) . "/Kickback/init.php");

use Kickback\Common\Version;

//$application_information = $thisQuest['application_information'];
if ($thisQuest->isRaffle())
{
?>

<div class="modal fade" id="actionModal" tabindex="-1" role="dialog" aria-labelledby="actionModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            <form method="post" action="<?php echo Version::urlBetaPrefix(); ?>/q/<?php echo $thisQuest->locator; ?>">
                <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">

                <input type="hidden" name="sessionToken"
                    value="<?php echo $_SESSION["sessionToken"]; ?>">
                <input type="hidden" name="serviceKey"
                    value="<?php echo $_SESSION["serviceKey"]; ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Raffle Entry</h5>
                    <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <img src="/assets/media/items/21.png" style="width: 100%;" />
                    <div class="row">
                        <div class="col-12">
                            <h3>How many tickets would you like to use?</h3>
                            <h6>Available Raffle Tickets: <?php echo $unusedTickets; ?></h6>
                            <fieldset>
                                <div class="input-group">
                                        <input class="touchspin" type="number" name="tickets" data-bts-init-val="1" min="1" max="<?php echo $unusedTickets; ?>">
                                        <!--<input class="touchspin" type="text" name="tickets" data-bts-init-val="1" oninput="validateMaxValue(this)">

                                        <script>
                                        function validateMaxValue(input) {
                                            var available = <?php echo $unusedTickets; ?>;
                                            if (input.value > available) {
                                                input.value = available;
                                            }
                                        }
                                        </script>-->
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="button"
                        data-bs-dismiss="modal">Close</button>
                    <input type="submit" class="btn bg-ranked-1"
                        name="submit-raffle" value="Submit Tickets" />
                </div>
            </form>
        </div>
    </div>
</div>

<?php

}
else
{



$title = "Quest Registration";
$areYouSure = "Are you sure you want to register for this quest?";
$warning = "You may recieve negative prestige if you register and don't attend!";
$yesText = "Yes, I want to Register!";
$img = "register.jpg";
if ($thisQuest->requiresApplication)
{

    $title = "Quest Application";
    $areYouSure = "Are you sure you want to apply for this quest?";
    $warning = "You may recieve negative prestige if you get accepted and don't attend!";
    $yesText = "Yes, submit my application!";
    $img = "apply.jpg";
}

?>
<div class="modal fade" id="actionModal" tabindex="-1" role="dialog" aria-labelledby="actionModal" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="post" action="<?= $thisQuest->getURL(); ?>">
                <input type="hidden" name="sessionToken" value="<?php echo $_SESSION["sessionToken"]; ?>">
                <input type="hidden" name="serviceKey" value="<?php echo $_SESSION["serviceKey"]; ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $title; ?></h5>
                    <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <img src="/assets/media/context/<?php echo $img; ?>" style="width: 100%;" />
                    <div class="row">
                        <div class="col">
                            <div style="padding: 16px;background-color: #dadada;border-radius: 8px;margin-top: 8px;">
                                <p style="color: black;">
                                    <strong><?php echo $areYouSure; ?></strong><br />
                                    <small><?php echo $warning; ?></small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="button" data-bs-dismiss="modal">Maybe Later</button>
                    <input type="submit" class="btn bg-ranked-1" name="submit-apply" value="<?php echo $yesText; ?>" />
                </div>
            </form>
        </div>
    </div>
</div>


<?php


}

?>
