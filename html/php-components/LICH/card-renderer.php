<?php
declare(strict_types=1);

use Kickback\Common\Version;
?>
<!-- Save Progress Modal -->
<div class="modal fade" id="saveProgressModal" tabindex="-1" aria-labelledby="saveProgressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="saveProgressModalLabel">Saving Card</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul id="saveStepsList" class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Save Card Data
                        <span class="badge bg-secondary" id="step1-status">Pending</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Save Card Image
                        <span class="badge bg-secondary" id="step2-status">Pending</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Link Image to Card Data
                        <span class="badge bg-secondary" id="step3-status">Pending</span>
                    </li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-info" onclick="goToWikiPage()">Go to Wiki Page</button>
                <a type="button" class="btn btn-outline-primary" href="/lich/card-search">Go to Card Search</a>
                <a type="button" class="btn btn-outline-success" href="/lich-card-edit.php">Create New Card</a>
                <button type="button" class="btn btn-tertiary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<div class="container">
    <div class="row">
        <!-- Card Inputs -->
        <div class="col-md-6">
            <form id="lich-card-form" method="POST">
                
                <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">  
                <input id="input-lichCardData" type="hidden" name="lichCardData"/>
                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <!-- Save as Image Dropdown -->
                            <div class="btn-group">
                                <button id="save-card-button" type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-download" aria-hidden="true"></i> Save as Image
                                </button>
                                <ul class="dropdown-menu">
                                    <li><button type="button" class="dropdown-item" onclick="saveCardAsImage(1, 1);">Scale 1x</button></li>
                                    <li><button type="button" class="dropdown-item" onclick="saveCardAsImage(2, 2);">Scale 2x</button></li>
                                    <li><button type="button" class="dropdown-item" onclick="saveCardAsImage(3, 3);">Scale 3x</button></li>
                                    <li><button type="button" class="dropdown-item" onclick="saveCardAsImage(4, 4);">Scale 4x</button></li>
                                </ul>
                            </div>
                            
                            <!-- Save to Database -->
                            <button class="btn btn-outline-success" name="submit_lich_card_edit" type="button" onclick="saveCard()">Save To Database</button>
                            
                            <!-- Go to Wiki Page -->
                            <button type="button" class="btn btn-outline-info" onclick="goToWikiPage()">Go to Wiki Page</button>
                            
                            <!-- Go to Card Search -->
                            <a type="button" class="btn btn-outline-primary" href="<?= Version::urlBetaPrefix() ?>/lich/card-search">Go to Card Search</a>
                            
                            <!-- Create New Card -->
                            <a type="button" class="btn btn-outline-success" href="<?= Version::urlBetaPrefix() ?>/lich-card-edit.php">Create New Card</a>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="mb-3">
                        <label for="input-card-set" class="form-label">Set</label>
                        
                        <select class="form-select" id="input-card-set" onchange="updateCardData();">
                        <?php foreach ($lichSets as $index => $lichSet): ?>
                            <option 
                                value="<?= htmlspecialchars($lichSet->ctime . '|' . $lichSet->crand) ?>" 
                                <?= $index === 0 ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lichSet->name) ?>
                            </option>
                        <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="input-card-name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="input-card-name" value="Take 2 Steps Back" onchange="updateCardData();" onkeyup="updateCardData();" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="input-card-type" class="form-label">Type</label>
                        <select class="form-select" id="input-card-type" onchange="updateCardData();">
                            <option value="0" selected>Hero</option>
                            <option value="1">Equipment</option>
                            <option value="2">Technique</option>
                            <option value="3">Reaction</option>
                            <option value="4">Alteration</option>
                            <option value="5">Source</option>
                            <option value="6">Skill</option>
                            <option value="7">Summon</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="input-card-rarity" class="form-label">Rarity</label>
                        <select class="form-select" id="input-card-rarity" onchange="updateCardRarity();">
                            <option value="0" selected>Silver</option>
                            <option value="1">Gold</option>
                            <option value="2">Sapphire</option>
                            <option value="3">Amethyst</option>
                            <option value="4">Aether</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="input-card-locator" class="form-label">Locator</label>
                        <input type="text" class="form-control" id="input-card-locator" value="" onchange="updateCardData();" onkeyup="updateCardData();" required>
                    </div>
                </div>



                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Card Art</h5>
                        <button type="button" class="btn btn-primary" onclick="OpenSelectMediaModal(null,'cardArtIcon','cardArtIconFormInput', selectLichCardArtCallback);">
                            <i class="fa-solid fa-image me-2"></i>Select Media
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <!-- Card Art Preview -->
                            <div class="col-12 text-center">
                                <input type="hidden" id="cardArtIconFormInput" name="cardArtIcon" value="" />
                                <img class="img-thumbnail" src="" id="cardArtIcon" alt="Card Art Preview" style="max-width: 100%; height: auto;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="input-card-subtypes" class="form-label">Subtypes</label>
                    <div id="subtype-container">
                        <input type="text" class="form-control" id="input-card-subtypes" placeholder="Search or add a subtype">
                        <div id="subtype-tags" class="mt-2"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="input-card-description" class="form-label">Description</label>
                    <textarea class="form-control" id="input-card-description" rows="3" onchange="updateCardData();" onkeyup="updateCardData();" required>Negate 2 movement.</textarea>
                </div>
                <div class="row">
                    <!-- Health -->
                    <div class="col-md-4">
                        <div class="input-group mb-3">
                            <span class="input-group-text" id="inputGroup-sizing-default" style="color: red;">
                                <i class="fa-solid fa-heart"></i>
                            </span>
                            <input type="number" class="form-control" id="input-card-health" value="1" onchange="updateCardData();" onkeyup="updateCardData();">
                        </div>
                    </div>
                    <!-- Intelligence -->
                    <div class="col-md-4">
                        <div class="input-group mb-3">
                            <span class="input-group-text" id="inputGroup-sizing-default" style="color:rgb(221, 152, 151);"> <!-- Brain color -->
                                <i class="fa-solid fa-brain"></i>
                            </span>
                            <input type="number" class="form-control" id="input-card-intelligence" value="2" onchange="updateCardData();" onkeyup="updateCardData();">
                        </div>
                    </div>
                    <!-- Defense -->
                    <div class="col-md-4">
                        <div class="input-group mb-3">
                            <span class="input-group-text" id="inputGroup-sizing-default" style="color: orange;">
                                <i class="fa-solid fa-shield-alt"></i>
                            </span>
                            <input type="number" class="form-control" id="input-card-defense" value="3" onchange="updateCardData();" onkeyup="updateCardData();">
                        </div>
                    </div>
                </div>


                <div class="row">
                    <!-- First Line -->
                    <div class="col-md-4">
                        <div class="input-group mb-3">
                            <span class="input-group-text" style="color: white; background-color: purple;">
                            <i class="fa-solid fa-hourglass-start"></i>
                            </span>
                            <input type="number" class="form-control" id="input-source-arcanic" value="1" onchange="updateCardData();" onkeyup="updateCardData();">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group mb-3">
                            <span class="input-group-text" style="color: white; background-color: black;">
                            <i class="fa-solid fa-galaxy"></i>
                            </span>
                            <input type="number" class="form-control" id="input-source-abyssal" value="2" onchange="updateCardData();" onkeyup="updateCardData();">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group mb-3">
                            <span class="input-group-text" style="color: white; background-color: red;">
                                <i class="fa-solid fa-fire"></i>
                            </span>
                            <input type="number" class="form-control" id="input-source-thermic" value="3" onchange="updateCardData();" onkeyup="updateCardData();">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <!-- Second Line -->
                    <div class="col-md-4">
                        <div class="input-group mb-3">
                            <span class="input-group-text" style="color: white; background-color: green;">
                                <i class="fa-solid fa-leaf"></i>
                            </span>
                            <input type="number" class="form-control" id="input-source-verdant" value="4" onchange="updateCardData();" onkeyup="updateCardData();">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group mb-3">
                            <span class="input-group-text" style="color: black; background-color: white;">
                                <i class="fa-solid fa-sun"></i>
                            </span>
                            <input type="number" class="form-control" id="input-source-luminate" value="5" onchange="updateCardData();" onkeyup="updateCardData();">
                        </div>
                    </div>
                </div>

                
                <!-- Font Size Inputs -->
                <div class="mb-3">
                    <label for="font-size-card-name" class="form-label">Card Name Font Size</label>
                    <input type="number" class="form-control" id="font-size-card-name" value="0.8" step="0.05" onchange="updateFontSize('lich-card-name', this.value);">
                </div>

                <div class="mb-3">
                    <label for="font-size-card-type" class="form-label">Card Type Font Size</label>
                    <input type="number" class="form-control" id="font-size-card-type" value="0.5" step="0.05" onchange="updateFontSize('lich-card-type', this.value);">
                </div>

                <div class="mb-3">
                    <label for="font-size-card-description" class="form-label">Card Description Font Size</label>
                    <input type="number" class="form-control" id="font-size-card-description" value="0.7" step="0.05" onchange="updateFontSize('lich-card-description', this.value);">
                </div>



            </form>
        </div>

        <!-- Card Viewer -->
        <div class="col-md-6  d-flex justify-content-center align-items-center">
            <div id="lich-card-container" style="width: 100%;height: auto;position: relative;overflow: hidden;aspect-ratio: 2.5 / 3.5;">

                <div id="lich-card-renderer" style="position: absolute; aspect-ratio: 2.5 / 3.5; width: 350px; border-radius: 10px; overflow: hidden; transform: scale(1.31143); height: 490px; transform-origin: left top; top: 0px; left: 0px;">
                    
                    <!-- Card Art -->
                    <div id="lich-card-art" style="position: absolute;top: 17%;bottom: 44%;background: url(&quot;http://localhost/assets/images/lich/take%20a%20step%20back.png&quot;) center center / cover no-repeat;image-rendering: pixelated;width: 100%;"></div>

                    <!-- Card Background -->
                    <div id="lich-card-bg" style="
                        position: absolute;
                        top: 10px;
                        left: 10px;
                        right: 10px;
                        bottom: 10px;
                        background: url('/assets/images/lich/back.png') no-repeat center center;
                        background-size: cover;
                        image-rendering: pixelated;
                        ">
                    </div>

                    <!-- Card Art Frame -->
                    <div id="lich-card-art-frame" style="position: absolute;top: 10%;bottom: 37%;background: url('/assets/images/lich/hex.png') no-repeat center center;background-size: contain;image-rendering: pixelated;width: 100%;">
                    
                    </div>

                    <!-- Card Border -->
                    <img src="/assets/images/lich/border.png" class="lich-card-border" style="width: 100%; height: 100%; position: absolute; top: 0; left: 0; image-rendering: pixelated; " alt="Border">

                    <!-- Card Description Box -->
                    <div id="lich-card-description" style="position: absolute;top: 68%;left: 10%;right: 10%;padding: 10px;border-radius: 5px;font-family: 'Press Start 2P', monospace;font-size: 0.7em;text-align: left;color: #000000;overflow-wrap: break-word;">
                    Negate 1 movement.
                    </div>

                    <!-- Card Type Box -->
                    <div id="lich-card-type" style="position: absolute;top: 63.3%;left: 16%;right: 16%;border-radius: 5px;font-family: 'Press Start 2P', monospace;font-size: 0.5em;text-align: left;color: #000000;">Reaction</div>

                    <!-- Card Name Box -->
                    <div id="lich-card-name" style="position: absolute;top: 4.5%;left: 12%;right: 12%;border-radius: 5px;font-family: 'Press Start 2P', monospace;font-size: 0.8em;color: #000000;text-align: center;">Take a step back</div>

                    <!-- Evenly Spaced Divs Section with Numbers -->
                    <div id="lich-card-sources" style="position: absolute;top: 11%;left: 0px;right: 0px;display: flex;justify-content: center;align-items: center;">
                        <div class="lich-card-sources-arcanic" style="width: 32px; height: 32px; background: url('/assets/images/lich/slot2.png') no-repeat center center; background-size: cover; position: relative; image-rendering: pixelated; ">
                            <img src="/assets/images/lich/arcanic.png" style="width: 86%;height: auto;position: absolute;top: 5%;left: 7%;object-fit: contain;border-radius: 50%;image-rendering: pixelated;">
                            <div style="
                                position: absolute;
                                top: 50%;
                                left: 50%;
                                transform: translate(-50%, -50%);
                                color: black;
                                font-size: .8em;
                                font-family: 'Press Start 2P', monospace;
                                text-shadow: -1px -1px 0 white, 
                                1px -2px 0 white, 
                                -1px 1px 0 white, 
                                1px 1px 0 white;
                                ">
                                3
                            </div>
                        </div>
                        <div class="lich-card-sources-abyssal" style="width: 32px; height: 32px; background: url('/assets/images/lich/slot2.png') no-repeat center center; background-size: cover; position: relative; image-rendering: pixelated; ">
                            <img src="/assets/images/lich/abyssal.png" style="width: 86%;height: auto;position: absolute;top: 5%;left: 7%;object-fit: contain;border-radius: 50%;image-rendering: pixelated;">
                            <div style="
                                position: absolute;
                                top: 50%;
                                left: 50%;
                                transform: translate(-50%, -50%);
                                color: black;
                                font-size: .8em;
                                font-family: 'Press Start 2P', monospace;
                                text-shadow: -1px -1px 0 white, 
                                1px -2px 0 white, 
                                -1px 1px 0 white, 
                                1px 1px 0 white;
                                ">
                                2
                            </div>
                        </div>
                        <div class="lich-card-sources-thermic" style="width: 32px; height: 32px; background: url('/assets/images/lich/slot2.png') no-repeat center center; background-size: cover; position: relative; image-rendering: pixelated; ">
                            <img src="/assets/images/lich/thermic.png" style="width: 86%;height: auto;position: absolute;top: 5%;left: 7%;object-fit: contain;border-radius: 50%;image-rendering: pixelated;">
                            <div style="
                                position: absolute;
                                top: 50%;
                                left: 50%;
                                transform: translate(-50%, -50%);
                                color: black;
                                font-size: .8em;
                                font-family: 'Press Start 2P', monospace;
                                text-shadow: -1px -1px 0 white, 
                                1px -2px 0 white, 
                                -1px 1px 0 white, 
                                1px 1px 0 white;
                                ">
                                4
                            </div>
                        </div>
                        <div class="lich-card-sources-verdant" style="width: 32px; height: 32px; background: url('/assets/images/lich/slot2.png') no-repeat center center; background-size: cover; position: relative; image-rendering: pixelated; ">
                            <img src="/assets/images/lich/verdant.png" style="width: 86%;height: auto;position: absolute;top: 5%;left: 7%;object-fit: contain;border-radius: 50%;image-rendering: pixelated;">
                            <div style="
                                position: absolute;
                                top: 50%;
                                left: 50%;
                                transform: translate(-50%, -50%);
                                color: black;
                                font-size: .8em;
                                font-family: 'Press Start 2P', monospace;
                                text-shadow: -1px -1px 0 white, 
                                1px -2px 0 white, 
                                -1px 1px 0 white, 
                                1px 1px 0 white;
                                ">
                                1
                            </div>
                        </div>
                        <div class="lich-card-sources-luminate" style="width: 32px; height: 32px; background: url('/assets/images/lich/slot2.png') no-repeat center center; background-size: cover; position: relative; image-rendering: pixelated; ">
                            <img src="/assets/images/lich/luminate.png" style="width: 86%;height: auto;position: absolute;top: 5%;left: 7%;object-fit: contain;border-radius: 50%;image-rendering: pixelated;">
                            <div style="
                                position: absolute;
                                top: 50%;
                                left: 50%;
                                transform: translate(-50%, -50%);
                                color: black;
                                font-size: .8em;
                                font-family: 'Press Start 2P', monospace;
                                text-shadow: -1px -1px 0 white, 
                                1px -2px 0 white, 
                                -1px 1px 0 white, 
                                1px 1px 0 white;
                                ">
                                5
                            </div>
                        </div>
                    </div>

                    <!-- Health Icon with Number (Top Right) -->
                    <div id="lich-card-health" style="position: absolute;top: 12%;right: 5.5%;width: 11%;height: auto;">
                        <img src="/assets/images/lich/health.png" alt="Health" style="width: 100%;height: 100%;object-fit: contain; image-rendering: pixelated; ">
                        <div id="lich-card-health-value" style="position: absolute;top: 50%;left: 50%;transform: translate(-50%, -50%);color: black;font-size: 1.1em;font-weight: bold; font-family: 'Press Start 2P', monospace; text-shadow: 
                            -2px -2px 0 white, 
                            2px -2px 0 white, 
                            -2px 2px 0 white, 
                            2px 2px 0 white;">
                            10
                        </div>
                    </div>

                    <!-- INT Icon with Number (Top Left) -->
                    <div id="lich-card-int" style="position: absolute;top: 12%;left: 5.5%;width: 15%;height: auto;">
                        <img src="/assets/images/lich/int.png" alt="Intelligence" style="width: 100%;height: 100%;object-fit: contain; image-rendering: pixelated; ">
                        <div id="lich-card-int-value" style="position: absolute;top: 50%;left: 50%;transform: translate(-50%, -50%);color: black;font-size: 1.1em;font-weight: bold; font-family: 'Press Start 2P', monospace; text-shadow: 
                            -2px -2px 0 white, 
                            2px -2px 0 white, 
                            -2px 2px 0 white, 
                            2px 2px 0 white;">
                            8
                        </div>
                    </div>

                    <!-- Defense Icon with Number (Bottom Left) -->
                    <div id="lich-card-def" style="position: absolute;top: 48%;left: 5%;width: 15%;height: auto;">
                        <img src="/assets/images/lich/def.png" alt="Defense" style="width: 100%;height: 100%;object-fit: contain; image-rendering: pixelated; ">
                        <div id="lich-card-def-value" style="position: absolute;top: 50%;left: 50%;transform: translate(-50%, -50%);color: black;font-size: 1em;font-weight: bold; font-family: 'Press Start 2P', monospace; text-shadow: 
                            -2px -2px 0 white, 
                            2px -2px 0 white, 
                            -2px 2px 0 white, 
                            2px 2px 0 white;">
                            5
                        </div>
                    </div>

                    <!-- Set Icon -->
                    <div id="lich-card-set" style="position: absolute;top: 48%;right: 5%;width: 15%;height: auto;display:none;">
                        <img src="/assets/images/lich/awakening.png" alt="Defense" style="width: 100%;height: 100%;object-fit: contain; image-rendering: pixelated; ">
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>


