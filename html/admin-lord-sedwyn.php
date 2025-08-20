<?php

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Services\Session;
use Kickback\Backend\Controllers\DiscordController;
use Kickback\Backend\Config\ServiceCredentials;
if (!Session::isMagisterOfTheAdventurersGuild())
{
    header('Location: index.php');
    exit();
}

$lordSedwynMessages = [
    "By the grace of the realm, a new dawn rises over Kickback Kingdom. Let all guilds rejoice, for our numbers swell and our strength multiplies.",
    "Hark! The artisans of code and craft have wrought a new feature into existence. Visit the site and bear witness to their ingenuity.",
    "The Kingdom flourishes! 135 brave souls now call Kickback their home. May their banners fly high!",
    "Let it be known: the next tournament draws near. Steel your wits and ready your skills — glory awaits!",
    "A storm of ideas brews in the minds of our guild members. Game Jam #3 has been announced! May creativity guide your hand.",
    "The Emberwood Trading Company has returned! Their cargo holds brim with treasures — be swift, for the stock will not last.",
    "Our halls now echo with the laughter of new allies. To the freshly joined, Lord Sedwyn bids thee welcome!",
    "Today we celebrate stability — the realm’s servers stand firmer than ever. The foundation of our kingdom grows unshakable.",
    "A pact of peace and progress: the Accessibility & Stability category is now honored in our competitions. Let all games be made for all!",
    "By royal decree, no player shall be cast out for poor connection. In Kickback Kingdom, even the weakest link holds value.",
    "The forge has burned hot — a new quest system now shines with polish and purpose. Venture forth and leave thy mark.",
    "The L.I.C.H. awakens. The soft launch has begun. Let the decks clash and the legends rise!",
    "Word travels fast — Monday Night Magic Mania draws a crowd! Join us, and test thy mettle in fair battle.",
    "Lord Sedwyn has seen the reports — our monthly growth is strong. Fifteen percent more join our cause each moon!",
    "The Exiles of Conan cry out for justice, and the Sons of Atlas answer. Peace shall be restored to the land!",
    "Let it be remembered: in the battle against Trka and S-k1, our alliance stood victorious, our banners unbroken.",
    "The halls of Discord buzz like a lively tavern. Join the conversation and speak freely under the protection of the crown.",
    "The stars align — Atlas Odyssey deepens. Secrets of Obitus unfold, and the tale grows ever darker.",
    "An edict from the throne: all shall have the chance to shape the world. Our systems grow ever more open and player-driven.",
    "With each passing spin, Kickback Kingdom grows not only in power but in heart. Together, we write the saga.",
    "May your games be stable, your quests rewarding, and your enemies clever but beatable. Go forth, champions of Kickback!",
    "Whispers from the deep: a new point of interest has emerged in our underwater temple. Assemble thy squad and prepare.",
    "The lore thickens like winter stew. Discover the truth behind BioNova and the Crave outbreak, if ye dare...",
    "The Kingdom prepares for an age of plenty. The store now accepts shipments every fortnight. Patience brings fortune.",
    "Lo and behold — the floating origin tech now works! Players may soar from star to star with elegance and grace.",
    "Our conquest in Rise of Kingdoms has drawn the attention of titans. But fear not — we shall regroup and rise again.",
    "From the streets to the throne room, from game night to code deployment, the Kingdom is strong, because **you** are strong."
];


$kk_credentials = ServiceCredentials::instance();
    
// Ex: $webhookURL = "https://discord.com/api/webhooks/<some_number>/<api_key>"
$webhookURL = $kk_credentials["discord_api_url"] . '/' . $kk_credentials["discord_api_key"];

?>


<!DOCTYPE html>
<html lang="en">


<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    
    <?php 
    
    require("php-components/base-page-components.php"); 
    
    require("php-components/ad-carousel.php"); 
    
    ?>

    

    <!--MAIN CONTENT-->
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                <?php 
                    $activePageName = "I am Lord Sedwyn!";
                    require("php-components/base-page-breadcrumbs.php"); 
                ?>
                <?php
                if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["msg"])) {
                    $msg = trim($_POST["msg"]);
                    if ($msg !== "") {
                        DiscordController::sendWebhook($msg);
                        echo '<div class="alert alert-success mt-3" role="alert">';
                        echo '<strong>Message sent:</strong> ' . htmlspecialchars($msg);
                        echo '</div>';
                    }
                }

                $randomMessage = $lordSedwynMessages[array_rand($lordSedwynMessages)];

                ?>


                <form method="POST" class="mt-4">
                    <div class="mb-3">
                        <label for="msg" class="form-label"><strong>Message from Lord Sedwyn</strong></label>
                        <textarea class="form-control" id="msg" name="msg" rows="6"><?= htmlspecialchars($randomMessage) ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-megaphone-fill me-1"></i> Send Proclamation
                    </button>
                </form>

            </div>

            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>
</body>

</html>
