<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");


use Kickback\Backend\Controllers\LichCardController;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vLichCard;

if (!isset($thisLichCardData))
{

    if (isset($_GET["locator"]))
    {
        // Retrieve the Lich Card by locator
        $response = LichCardController::getLichCardByLocator($_GET["locator"]);

        if ($response->success) {
            $thisLichCardData = $response->data;
            $thisLichCardData->populateEverything();
        } else {
            // Handle error (e.g., display an error message)
            $thisLichCardData = new vLichCard(); // Default to an empty Lich Card
        }
    }
    else{
        $thisLichCardData = new vLichCard();
    }
}

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
                $activePageName = htmlspecialchars($thisLichCardData->name) ?? "L.I.C.H. Card";
                require("php-components/base-page-breadcrumbs.php"); 
                ?>

                <div class="row">
                    <!-- Left: Card Image -->
                    <div class="col-md-5">
                        <img src="<?= htmlspecialchars($thisLichCardData->cardImage->getFullPath()); ?>" 
                             class="img-fluid rounded shadow-sm" 
                             alt="<?= htmlspecialchars($thisLichCardData->name); ?>" style=" image-rendering: pixelated;">
                    </div>

                    <!-- Right: Card Details -->
                    <div class="col-md-7">
                        <!-- Card Header -->
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <!-- Card Stats Table -->
                                <table class="table table-sm table-borderless">
                                    <tbody>
                                        <tr>
                                            <th scope="row">Name:</th>
                                            <td><?= htmlspecialchars($thisLichCardData->name); ?></td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Set:</th>
                                            <td><a href="/lich/set/<?= htmlspecialchars($thisLichCardData->set->locator); ?>"><?= htmlspecialchars($thisLichCardData->set->name); ?></a></td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Type:</th>
                                            <td>
                                                <?php
                                                // Define the lichCardTypes array
                                                $lichCardTypes = [
                                                    0 => "Hero",
                                                    1 => "Equipment",
                                                    2 => "Technique",
                                                    3 => "Reaction",
                                                    4 => "Alteration",
                                                    5 => "Source",
                                                    6 => "Skill",
                                                    7 => "Summon"
                                                ];

                                                // Get the card type name dynamically
                                                $type = $thisLichCardData->type ?? 0; // Default to 0 if type is not set
                                                $typeName = $lichCardTypes[$type] ?? "Unknown"; // Fallback to "Unknown" if type is out of bounds
                                                echo htmlspecialchars($typeName);
                                                ?>
                                            </td>
                                        </tr>
                                        <?php if (!empty($thisLichCardData->subTypes)): ?>
                                        <tr>
                                            <th scope="row">Sub Types:</th>
                                            <td><?= htmlspecialchars(implode(', ', $thisLichCardData->subTypes)); ?></td>
                                        </tr>
                                        <?php endif; ?>

                                        <tr>
                                            <th scope="row">Rarity:</th>
                                            <td>
                                                <?php
                                                $rarityNames = ['Silver', 'Gold', 'Sapphire', 'Amethyst', 'Aether'];
                                                $rarityBadgeClasses = [
                                                    'bg-dark-subtle text-bg-light',  // Silver
                                                    'bg-ranked-1',     // Gold
                                                    'bg-info text-white',    // Sapphire
                                                    'bg-primary text-white',     // Amethyst (requires a class like bg-purple in your CSS)
                                                    'bg-danger'        // Aether
                                                ];
                                                $rarity = $thisLichCardData->rarity ?? 0;
                                                echo "<span class='badge {$rarityBadgeClasses[$rarity]}'>" . htmlspecialchars($rarityNames[$rarity]) . "</span>";
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <?php if ($thisLichCardData->health > 0): ?>
                                                <th scope="row">Health:</th>
                                                <td>
                                                    <img src="/assets/images/lich/health.png" alt="Health" title="Health" 
                                                        class="me-2" style="width: auto;height: 24px;image-rendering: pixelated;padding-left: 4px;padding-right: 3px;">
                                                    <?= htmlspecialchars($thisLichCardData->health); ?>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                        <tr>
                                            <?php if ($thisLichCardData->intelligence > 0): ?>
                                                <th scope="row">Intelligence:</th>
                                                <td>
                                                    <img src="/assets/images/lich/int.png" alt="Intelligence" title="Intelligence" 
                                                        class="me-2" style="width: 24px; height: 24px;  image-rendering: pixelated;">
                                                    <?= htmlspecialchars($thisLichCardData->intelligence); ?>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                        <tr>
                                            <?php if ($thisLichCardData->defense > 0): ?>
                                                <th scope="row">Defense:</th>
                                                <td>
                                                    <img src="/assets/images/lich/def.png" alt="Defense" title="Defense" 
                                                        class="me-2" style="width: auto;height: 24px;image-rendering: pixelated;padding-left: 3px;padding-right: 1px;">
                                                    <?= htmlspecialchars($thisLichCardData->defense); ?>
                                                </td>
                                            <?php endif; ?>
                                        </tr>

                                        <tr>
                                            <th scope="row">Description:</th>
                                            <td><?= str_replace("\n", "<br>", $thisLichCardData->description); ?></td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Sources:</th>
                                            <td>
                                                <div class="d-flex flex-wrap">
                                                    <?php
                                                    // Define sources and their respective values
                                                    $sources = [
                                                        'arcanic' => $thisLichCardData->arcanic,
                                                        'abyssal' => $thisLichCardData->abyssal,
                                                        'thermic' => $thisLichCardData->thermic,
                                                        'verdant' => $thisLichCardData->verdant,
                                                        'luminate' => $thisLichCardData->luminate
                                                    ];

                                                    // Iterate through each source and display its icon and value
                                                    foreach ($sources as $sourceName => $sourceValue) {
                                                        if ($sourceValue > 0) {
                                                            $iconPath = "/assets/images/lich/$sourceName.png";
                                                            echo "
                                                            <div class='lich-card-source' style='width: 32px; height: 32px; background: url(\"/assets/images/lich/slot2.png\") no-repeat center center; background-size: cover; position: relative; image-rendering: pixelated; margin-right: 10px;'>
                                                                <img src='$iconPath' alt='$sourceName' title='$sourceName: $sourceValue' 
                                                                    style='width: 86%; height: auto; position: absolute; top: 5%; left: 7%; object-fit: contain; border-radius: 50%; image-rendering: pixelated;'>
                                                                <div style='
                                                                    position: absolute;
                                                                    top: 50%;
                                                                    left: 50%;
                                                                    transform: translate(-50%, -50%);
                                                                    color: black;
                                                                    font-size: 0.8em;
                                                                    font-family: \"Press Start 2P\", monospace;
                                                                    text-shadow: -1px -1px 0 white, 
                                                                                1px -1px 0 white, 
                                                                                -1px 1px 0 white, 
                                                                                1px 1px 0 white;
                                                                '>
                                                                    $sourceValue
                                                                </div>
                                                            </div>";
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                            </td>
                                        </tr>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <?php if (Kickback\Services\Session::isServantOfTheLich()) { ?>
                        <a href="/lich/card/edit/<?= $thisLichCardData->locator;?>" class="btn btn-primary">Edit Card</a>
                        <?php } ?>
                    </div>


                    <!-- Content Section -->
                    <div class="mt-4">
                        <div class="display-6 tab-pane-title">Card Information</div>
                        <?php 
                        if ($thisLichCardData->hasPageContent()) {
                            $_vCanEditContent = $thisLichCardData->canEdit();
                            $_vContentViewerEditorTitle = "L.I.C.H. Card Information Manager";
                            $_vPageContent = $thisLichCardData->getPageContent();
                            require("php-components/content-viewer.php");
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>
    <?php 
    if ($thisLichCardData->hasPageContent())
    {
        $_vPageContent = $thisLichCardData->getPageContent();
        require("php-components/content-viewer-javascript.php"); 
    }
    ?>
</body>

</html>