<script>

const colorCombinationBackgrounds = {
    "arcanic": "/assets/images/lich/bg_arcanic.png",
    "abyssal": "/assets/images/lich/bg_abyssal.png",
    "thermic": "/assets/images/lich/bg_thermic.png",
    "verdant": "/assets/images/lich/bg_verdant.png",
    "luminate": "/assets/images/lich/bg_luminate.png",

    
    "arcanic-abyssal": "/assets/images/lich/bg_arcanic-abyssal.png",
    "arcanic-thermic": "/assets/images/lich/bg_arcanic-thermic.png",
    "arcanic-verdant": "/assets/images/lich/bg_arcanic-verdant.png",
    "arcanic-luminate": "/assets/images/lich/bg_arcanic-luminate.png",

    
    "abyssal-thermic": "/assets/images/lich/bg_abyssal-thermic.png",
    "abyssal-verdant": "/assets/images/lich/bg_abyssal-verdant.png",
    "abyssal-luminate": "/assets/images/lich/bg_abyssal-luminate.png",

    
    "thermic-verdant": "/assets/images/lich/bg_thermic-verdant.png",
    "thermic-luminate": "/assets/images/lich/bg_thermic-luminate.png",

    "verdant-luminate": "/assets/images/lich/bg_verdant-luminate.png",

    "tri": "/assets/images/lich/bg_tri.png",

    "hero": "/assets/images/lich/Hero_BG.png",

};
const rarityBorders = {
    0: "/assets/images/lich/Silver_HEX.png", // 
    1: "/assets/images/lich/Gold_HEX.png", // 
    2: "/assets/images/lich/Sapphire_HEX.png", // 
    3: "/assets/images/lich/Amethyst_HEX.png", // 
    4: "/assets/images/lich/Aether_HEX.png", // 
};


const lichCardTypes = {0: "Hero", 1: "Equipment", 2: "Technique", 3:"Reaction", 4:"Alteration", 5:"Source", 6:"Skill", 7: "Summon"};

const sources = ["arcanic", "abyssal", "thermic", "verdant", "luminate"];

const existingSubtypes = <?= json_encode($lichSubTypeNames); ?>;


const lichCardData = <?= json_encode($thisLichCardData); ?>


initCardForm(lichCardData);

function selectLichCardArtCallback(mediaId, mediaPath) {
    console.log("Callback Executed with Media ID:", mediaId);
    console.log("Callback Executed with Media Path:", mediaPath);

    updateCardData();
}

function getLichCardAttributes(card) {
    const type = card.type !== undefined && lichCardTypes[card.type] ? lichCardTypes[card.type] : "Unknown Type";
    const subTypes = Array.isArray(card.subTypes) && card.subTypes.length > 0 
        ? ` > ${card.subTypes.join(", ")}` 
        : ""; // Add ">" only if subtypes exist
    return `${type}${subTypes}`;
}


function initCardForm(data)
{

    // Update form fields
    document.getElementById("input-card-name").value = data.name;
    document.getElementById("input-card-locator").value = data.locator;
    document.getElementById("input-card-type").value = data.type;
    //document.getElementById("input-card-subtypes").value = data.subTypes.join(", ");
    document.getElementById("input-card-description").value = data.description;
    document.getElementById("input-card-health").value = data.health;
    document.getElementById("input-card-intelligence").value = data.intelligence;
    document.getElementById("input-card-defense").value = data.defense;
    document.getElementById("cardArtIcon").src = data.art.url;
    document.getElementById("cardArtIconFormInput").value = data.art.crand;
    // Populate rarity select box
    document.getElementById("input-card-rarity").value = data.rarity || 0;

    // Set initial art frame
    const artFrame = document.getElementById("lich-card-art-frame");
    artFrame.style.backgroundImage = `url('${rarityBorders[data.rarity] || rarityBorders[0]}')`;

    // Populate source inputs
    sources.forEach((source) => {
        const inputId = `input-source-${source}`;
        if (document.getElementById(inputId)) {
            document.getElementById(inputId).value = data[source];
        }
    });


    // Populate font size inputs
    document.getElementById("font-size-card-name").value = data.nameFontSize || 0.8;
    document.getElementById("font-size-card-type").value = data.typeFontSize || 0.5;
    document.getElementById("font-size-card-description").value = data.descriptionFontSize || 0.7;

    // Pre-create subtype tags
    const tagsContainer = document.getElementById("subtype-tags");
    tagsContainer.innerHTML = ""; // Clear existing tags
    data.subTypes.forEach((subtype) => {
        addSubtype(subtype);
    });

    initFontSizes();
}

function saveCardJSONToHiddenInput()
{
    document.getElementById("input-lichCardData").value = JSON.stringify(lichCardData);
}

function updateCardRarity() {
    const rarity = parseInt(document.getElementById("input-card-rarity").value, 10);
    lichCardData.rarity = rarity;

    // Update the art frame image based on rarity
    const frameImage = rarityBorders[rarity] || rarityBorders[0];
    const artFrame = document.getElementById("lich-card-art-frame");
    artFrame.style.backgroundImage = `url('${frameImage}')`;

    console.log(`Card rarity updated to: ${rarity}, frame set to: ${frameImage}`);

    saveCardJSONToHiddenInput();
}

function updateFontSize(targetId, fontSize) {
    const targetElement = document.getElementById(targetId);

    if (targetElement) {
        targetElement.style.fontSize = `${fontSize}em`;

        // Update the font size in lichCardData based on the targetId
        if (targetId === 'lich-card-name') {
            lichCardData.nameFontSize = parseFloat(fontSize) || 0.8;
        } else if (targetId === 'lich-card-type') {
            lichCardData.typeFontSize = parseFloat(fontSize) || 0.5;
        } else if (targetId === 'lich-card-description') {
            lichCardData.descriptionFontSize = parseFloat(fontSize) || 0.7;
        }
    } else {
        console.error(`Element with ID ${targetId} not found.`);
    }
    
    saveCardJSONToHiddenInput();
}


// Initialize font sizes on page load
function initFontSizes() {
    updateFontSize('lich-card-name', document.getElementById('font-size-card-name').value);
    updateFontSize('lich-card-type', document.getElementById('font-size-card-type').value);
    updateFontSize('lich-card-description', document.getElementById('font-size-card-description').value);
}

function populateCard(data) {

    updateBackgroundImage();
    //updateGradient();
    // Set card art and background url('${data.art}') no-repeat center center

    upscalePixelArt(data.art.url, 4).then((upscaledArt) => {
        document.getElementById("lich-card-art").style = `
        position: absolute;
        top: 16%;
        bottom: 43.5%;
        background: url('${upscaledArt}') no-repeat center center;
        background-size: contain;
        image-rendering: pixelated;
        width: 100%;`;

    });
    
    
    const artFrame = document.getElementById("lich-card-art-frame");
    artFrame.style.backgroundImage = `url('${rarityBorders[data.rarity] || rarityBorders[0]}')`;


    // Set type and subtypes as attributes
    const cardTypeElement = document.getElementById("lich-card-type");
    const attributes = getLichCardAttributes(data);
    cardTypeElement.textContent = attributes;
    cardTypeElement.style.fontSize = `${data.typeFontSize}em`;

    // Set card description
    const cardDescElement = document.getElementById("lich-card-description");
    cardDescElement.innerHTML = data.description.replace(/\n/g, "<br>"); // Convert \n to <br>
    cardDescElement.style.fontSize = `${data.descriptionFontSize}em`;

    const cardNameElement = document.getElementById("lich-card-name");
    cardNameElement.textContent = data.name;
    cardNameElement.style.fontSize = `${data.nameFontSize}em`;

    // Handle stats: Health, Intelligence, Defense
    const healthContainer = document.getElementById("lich-card-health");
    const intContainer = document.getElementById("lich-card-int");
    const defContainer = document.getElementById("lich-card-def");

    if (data.health > 0) {
        healthContainer.style.display = "block";
        document.querySelector("#lich-card-health-value").textContent = data.health;
    } else {
        healthContainer.style.display = "none";
    }

    if (data.intelligence > 0) {
        intContainer.style.display = "block";
        document.querySelector("#lich-card-int-value").textContent = data.intelligence;
    } else {
        intContainer.style.display = "none";
    }

    if (data.defense > 0) {
        defContainer.style.display = "block";
        document.querySelector("#lich-card-def-value").textContent = data.defense;
    } else {
        defContainer.style.display = "none";
    }

    const activeSources = sources.filter((source) => data[source] > 0);
    const sourcesContainer = document.getElementById("lich-card-sources");
    if (activeSources.length === 0) {
        // Hide the entire sources container if no sources are active
        sourcesContainer.style.display = "none";
    } else {
        // Show the sources container and update individual source elements
        sourcesContainer.style.display = "flex";
        // Set source values and visibility
        sources.forEach((source) => {
            const sourceDiv = document.querySelector(`.lich-card-sources-${source}`);
            const sourceValue = data[source];
            const sourceImg = sourceDiv.querySelector("img");
            const sourceNumberDiv = sourceDiv.querySelector("div");

            if (sourceValue === 0) {
                // Hide the image and number if the source value is 0
                sourceImg.style.display = "none";
                sourceNumberDiv.style.display = "none";
            } else {
                // Show the image and number otherwise
                sourceImg.style.display = "block";
                sourceNumberDiv.style.display = "block";
                sourceNumberDiv.textContent = sourceValue; // Update the source value
            }
        });
    }
}


function updateBackgroundImage() {
    // Get active sources
    const activeSources = sources
        .filter(source => parseInt(document.getElementById(`input-source-${source}`).value, 10) > 0);

    // Generate a key for the combination
    var key = activeSources.join("-");
    if (activeSources.length >= 3)
    {
        key = "tri";   
    }
    console.log(key);

    // Find the corresponding background image or use the default
    const backgroundImage = colorCombinationBackgrounds[key] || colorCombinationBackgrounds["hero"];

    // Update the background image
    const cardBackground = document.getElementById("lich-card-bg");
    cardBackground.style.backgroundImage = `url('${backgroundImage}')`;
}



function updateCardData() {

    lichCardData.name = document.getElementById("input-card-name").value;
    lichCardData.locator = document.getElementById("input-card-locator").value;
    lichCardData.type = parseInt(document.getElementById("input-card-type").value, 10);
    lichCardData.description = document.getElementById("input-card-description").value;



    let locatorInput = document.getElementById("input-card-locator");
    locatorInput.value = locatorInput.value.replace(/\s+/g, '-');
    lichCardData.locator = locatorInput.value;

    var setKeys = document.getElementById("input-card-set").value.split("|");
    console.log(setKeys);
    lichCardData.set.ctime = setKeys[0];
    lichCardData.set.crand = parseInt(setKeys[1], 10) || -1;

    // Update font sizes
    lichCardData.nameFontSize = parseFloat(document.getElementById("font-size-card-name").value) || 0.8;
    lichCardData.typeFontSize = parseFloat(document.getElementById("font-size-card-type").value) || 0.5;
    lichCardData.descriptionFontSize = parseFloat(document.getElementById("font-size-card-description").value) || 0.7;


    lichCardData.health = parseInt(document.getElementById("input-card-health").value, 10) || 0;
    lichCardData.intelligence = parseInt(document.getElementById("input-card-intelligence").value, 10) || 0;
    lichCardData.defense = parseInt(document.getElementById("input-card-defense").value, 10) || 0;

    lichCardData.arcanic = parseInt(document.getElementById("input-source-arcanic").value, 10) || 0;
    lichCardData.abyssal = parseInt(document.getElementById("input-source-abyssal").value, 10) || 0;
    lichCardData.thermic = parseInt(document.getElementById("input-source-thermic").value, 10) || 0;
    lichCardData.verdant = parseInt(document.getElementById("input-source-verdant").value, 10) || 0;
    lichCardData.luminate = parseInt(document.getElementById("input-source-luminate").value, 10) || 0;

    lichCardData.art.url = document.getElementById("cardArtIcon").src;
    lichCardData.art.crand = parseInt(document.getElementById("cardArtIconFormInput").value, 10) || -1;

    // Update subTypes from the dynamically created buttons
    const tagsContainer = document.getElementById("subtype-tags");
    lichCardData.subTypes = Array.from(tagsContainer.children).map(
        (button) => button.textContent.trim().replace(/\s×$/, "") // Remove " ×" from the button text
    );

    saveCardJSONToHiddenInput();
    populateCard(lichCardData);
}

function resizeCard() {
    const cardRenderer = document.getElementById("lich-card-renderer");
    const cardContainer = document.getElementById("lich-card-container");

    // Ensure the cardContainer has valid dimensions
    const containerWidth = cardContainer.offsetWidth || 1; // Avoid division by 0
    const containerHeight = cardContainer.offsetHeight || 1;

    // Base dimensions of the card renderer
    const baseWidth = 350;
    const baseHeight = 490;

    // Calculate scale factors
    const scaleX = containerWidth / baseWidth;
    const scaleY = containerHeight / baseHeight;

    // Choose the smaller scale factor to maintain aspect ratio
    const scaleFactor = Math.min(scaleX, scaleY);

    // Apply the scale transform with centered alignment
    cardRenderer.style.transform = `scale(${scaleFactor})`;
    cardRenderer.style.transformOrigin = "left top"; // Center scaling
}

function sanitizeFileName(name) {
    // Replace unsafe characters with underscores
    return name.replace(/[^a-zA-Z0-9-_]/g, '_');
}

function getFormattedSetForDirectory() {
    var dropdown = document.getElementById("input-card-set");
    var selectedText = dropdown.options[dropdown.selectedIndex].text;
    return selectedText.replace(/\s+/g, "-");
}

/**
 * Updates the status badge for a given step in the modal.
 * @param {string} stepId - The ID of the status badge element.
 * @param {string} statusText - The text to display in the badge.
 * @param {string} statusClass - The Bootstrap class for the badge (e.g., "success", "warning", "danger").
 */
function updateStepStatus(stepId, statusText, statusClass) {
    const statusElement = document.getElementById(stepId);
    statusElement.className = `badge bg-${statusClass}`;
    statusElement.textContent = statusText;
}

/**
 * Saves card data to the database using the save-card API.
 * @param {object} cardData - The card data to save.
 * @returns {Promise<object>} - The API response.
 */
function saveCardData(cardData) {
    const data = {
        lichCardData: JSON.stringify(cardData),
        sessionToken: "<?php echo $_SESSION['sessionToken']; ?>",
    };

    const params = new URLSearchParams(data);

    return fetch('<?= Version::formatUrl("/api/v1/lich/save-card.php"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: params,
    }).then(response => {
        if (!response.ok) throw new Error('Failed to save card data');
        return response.json();
    }).then(data => {
        if (!data.success) throw new Error(data.message);
        return data.data;
    });
}

function saveCard() {
    // Step 1: Save Card Data
    updateStepStatus("step1-status", "Pending", "tertiary");
    updateStepStatus("step2-status", "Pending", "tertiary");
    updateStepStatus("step3-status", "Pending", "tertiary");
    const modal = new bootstrap.Modal(document.getElementById('saveProgressModal'));
    modal.show();

    setTimeout(() => {
        // Step 1: Save Card Data
        updateStepStatus("step1-status", "In Progress", "success");


        saveCardData(lichCardData)
        .then(data => {
            

            updateStepStatus("step1-status", "Complete", "ranked-1");
            
            // Update card with the returned card ID
            lichCardData.ctime = data.ctime;
            lichCardData.crand = data.crand;
            lichCardData.item = data.item;
            // Proceed to Step 2
            updateStepStatus("step2-status", "In Progress", "success");
            var pixelation = 1;
            var ratio = 1250/350;
            var scale = 1;
            var imageScale = (1/pixelation)*scale;
            var canvasScale = (ratio*pixelation)*scale;
            imageScale = 2;
            canvasScale = 2;
            return handleCardImage(
                    document.getElementById('lich-card-renderer'),
                    {
                        upload: true,
                        uploadParams: {
                            directory: `lich/cards/${getFormattedSetForDirectory()}`,
                            name: `${lichCardData.name}`,
                            desc: `Lich card image for ${lichCardData.name}`,
                            sessionToken: "<?php echo $_SESSION['sessionToken']; ?>",
                            crand: lichCardData.cardImage.crand
                        },
                    }, 
                    imageScale,
                    canvasScale
                );
        })
        .then(data => {
            if (!data.success) throw new Error(data.message);
            console.log(data);
            // Update card with the saved image ID
            lichCardData.cardImage = {
                    crand: data.data.mediaId,
                    url: data.data.url,
                };
            updateStepStatus("step2-status", "Complete", "ranked-1");
            updateStepStatus("step3-status", "In Progress", "success");


            return saveCardData(lichCardData);
        })
            .then(data => {

                updateStepStatus("step3-status", "Complete", "ranked-1");
                console.log('Card successfully linked to image.');
            })
            .catch(error => {
                console.error('Error in saveCard process:', error);

                // Determine the step that failed
                const failedStep = document.getElementById("step1-status").textContent === "Complete" &&
                                   document.getElementById("step2-status").textContent === "Complete"
                    ? "step3-status"
                    : document.getElementById("step1-status").textContent === "Complete"
                        ? "step2-status"
                        : "step1-status";

                updateStepStatus(failedStep, "Failed", "danger");
            });
    }, 100); // Small delay to ensure the modal renders
}
/**
 * Links the card image to the card data in the database.
 * @param {object} lichCardData - The card data containing image information.
 * @returns {Promise<Response>} - The fetch promise.
 */
function linkCardImageToData(lichCardData) {
    const data = {
        card: lichCardData,
        sessionToken: "<?php echo $_SESSION['sessionToken']; ?>",
    };

    const params = new URLSearchParams(data);

    return fetch('<?= Version::formatUrl("/api/v1/lich/link-card-image.php"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: params.toString(),
    });
}

function uploadCanvas(canvas, lichCardData)
{
    const imgBase64 = canvas.toDataURL("image/png");
    const formData = new URLSearchParams();
    
    // Add required fields for your upload API
    formData.append('directory', 'lich/cards/'+getFormattedSetForDirectory()); // Adjust directory as needed
    formData.append('name', lichCardData.name+`_${lichCardData.ctime}_${lichCardData.crand}`);
    formData.append('desc', lichCardData.name+`_${lichCardData.ctime}_${lichCardData.crand}`);
    formData.append('imgBase64', imgBase64);
    formData.append('sessionToken', "<?php echo $_SESSION['sessionToken']; ?>");

    // Make POST request to the upload API
    return fetch('<?= Version::formatUrl("/api/v1/media/upload.php"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString()
    });
}

function saveCardImage(lichCardData) {
    // Capture the card as an image using html2canvas
    return html2canvas(document.getElementById('lich-card-renderer'))
        .then(canvas => {
            
            return uploadCanvas(canvas, lichCardData);
        })
        .then(response => {
            if (!response.ok) throw new Error('Failed to save card image');
            return response.json();
        })
        .then(data => {
            if (!data.success) throw new Error(data.message);

            // Update lichCardData with the returned media ID
            lichCardData.cardImage.crand = data.data.mediaId;
            lichCardData.cardImage.url = "";
            console.log('Card image saved with media ID:', data.data.mediaId);
        });
}



function saveCardAsImage(imageScale = 1, canvasScale = 1) {
    const cardName = document.getElementById("input-card-name").value;
    const safeFileName = sanitizeFileName(cardName) || "card_image";
        handleCardImage(
        document.getElementById("lich-card-renderer"), 
        { fileName: safeFileName, upload: false }, 
        imageScale,
        canvasScale
    );

}

/**
 * Captures the given HTML element as a canvas.
 * @param {HTMLElement} element - The element to capture.
 * @param {number} scale - Scale factor for high-resolution capture.
 * @returns {Promise<HTMLCanvasElement>} - A promise that resolves to the captured canvas.
 */
function captureElementAsCanvas(element, scale = 1) {
    const originalTransform = element.style.transform;
    element.style.transform = ""; // Temporarily remove scaling for accurate capture

    return html2canvas(element, {
        scale: scale,
        useCORS: true,
        backgroundColor: null, // Transparent background
        imageRendering: "pixelated", // Preserve pixelation
        logging: true, // Enable logging for debugging
    }).then((canvas) => {
        const pixelCanvas = document.createElement("canvas");
        const ctx = pixelCanvas.getContext("2d");

        // Set canvas dimensions based on scale
        pixelCanvas.width = canvas.width;
        pixelCanvas.height = canvas.height;

        // Disable image smoothing to retain pixelation
        ctx.imageSmoothingEnabled = false;

        // Draw the captured canvas onto the new canvas
        ctx.drawImage(canvas, 0, 0, canvas.width, canvas.height);
        return pixelCanvas; // Return the canvas with pixelated output
    }).finally(() => {
        element.style.transform = originalTransform; // Restore original scaling
    });
}

/**
 * Converts a canvas to a Base64 string.
 * @param {HTMLCanvasElement} canvas - The canvas to convert.
 * @returns {string} - The Base64 string of the canvas image.
 */
function canvasToBase64(canvas) {
    return canvas.toDataURL("image/png");
}

/**
 * Creates a pixelated version of a canvas.
 * @param {HTMLCanvasElement} canvas - The original canvas.
 * @param {number} scale - Scale factor for pixelation.
 * @returns {HTMLCanvasElement} - The pixelated canvas.
 */
function createPixelatedCanvas(canvas, scale = 1) {
    const pixelatedCanvas = document.createElement("canvas");
    pixelatedCanvas.width = canvas.width * scale;
    pixelatedCanvas.height = canvas.height * scale;

    const ctx = pixelatedCanvas.getContext("2d");
    ctx.imageSmoothingEnabled = false;
    ctx.mozImageSmoothingEnabled = false; // For older browsers
    ctx.webkitImageSmoothingEnabled = false;
    
    ctx.drawImage(
        canvas,
        0,
        0,
        canvas.width,
        canvas.height,
        0,
        0,
        pixelatedCanvas.width,
        pixelatedCanvas.height
    );

    return pixelatedCanvas;
}

function upscalePixelArt(imageSrc, scale) {
    return new Promise((resolve) => {
        const img = new Image();
        img.src = imageSrc;
        img.onload = () => {
            // Create a canvas for upscaling
            const canvas = document.createElement("canvas");
            canvas.width = img.width * scale;
            canvas.height = img.height * scale;

            const ctx = canvas.getContext("2d");
            ctx.imageSmoothingEnabled = false; // Preserve sharp edges
            ctx.mozImageSmoothingEnabled = false; // For older browsers
            ctx.webkitImageSmoothingEnabled = false;

            // Draw the image onto the canvas at the scaled size
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

            // Resolve with the upscaled image as a data URL
            resolve(canvas.toDataURL());
        };
    });
}


/**
 * Downloads the canvas image as a file.
 * @param {HTMLCanvasElement} canvas - The canvas to download.
 * @param {string} fileName - The name of the downloaded file.
 */
function downloadCanvasAsImage(canvas, fileName = "image.png") {
    const link = document.createElement("a");
    link.download = fileName;
    link.href = canvas.toDataURL("image/png");
    link.click();
}

/**
 * Uploads the Base64 image to a server.
 * @param {string} base64Image - The Base64 string of the image.
 * @param {object} options - Additional upload parameters (e.g., directory, name, etc.).
 * @returns {Promise<object>} - A promise that resolves with the server response.
 */
function uploadBase64Image(base64Image, options = {}) {
    const formData = new URLSearchParams({
        imgBase64: base64Image,
        ...options, // Additional parameters
    });

    return fetch('<?= Version::formatUrl("/api/v1/media/upload.php"); ?>', {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: formData.toString(),
    }).then(response => {
        if (!response.ok) throw new Error("Failed to upload image");
        return response.json();
    });
}

/**
 * Main handler for generating, downloading, or uploading the card image.
 * @param {HTMLElement} element - The element to capture.
 * @param {object} options - Configuration options (e.g., fileName, upload).
 * @param {number} imageScale - Scale factor for the initial capture.
 * @param {number} canvasScale - Scale factor for pixelation.
 */
function handleCardImage(element, options = {}, imageScale = 1, canvasScale = 1) {
    const { fileName = "card_image.png", upload = false, uploadParams = {} } = options;

    return captureElementAsCanvas(element, imageScale)
        .then(canvas => {
            return createPixelatedCanvas(canvas, canvasScale);
        })
        .then(canvas => {
            if (upload) {
                const base64Image = canvasToBase64(canvas);
                return uploadBase64Image(base64Image, uploadParams);
            } else {
                downloadCanvasAsImage(canvas, fileName);
            }
        })
        .catch(error => {
            console.error("Error processing card image:", error);
        });
}


function goToWikiPage() {
    // Get the card locator from the input field
    const cardLocator = document.getElementById("input-card-locator").value;

    // Sanitize and format the locator (if necessary)
    const formattedLocator = encodeURIComponent(cardLocator.trim());

    // Build the URL for the wiki page
    const wikiUrl = `/lich/card/${formattedLocator}`;

    // Redirect to the generated URL
    window.location.href = wikiUrl;
}


function initSubtypes() {
    const input = document.getElementById("input-card-subtypes");

    // Render suggestions dropdown
    const renderSuggestions = () => {
        const value = input.value.trim();

        // Filter existing subtypes based on input
        const suggestions = existingSubtypes.filter((subtype) =>
            subtype.toLowerCase().startsWith(value.toLowerCase()) && !lichCardData.subTypes.includes(subtype)
        );

        // Create a dropdown for suggestions
        let dropdown = input.nextElementSibling;
        if (!dropdown || dropdown.id !== "subtype-dropdown") {
            dropdown = document.createElement("ul");
            dropdown.id = "subtype-dropdown";
            dropdown.style.position = "absolute";
            dropdown.style.backgroundColor = "white";
            dropdown.style.border = "1px solid #ccc";
            dropdown.style.listStyleType = "none";
            dropdown.style.padding = "0";
            dropdown.style.margin = "0";
            dropdown.style.width = `${input.offsetWidth}px`;
            dropdown.style.zIndex = "1000";
            input.insertAdjacentElement("afterend", dropdown);
        } else {
            dropdown.innerHTML = ""; // Clear existing suggestions
        }

        // Add suggestions to the dropdown
        suggestions.forEach((subtype) => {
            const item = document.createElement("li");
            item.textContent = subtype;
            item.style.padding = "5px";
            item.style.cursor = "pointer";

            item.addEventListener("mouseover", () => {
                item.style.backgroundColor = "#f0f0f0";
            });

            item.addEventListener("mouseout", () => {
                item.style.backgroundColor = "white";
            });

            item.addEventListener("click", () => {
                addSubtype(subtype);
                dropdown.remove();
                input.value = ""; // Clear the input field
            });

            dropdown.appendChild(item);
        });

        // Remove dropdown if no suggestions are available
        if (suggestions.length === 0) {
            dropdown.remove();
        }
    };

    // Show dropdown on input focus
    input.addEventListener("focus", renderSuggestions);

    // Show dropdown on input typing
    input.addEventListener("input", renderSuggestions);

    // Hide dropdown when input loses focus
    input.addEventListener("blur", () => {
        setTimeout(() => {
            const dropdown = document.getElementById("subtype-dropdown");
            if (dropdown) dropdown.remove();
        }, 200); // Delay to allow click events to process
    });

    // Add new subtype when "Enter" is pressed
    input.addEventListener("keydown", (event) => {
        if (event.key === "Enter" && input.value.trim()) {
            addSubtype(input.value.trim());
            input.value = ""; // Clear the input field
            event.preventDefault(); // Prevent default form behavior
        }
    });
}



function addSubtype(subtype) {
    const tagsContainer = document.getElementById("subtype-tags");
    if (!lichCardData.subTypes.includes(subtype)) {
        lichCardData.subTypes.push(subtype);
    }

    // Check if the button is already rendered
    if ([...tagsContainer.children].some((child) => child.textContent.includes(subtype))) {
        return;
    }

    // Create a Bootstrap button for the new subtype
    const button = document.createElement("button");
    button.innerHTML = subtype+` <i class="fa-sharp fa-solid fa-circle-xmark"></i>`;
    button.className = "btn btn-sm btn-primary me-2 mb-2"; // Bootstrap classes for a small button
    button.style.display = "inline-block";


    button.addEventListener("click", () => {
        lichCardData.subTypes = lichCardData.subTypes.filter((s) => s !== subtype);
        button.remove();
        updateCardData();
    });

    tagsContainer.appendChild(button);

    // Add to existing subtypes if it's new
    if (!existingSubtypes.includes(subtype)) {
        existingSubtypes.push(subtype);
    }

    
    updateCardData();
}



initSubtypes();
resizeCard();
populateCard(lichCardData);
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
